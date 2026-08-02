<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$coreDocs = $root . '/packages/core/docs';
$checks = 0;

$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        fwrite(STDERR, "Documentation check failed: {$message}\n");
        exit(1);
    }

    ++$checks;
};

$required = [
    'ACCOUNT_CREDENTIAL_CAPTURE.md' => ['exact region pair', 'FREEFIRE_DEFAULT_UID', 'Rotation procedure'],
    'OB_PROTOCOL_CAPTURE.md' => ['AES-128-CBC', 'Protobuf', 'LIVE_PROTOCOL_VERIFICATION.md'],
    'TOKEN_GENERATION_FLOW.md' => ['access_token + open_id', 'MajorLogin', 'Generated automatically at runtime'],
    'LIVE_PROTOCOL_VERIFICATION.md' => ['Offline preflight', 'Test matrix', 'Sanitized evidence record'],
];

foreach ($required as $name => $needles) {
    $path = $coreDocs . '/' . $name;
    $assert(is_file($path), "missing {$name}");

    $contents = (string) file_get_contents($path);
    $assert(strlen($contents) >= 2000, "{$name} is unexpectedly short");

    foreach ($needles as $needle) {
        $assert(str_contains($contents, $needle), "{$name} does not contain required topic: {$needle}");
    }
}

$index = (string) file_get_contents($root . '/docs/CORE_MAINTENANCE_DOCS.md');
$readme = (string) file_get_contents($root . '/README.md');

foreach (array_keys($required) as $name) {
    $assert(str_contains($index, $name), "core maintenance index does not list {$name}");
    $assert(str_contains($readme, $name), "README does not list {$name}");
}

$envExample = (string) file_get_contents($root . '/apps/starter/.env.example');
foreach (['FREEFIRE_DEFAULT_UID=', 'FREEFIRE_DEFAULT_PASSWORD='] as $placeholder) {
    $assert(str_contains($envExample, $placeholder), ".env.example is missing {$placeholder}");
}

foreach (['FREEFIRE_DEFAULT_UID', 'FREEFIRE_DEFAULT_PASSWORD'] as $name) {
    $assert(
        preg_match('/^' . preg_quote($name, '/') . '=\s*$/m', $envExample) === 1,
        ".env.example must leave {$name} empty",
    );
}

$markdownFiles = glob($coreDocs . '/*.md') ?: [];
foreach ($markdownFiles as $path) {
    $contents = (string) file_get_contents($path);
    preg_match_all('/\[[^\]]+\]\(([^)]+\.md(?:#[^)]+)?)\)/', $contents, $matches);

    foreach ($matches[1] as $target) {
        $target = explode('#', $target, 2)[0];
        if (preg_match('#^(?:https?://|mailto:)#i', $target) === 1) {
            continue;
        }

        $resolved = dirname($path) . '/' . $target;
        $assert(is_file($resolved), basename($path) . " has a broken Markdown link: {$target}");
    }
}

echo "Documentation validation passed ({$checks} checks).\n";
