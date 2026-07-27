<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Region;
use Refatbd\FreeFire\Exception\InvalidInputException;

final class RegionRegistry
{
    public const SUPPORTED = ['IND','BR','US','SAC','NA','SG','RU','ID','TW','VN','TH','ME','PK','CIS','BD','EUROPE'];
    private const ALIASES = ['EU' => 'EUROPE'];

    public function normalize(string $region): string
    {
        $value = strtoupper(trim($region));
        $value = self::ALIASES[$value] ?? $value;
        if (!in_array($value, self::SUPPORTED, true)) {
            throw new InvalidInputException('Unsupported region: '.($value ?: 'missing'));
        }
        return $value;
    }

    /** @return list<string> */
    public function all(): array { return self::SUPPORTED; }
}
