<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Protocol;

use Refatbd\FreeFire\Exception\ConfigurationException;

final class ProtocolProfileRegistry
{
    /** @var array<string,ProtocolProfileInterface> */
    private array $profiles = [];

    /** @param iterable<ProtocolProfileInterface> $profiles */
    public function __construct(iterable $profiles)
    {
        foreach ($profiles as $profile) {
            $key = self::normalize($profile->obVersion());
            if (isset($this->profiles[$key])) {
                throw new ConfigurationException("Duplicate protocol profile {$key}.");
            }
            $this->profiles[$key] = $profile;
        }
        if ($this->profiles === []) {
            throw new ConfigurationException('At least one protocol profile must be registered.');
        }
    }

    public function get(string $version): ProtocolProfileInterface
    {
        $key = self::normalize($version);
        return $this->profiles[$key]
            ?? throw new ConfigurationException("Unsupported Free Fire protocol profile {$key}.");
    }

    /** @return list<string> */
    public function versions(): array
    {
        return array_keys($this->profiles);
    }

    private static function normalize(string $version): string
    {
        $value = strtoupper(trim($version));
        if (!preg_match('/^OB\d{1,4}$/', $value)) {
            throw new ConfigurationException('Protocol profile must use an OB number such as OB54.');
        }
        return $value;
    }
}
