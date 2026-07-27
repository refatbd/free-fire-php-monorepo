<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Media;

final class MediaVersion
{
    /** @param array<string,mixed> $player */
    public static function avatar(array $player, ?string $obVersion = null): string
    {
        $basic = self::basic($player);
        return self::hash([
            $obVersion ?? (string) self::get($player, ['mediaInfo', 'obVersion']),
            (string) ($basic['accountId'] ?? $basic['accountid'] ?? ''),
            (string) ($basic['headPic'] ?? $basic['headpic'] ?? ''),
        ]);
    }

    /** @param array<string,mixed> $player */
    public static function banner(array $player, ?string $obVersion = null): string
    {
        $basic = self::basic($player);
        $clan = self::clan($player);
        return self::hash([
            $obVersion ?? (string) self::get($player, ['mediaInfo', 'obVersion']),
            (string) ($basic['accountId'] ?? $basic['accountid'] ?? ''),
            (string) ($basic['bannerId'] ?? $basic['bannerid'] ?? ''),
            (string) ($basic['headPic'] ?? $basic['headpic'] ?? ''),
            (string) ($basic['nickname'] ?? ''),
            (string) ($basic['level'] ?? ''),
            (string) ($clan['clanName'] ?? $clan['clanname'] ?? ''),
        ]);
    }

    /** @param list<string> $values */
    private static function hash(array $values): string
    {
        return substr(hash('sha256', implode('|', $values)), 0, 16);
    }

    /** @param array<string,mixed> $player @return array<string,mixed> */
    private static function basic(array $player): array
    {
        return is_array($player['basicInfo'] ?? null)
            ? $player['basicInfo']
            : (is_array($player['basicinfo'] ?? null) ? $player['basicinfo'] : []);
    }

    /** @param array<string,mixed> $player @return array<string,mixed> */
    private static function clan(array $player): array
    {
        return is_array($player['clanBasicInfo'] ?? null)
            ? $player['clanBasicInfo']
            : (is_array($player['clanbasicinfo'] ?? null) ? $player['clanbasicinfo'] : []);
    }

    /** @param array<string,mixed> $data @param list<string> $path */
    private static function get(array $data, array $path): mixed
    {
        $value = $data;
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return '';
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
