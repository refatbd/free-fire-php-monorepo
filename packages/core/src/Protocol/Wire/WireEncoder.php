<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Protocol\Wire;

use Refatbd\FreeFire\Exception\ProtocolException;

final class WireEncoder
{
    private const UINT64_MAX = '18446744073709551615';

    public static function varint(int|string $value): string
    {
        $decimal = self::normalizeUnsignedDecimal($value);
        $bytes = '';

        do {
            [$decimal, $remainder] = self::divideDecimal($decimal, 128);
            $bytes .= chr($remainder | ($decimal !== '0' ? 0x80 : 0));
        } while ($decimal !== '0');

        return $bytes;
    }

    public static function key(int $field, int $wireType): string
    {
        if ($field < 1 || $field > 536_870_911) {
            throw new ProtocolException('Invalid protobuf field number.');
        }
        if (!in_array($wireType, [0, 1, 2, 3, 4, 5], true)) {
            throw new ProtocolException('Invalid protobuf wire type.');
        }

        return self::varint(($field << 3) | $wireType);
    }

    public static function uint(int $field, int|string $value): string
    {
        return self::key($field, 0).self::varint($value);
    }

    public static function string(int $field, string $value): string
    {
        return self::key($field, 2).self::varint(strlen($value)).$value;
    }

    private static function normalizeUnsignedDecimal(int|string $value): string
    {
        if (is_int($value)) {
            if ($value < 0) {
                throw new ProtocolException('Unsigned varint cannot be negative.');
            }
            return (string) $value;
        }

        $value = trim($value);
        if ($value === '' || !ctype_digit($value)) {
            throw new ProtocolException('Unsigned varint must be a decimal integer.');
        }
        $value = ltrim($value, '0');
        $value = $value === '' ? '0' : $value;
        if (strlen($value) > strlen(self::UINT64_MAX)
            || (strlen($value) === strlen(self::UINT64_MAX) && strcmp($value, self::UINT64_MAX) > 0)) {
            throw new ProtocolException('Unsigned varint exceeds the uint64 range.');
        }

        return $value;
    }

    /** @return array{0:string,1:int} */
    private static function divideDecimal(string $value, int $divisor): array
    {
        $quotient = '';
        $remainder = 0;
        $length = strlen($value);
        for ($index = 0; $index < $length; $index++) {
            $current = ($remainder * 10) + (ord($value[$index]) - 48);
            $digit = intdiv($current, $divisor);
            $remainder = $current % $divisor;
            if ($quotient !== '' || $digit !== 0) {
                $quotient .= (string) $digit;
            }
        }

        return [$quotient === '' ? '0' : $quotient, $remainder];
    }
}
