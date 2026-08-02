<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Credentials;

/** Maps public regions to the distinct upstream guest-account pools. */
final class CredentialGroupResolver
{
    public static function forRegion(string $region): string
    {
        $region = strtoupper(trim($region));

        return match ($region) {
            'IND' => 'IND',
            'BR', 'US', 'SAC', 'NA', 'EUROPE', 'EU' => 'AMERICAS',
            'VN', 'ID', 'TH', 'TW' => $region,
            default => 'GLOBAL',
        };
    }

    private function __construct()
    {
    }
}
