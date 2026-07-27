<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Protocol;

use Refatbd\FreeFire\Protocol\Profiles\Ob54ProtocolProfile;

final class BuiltInProtocolProfiles
{
    /** @return array<string,class-string<ProtocolProfileInterface>> */
    public static function classes(): array
    {
        return [
            'OB54' => Ob54ProtocolProfile::class,
        ];
    }

    /** @return list<ProtocolProfileInterface> */
    public static function instances(): array
    {
        return array_map(
            static fn (string $class): ProtocolProfileInterface => new $class(),
            array_values(self::classes()),
        );
    }

    public static function registry(): ProtocolProfileRegistry
    {
        return new ProtocolProfileRegistry(self::instances());
    }

    public static function get(string $version): ProtocolProfileInterface
    {
        return self::registry()->get($version);
    }
}
