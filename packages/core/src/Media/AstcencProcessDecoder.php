<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Media;

use Refatbd\FreeFire\Exception\MediaException;

final class AstcencProcessDecoder implements AstcDecoderInterface
{
    public function __construct(
        private readonly string $binary = 'astcenc',
        private readonly int $timeoutSeconds = 20,
    ) {}

    public function name(): string
    {
        return 'astcenc-process';
    }

    public function available(): bool
    {
        return function_exists('proc_open') && $this->resolveBinary() !== null;
    }

    public function decodeFile(string $inputAstc, string $outputPng): void
    {
        $binary = $this->resolveBinary();
        if (!function_exists('proc_open') || $binary === null) {
            throw new MediaException('astcenc executable is unavailable.');
        }
        if (!is_file($inputAstc) || !is_readable($inputAstc)) {
            throw new MediaException('ASTC input file is unavailable.');
        }

        $command = [$binary, '-dl', $inputAstc, $outputPng];
        $specification = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $specification, $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new MediaException('Could not start astcenc.');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $startedAt = microtime(true);
        $stdout = '';
        $stderr = '';
        $observedExitCode = null;
        $timedOut = false;

        try {
            do {
                $stdout .= (string) stream_get_contents($pipes[1]);
                $stderr .= (string) stream_get_contents($pipes[2]);
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $observedExitCode = is_int($status['exitcode']) && $status['exitcode'] >= 0
                        ? $status['exitcode']
                        : null;
                    break;
                }
                if (microtime(true) - $startedAt > max(1, $this->timeoutSeconds)) {
                    $timedOut = true;
                    proc_terminate($process, 9);
                    break;
                }
                usleep(25_000);
            } while (true);
        } finally {
            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
        }

        $closedExitCode = proc_close($process);
        if ($timedOut) {
            @unlink($outputPng);
            throw new MediaException('astcenc timed out.');
        }

        $exitCode = $observedExitCode ?? $closedExitCode;
        if ($exitCode !== 0 || !is_file($outputPng) || filesize($outputPng) === 0) {
            @unlink($outputPng);
            $detail = trim($stderr !== '' ? $stderr : $stdout);
            throw new MediaException('astcenc failed'.($detail !== '' ? ': '.$detail : '.'));
        }
    }

    private function resolveBinary(): ?string
    {
        $binary = trim($this->binary, " \t\n\r\0\x0B\"'");
        if ($binary === '' || str_contains($binary, "\0")) {
            return null;
        }

        if (str_contains($binary, DIRECTORY_SEPARATOR)
            || (DIRECTORY_SEPARATOR === '\\' && str_contains($binary, '/'))) {
            $real = realpath($binary);
            return $real !== false && is_file($real) && is_executable($real) ? $real : null;
        }

        $path = (string) getenv('PATH');
        $extensions = [''];
        if (PHP_OS_FAMILY === 'Windows') {
            $extensions = array_values(array_filter(explode(';', (string) getenv('PATHEXT'))));
            if ($extensions === []) {
                $extensions = ['.EXE', '.BAT', '.CMD'];
            }
        }

        foreach (explode(PATH_SEPARATOR, $path) as $directory) {
            $directory = trim($directory, " \t\n\r\0\x0B\"");
            if ($directory === '') {
                continue;
            }
            foreach ($extensions as $extension) {
                $candidate = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$binary.$extension;
                if (is_file($candidate) && is_executable($candidate)) {
                    return $candidate;
                }
            }
        }

        // Auto-detect the decoder bundled inside the core package. This works
        // both in the monorepo and after Composer installs the split package.
        $bundledName = PHP_OS_FAMILY === 'Windows' ? 'astcenc-windows-x64.exe' : 'astcenc-linux-x64';
        $packageRoot = dirname(__DIR__, 2);
        $candidates = [
            $packageRoot . '/bin/' . $bundledName,
            $packageRoot . '/bin/astcenc.exe',
            // Backward-compatible fallbacks for older monorepo layouts.
            dirname(__DIR__, 4) . '/bin/' . $bundledName,
            dirname(__DIR__, 4) . '/astcenc.exe',
        ];
        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if ($real === false || !is_file($real)) {
                continue;
            }
            if (PHP_OS_FAMILY !== 'Windows' && !is_executable($real)) {
                @chmod($real, 0755);
            }
            if (is_executable($real)) {
                return $real;
            }
        }

        return null;
    }
}
