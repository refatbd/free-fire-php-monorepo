<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Media;

use Refatbd\FreeFire\Exception\InvalidInputException;

final class OfficialAssetPolicy
{
    public const MAX_BYTES = 8_388_608;
    public const KNOWN_BASES = [
        'https://dl-tata.freefireind.in/live/ABHotUpdates/IconCDN/android',
        'https://dl.tata.freefiremobile.com/live/ABHotUpdates/IconCDN/android',
    ];

    /** @var list<string> */
    private array $bases;

    /** @param list<string>|null $requestedBases */
    public function __construct(?array $requestedBases = null)
    {
        $requestedBases ??= [self::KNOWN_BASES[0]];
        $accepted = [];
        foreach ($requestedBases as $candidate) {
            $normalized = rtrim(trim($candidate), '/');
            if (in_array($normalized, self::KNOWN_BASES, true)) {
                $accepted[] = $normalized;
            }
        }
        $this->bases = array_values(array_unique($accepted ?: [self::KNOWN_BASES[0]]));
    }

    public function itemId(string|int $id): string
    {
        $value = trim((string) $id);
        if (!preg_match('/^\d{6,14}$/', $value)) {
            throw new InvalidInputException('Invalid official item ID.');
        }
        return $value;
    }

    /** @return list<string> */
    public function urls(string|int $id): array
    {
        $safeId = $this->itemId($id);
        return array_map(
            static fn (string $base): string => $base."/{$safeId}_rgb.astc",
            $this->bases,
        );
    }

    public function isAllowedUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https') {
            return false;
        }
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return false;
        }
        if (isset($parts['port']) && (int) $parts['port'] !== 443) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        foreach (self::KNOWN_BASES as $base) {
            $baseHost = strtolower((string) parse_url($base, PHP_URL_HOST));
            $basePath = rtrim((string) parse_url($base, PHP_URL_PATH), '/');
            if ($host === $baseHost && preg_match('#^'.preg_quote($basePath, '#').'/\d{6,14}_rgb\.astc$#D', $path)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function bases(): array
    {
        return $this->bases;
    }
}
