<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Protocol\Wire;

use Refatbd\FreeFire\Exception\ProtocolException;

final class WireDecoder
{
    /** @return list<array{field:int,wire:int,value:int|string}> */
    public static function fields(string $bytes): array
    {
        $offset = 0;
        $length = strlen($bytes);
        $out = [];

        while ($offset < $length) {
            $key = self::readVarint($bytes, $offset);
            if (!is_int($key)) {
                throw new ProtocolException('Protobuf field key exceeds the supported range.');
            }
            $field = $key >> 3;
            $wire = $key & 7;
            if ($field < 1) {
                throw new ProtocolException('Invalid protobuf field number.');
            }

            $value = match ($wire) {
                0 => self::readVarint($bytes, $offset),
                1 => self::readFixed($bytes, $offset, 8),
                2 => self::readLengthDelimited($bytes, $offset),
                5 => self::readFixed($bytes, $offset, 4),
                default => throw new ProtocolException("Unsupported protobuf wire type {$wire}."),
            };
            $out[] = ['field' => $field, 'wire' => $wire, 'value' => $value];
        }

        return $out;
    }

    private static function readVarint(string $bytes, int &$offset): int|string
    {
        $digits = [];
        $length = strlen($bytes);
        for ($count = 0; $count < 10; $count++) {
            if ($offset >= $length) {
                throw new ProtocolException('Malformed protobuf varint.');
            }
            $byte = ord($bytes[$offset++]);
            $digits[] = $byte & 0x7F;
            if (($byte & 0x80) === 0) {
                if ($count === 9 && ($byte & 0x7F) > 1) {
                    throw new ProtocolException('Protobuf varint exceeds the uint64 range.');
                }
                $decimal = '0';
                for ($index = count($digits) - 1; $index >= 0; $index--) {
                    $decimal = self::multiplyAddDecimal($decimal, 128, $digits[$index]);
                }
                if (strlen($decimal) < strlen((string) PHP_INT_MAX)
                    || (strlen($decimal) === strlen((string) PHP_INT_MAX) && strcmp($decimal, (string) PHP_INT_MAX) <= 0)) {
                    return (int) $decimal;
                }
                return $decimal;
            }
        }

        throw new ProtocolException('Malformed protobuf varint.');
    }

    private static function readLengthDelimited(string $bytes, int &$offset): string
    {
        $length = self::readVarint($bytes, $offset);
        if (!is_int($length) || $length < 0 || $offset + $length > strlen($bytes)) {
            throw new ProtocolException('Truncated protobuf field.');
        }
        $value = substr($bytes, $offset, $length);
        $offset += $length;
        return $value;
    }

    private static function readFixed(string $bytes, int &$offset, int $size): string
    {
        if ($offset + $size > strlen($bytes)) {
            throw new ProtocolException('Truncated fixed protobuf field.');
        }
        $value = substr($bytes, $offset, $size);
        $offset += $size;
        return $value;
    }

    private static function multiplyAddDecimal(string $value, int $multiplier, int $addend): string
    {
        $carry = $addend;
        $output = '';
        for ($index = strlen($value) - 1; $index >= 0; $index--) {
            $current = ((ord($value[$index]) - 48) * $multiplier) + $carry;
            $output = (string) ($current % 10).$output;
            $carry = intdiv($current, 10);
        }
        while ($carry > 0) {
            $output = (string) ($carry % 10).$output;
            $carry = intdiv($carry, 10);
        }

        return ltrim($output, '0') ?: '0';
    }
}
