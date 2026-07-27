<?php
declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php tools/release/verify_split.php <core|laravel|starter> <distribution-directory>\n");
    exit(2);
}

[$script, $kind, $directory] = $argv;
$directory = rtrim($directory, DIRECTORY_SEPARATOR);
if (!is_dir($directory)) {
    fwrite(STDERR, "Distribution directory does not exist: {$directory}\n");
    exit(2);
}

/** @var list<string> $errors */
$errors = [];

$requiredByKind = [
    'core' => [
        'composer.json',
        'README.md',
        'LICENSE',
        'src/FreeFireClient.php',
        'src/FreeFireFactory.php',
        'protocol/ob54/AccountPersonalShow.proto',
        'protocol/ob54/LegacyLogin.proto',
        'protocol/ob54/PlayerRequest.proto',
        'tools/protobuf/generate.php',
        'bin/astcenc-linux-x64',
        'bin/astcenc-windows-x64.exe',
        'bin/LICENSE.astcenc.txt',
        'THIRD_PARTY_NOTICES.md',
    ],
    'laravel' => [
        'composer.json',
        'README.md',
        'LICENSE',
        'src/FreeFireServiceProvider.php',
        'src/Facades/FreeFire.php',
        'config/freefire.php',
        'routes/api.php',
    ],
    'starter' => [
        'composer.json',
        'README.md',
        'LICENSE',
        'artisan',
        'bootstrap/app.php',
        'app/Http/Controllers/PlayerPageController.php',
        'resources/views/welcome.blade.php',
        'resources/views/player.blade.php',
        'resources/views/docs.blade.php',
        'routes/web.php',
    ],
];

$expectedNames = [
    'core' => 'refatbd/free-fire-php',
    'laravel' => 'refatbd/laravel-free-fire',
    'starter' => 'refatbd/free-fire-info-starter',
];

if (!isset($requiredByKind[$kind])) {
    fwrite(STDERR, "Unknown distribution kind: {$kind}\n");
    exit(2);
}

foreach ($requiredByKind[$kind] as $relative) {
    if (!is_file($directory.DIRECTORY_SEPARATOR.$relative)) {
        $errors[] = "Missing required file: {$relative}";
    }
}

$manifestFile = $directory.DIRECTORY_SEPARATOR.'composer.json';
$manifest = null;
if (is_file($manifestFile)) {
    try {
        $manifest = json_decode((string) file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        $errors[] = 'composer.json is invalid JSON: '.$e->getMessage();
    }
}
if (is_array($manifest) && ($manifest['name'] ?? null) !== $expectedNames[$kind]) {
    $errors[] = sprintf(
        'Unexpected Composer package name. Expected %s, got %s.',
        $expectedNames[$kind],
        var_export($manifest['name'] ?? null, true),
    );
}

if ($kind === 'starter' && is_array($manifest)) {
    if (($manifest['repositories'] ?? []) !== []) {
        $errors[] = 'Starter distribution must not contain monorepo path repositories.';
    }
    $laravelConstraint = $manifest['require']['refatbd/laravel-free-fire'] ?? null;
    if (!is_string($laravelConstraint) || str_contains($laravelConstraint, '@dev') || str_contains($laravelConstraint, '*')) {
        $errors[] = 'Starter distribution must require a stable laravel-free-fire version constraint.';
    }
    if (is_file($directory.'/composer.lock')) {
        $errors[] = 'Starter distribution must not contain the monorepo path-based composer.lock.';
    }
}

$readme = is_file($directory.'/README.md') ? (string) file_get_contents($directory.'/README.md') : '';
if (!str_contains($readme, 'Generated distribution repository')) {
    $errors[] = 'README.md is missing the generated-distribution warning.';
}

foreach (['packages/core', 'packages/laravel', 'apps/starter', '.github/workflows/split-repositories.yml'] as $forbidden) {
    if (file_exists($directory.DIRECTORY_SEPARATOR.$forbidden)) {
        $errors[] = "Monorepo-only path leaked into distribution: {$forbidden}";
    }
}

if ($kind === 'core') {
    $generatedRoot = $directory.'/protocol/generated/php';
    $generated = [];
    if (is_dir($generatedRoot)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($generatedRoot, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $generated[] = $file->getPathname();
            }
        }
    }
    if ($generated === []) {
        $errors[] = 'Core distribution contains no generated Protobuf PHP classes.';
    }
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

echo sprintf("Verified %s distribution at %s.\n", $kind, $directory);
