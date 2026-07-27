<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Cache;

use Refatbd\FreeFire\Media\RenderedMedia;
use Refatbd\FreeFire\Token\TokenInfo;

final class FileCacheStore implements CacheStoreInterface
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Cannot create cache directory.');
        }
        @chmod($directory, 0700);
    }

    public function get(string $key): mixed
    {
        $file = $this->file($key);
        $handle = @fopen($file, 'rb');
        if (!is_resource($handle)) {
            return null;
        }

        try {
            if (!flock($handle, LOCK_SH)) {
                return null;
            }
            $raw = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $data = @unserialize($raw, [
            'allowed_classes' => [TokenInfo::class, RenderedMedia::class],
        ]);
        if (!is_array($data) || !is_int($data['expires'] ?? null) || $data['expires'] <= time()) {
            @unlink($file);
            return null;
        }

        $value = $data['value'] ?? null;
        if (!$this->isAllowedValue($value)) {
            @unlink($file);
            return null;
        }

        return $value;
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $file = $this->file($key);
        $tmp = $file.'.'.bin2hex(random_bytes(5)).'.tmp';
        $data = serialize([
            'expires' => time() + max(1, $ttlSeconds),
            'value' => $value,
        ]);

        $handle = @fopen($tmp, 'xb');
        if (!is_resource($handle)) {
            throw new \RuntimeException('Cannot create temporary cache item.');
        }

        try {
            if (!flock($handle, LOCK_EX) || !$this->writeAll($handle, $data)) {
                throw new \RuntimeException('Cannot write temporary cache item.');
            }
            fflush($handle);
            if (function_exists('fsync')) {
                @fsync($handle);
            }
            flock($handle, LOCK_UN);
        } catch (\Throwable $e) {
            fclose($handle);
            @unlink($tmp);
            throw $e;
        }
        fclose($handle);
        @chmod($tmp, 0600);

        // POSIX rename is atomic and replaces the destination. Some Windows
        // filesystems refuse to replace an existing file, so use a locked
        // in-place write as the portable fallback; readers also take a lock.
        if (!@rename($tmp, $file)) {
            $destination = @fopen($file, 'c+b');
            if (!is_resource($destination)) {
                @unlink($tmp);
                throw new \RuntimeException('Cannot replace cache item.');
            }
            try {
                if (!flock($destination, LOCK_EX)
                    || !ftruncate($destination, 0)
                    || fseek($destination, 0) !== 0
                    || !$this->writeAll($destination, $data)) {
                    throw new \RuntimeException('Cannot replace cache item.');
                }
                fflush($destination);
                if (function_exists('fsync')) {
                    @fsync($destination);
                }
                flock($destination, LOCK_UN);
            } finally {
                fclose($destination);
                @unlink($tmp);
            }
        }
        @chmod($file, 0600);
    }

    public function forget(string $key): void
    {
        @unlink($this->file($key));
    }

    public function acquireLock(string $key, int $ttlSeconds): ?string
    {
        $file = $this->file('lock:'.$key);
        $owner = bin2hex(random_bytes(16));
        if (is_file($file) && filemtime($file) !== false && filemtime($file) + $ttlSeconds < time()) {
            @unlink($file);
        }
        $handle = @fopen($file, 'x');
        if ($handle === false) {
            return null;
        }
        fwrite($handle, $owner);
        fclose($handle);
        @chmod($file, 0600);

        return $owner;
    }

    public function releaseLock(string $key, string $owner): void
    {
        $file = $this->file('lock:'.$key);
        $storedOwner = is_file($file) ? trim((string) @file_get_contents($file)) : '';
        if ($storedOwner !== '' && hash_equals($storedOwner, $owner)) {
            @unlink($file);
        }
    }

    /** @param resource $handle */
    private function writeAll($handle, string $data): bool
    {
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $result = fwrite($handle, substr($data, $written));
            if ($result === false || $result === 0) {
                return false;
            }
            $written += $result;
        }
        return true;
    }

    private function isAllowedValue(mixed $value): bool
    {
        if ($value === null || is_scalar($value)) {
            return true;
        }
        if ($value instanceof TokenInfo || $value instanceof RenderedMedia) {
            return true;
        }
        if (!is_array($value)) {
            return false;
        }
        foreach ($value as $key => $item) {
            if ((!is_int($key) && !is_string($key)) || !$this->isAllowedValue($item)) {
                return false;
            }
        }

        return true;
    }

    private function file(string $key): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .hash('sha256', $key)
            .'.cache';
    }
}
