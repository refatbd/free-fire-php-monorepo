<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$monorepoRoot = dirname(__DIR__, 4);
$protocolRoot = $root.'/protocol';
$output = $protocolRoot.'/generated/php';
$protocEnv = getenv('PROTOC_BINARY');
if ($protocEnv !== false && $protocEnv !== '') {
    $protocCmd = [$protocEnv];
} elseif (file_exists($monorepoRoot.'/protoc/bin/protoc.exe')) {
    $protocCmd = [realpath($monorepoRoot.'/protoc/bin/protoc.exe')];
} else {
    $protocCmd = ['protoc'];
}
$sourceDirectories = glob($protocolRoot.'/ob*', GLOB_ONLYDIR) ?: [];
natsort($sourceDirectories);
$sourceDirectories = array_values($sourceDirectories);

if ($sourceDirectories === []) {
    fwrite(STDERR, "No versioned protocol directories were found.\n");
    exit(1);
}

$temporary = $protocolRoot.'/generated/.php-build-'.bin2hex(random_bytes(6));
if (!mkdir($temporary, 0775, true) && !is_dir($temporary)) {
    fwrite(STDERR, "Cannot create temporary Protobuf output directory.\n");
    exit(1);
}

$removeTree = static function (string $directory) use (&$removeTree): void {
    if (!is_dir($directory)) {
        return;
    }
    $items = scandir($directory);
    if (!is_array($items)) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $directory.DIRECTORY_SEPARATOR.$item;
        if (is_dir($path) && !is_link($path)) {
            $removeTree($path);
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
};

$copyTree = static function (string $source, string $destination) use (&$copyTree): void {
    if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
        throw new RuntimeException("Cannot create {$destination}.");
    }
    $items = scandir($source);
    if (!is_array($items)) {
        throw new RuntimeException("Cannot read {$source}.");
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $from = $source.DIRECTORY_SEPARATOR.$item;
        $to = $destination.DIRECTORY_SEPARATOR.$item;
        if (is_dir($from) && !is_link($from)) {
            $copyTree($from, $to);
        } elseif (!copy($from, $to)) {
            throw new RuntimeException("Cannot copy generated file {$from}.");
        }
    }
};

try {
    foreach ($sourceDirectories as $source) {
        $files = glob($source.'/*.proto') ?: [];
        if ($files === []) {
            throw new RuntimeException("No .proto files found in {$source}.");
        }

        $executable = str_replace('/', DIRECTORY_SEPARATOR, $protocCmd[0]);
        $command = array_merge(
            [$executable],
            array_slice($protocCmd, 1),
            [
                '--proto_path='.str_replace('/', DIRECTORY_SEPARATOR, $protocolRoot),
                '--php_out='.str_replace('/', DIRECTORY_SEPARATOR, $temporary),
            ],
            array_map(fn($f) => str_replace('/', DIRECTORY_SEPARATOR, $f), $files)
        );
        $specification = [0 => STDIN, 1 => STDOUT, 2 => STDERR];
        $process = proc_open($command, $specification, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start protoc. Set PROTOC_BINARY when it is not on PATH.');
        }
        $code = proc_close($process);
        if ($code !== 0) {
            throw new RuntimeException('Protocol generation failed for '.basename($source).'.');
        }
    }

    if (!is_dir($output) && !mkdir($output, 0775, true) && !is_dir($output)) {
        throw new RuntimeException('Cannot create final Protobuf output directory.');
    }
    $removeTree($output);
    $copyTree($temporary, $output);
    file_put_contents($output.'/.gitkeep', '');
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    $removeTree($temporary);
    @rmdir($temporary);
    exit(1);
}

$removeTree($temporary);
@rmdir($temporary);
echo 'Generated PHP Protobuf classes for '.count($sourceDirectories)." OB profile(s).\n";
