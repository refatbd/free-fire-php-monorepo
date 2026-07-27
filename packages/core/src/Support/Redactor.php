<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Support;

final class Redactor
{
    /** @param array<string, mixed> $context */
    public static function context(array $context): array
    {
        $sensitive = ['password', 'token', 'access_token', 'authorization', 'client_secret', 'secureToken'];
        array_walk_recursive($context, static function (&$value, $key) use ($sensitive): void {
            if (in_array(strtolower((string) $key), array_map('strtolower', $sensitive), true)) {
                $value = '[REDACTED]';
            }
        });
        return $context;
    }
}
