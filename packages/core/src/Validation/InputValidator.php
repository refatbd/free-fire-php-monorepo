<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Validation;

use Refatbd\FreeFire\Exception\InvalidInputException;

final class InputValidator
{
    private const SIGNED_INT64_MAX = '9223372036854775807';

    public static function uid(string|int $uid): string
    {
        $value = trim((string) $uid);
        if (!preg_match('/^\d{5,20}$/', $value)) {
            throw new InvalidInputException('UID must contain 5 to 20 digits.');
        }
        $normalized = ltrim($value, '0') ?: '0';
        if (strlen($normalized) > strlen(self::SIGNED_INT64_MAX)
            || (strlen($normalized) === strlen(self::SIGNED_INT64_MAX)
                && strcmp($normalized, self::SIGNED_INT64_MAX) > 0)) {
            throw new InvalidInputException('UID exceeds the supported signed 64-bit range.');
        }

        return $value;
    }
}
