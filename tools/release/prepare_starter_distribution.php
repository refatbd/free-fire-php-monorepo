<?php
declare(strict_types=1);

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php tools/release/prepare_starter_distribution.php <starter-directory>\n");
    exit(2);
}

$directory = rtrim($argv[1], DIRECTORY_SEPARATOR);
$manifestFile = $directory.DIRECTORY_SEPARATOR.'composer.json';
$lockFile = $directory.DIRECTORY_SEPARATOR.'composer.lock';

if (!is_file($manifestFile)) {
    fwrite(STDERR, "Starter composer.json not found: {$manifestFile}\n");
    exit(2);
}

try {
    $manifest = json_decode((string) file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    fwrite(STDERR, 'Invalid starter composer.json: '.$e->getMessage()."\n");
    exit(1);
}

if (!is_array($manifest)) {
    fwrite(STDERR, "Starter composer.json did not decode to an object.\n");
    exit(1);
}

// The canonical monorepo uses local path repositories for development. The
// public subtree split must resolve released packages from Packagist instead.
unset($manifest['repositories'], $manifest['minimum-stability']);
$manifest['prefer-stable'] = true;
$manifest['require']['refatbd/laravel-free-fire'] = '^1.0';

$json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
if (file_put_contents($manifestFile, $json) === false) {
    fwrite(STDERR, "Could not write starter composer.json.\n");
    exit(1);
}

// The monorepo lock records local path packages and must never leak into the
// public starter distribution.
if (is_file($lockFile) && !unlink($lockFile)) {
    fwrite(STDERR, "Could not remove starter composer.lock.\n");
    exit(1);
}

echo "Prepared public starter distribution manifest.\n";
