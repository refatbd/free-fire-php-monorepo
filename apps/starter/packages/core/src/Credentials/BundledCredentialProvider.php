<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Credentials;

/**
 * Bundled service credentials required by the upstream guest-login workflow.
 * Keep this class server-side. Never expose credentials through API responses or logs.
 */
final class BundledCredentialProvider implements CredentialProviderInterface
{
    /** @var array<string, array{0:string,1:string}> */
    private const ACCOUNTS = [
        'IND' => ['3692279677', '473AFFEF67F708CBB0962A958BB2809DA0843EA41BDB70D738FD9527EA04B27B'],
        'AMERICAS' => ['3692292847', 'FC22F6812C850FF7D8DB8C5474A106B6FE22CB10C0A6673837216A32675E5649'],
        'VN' => ['3686689562', 'AD9C4A2B51A749481913F72A36F68A9F231520E9AC29B244DB47A64FD7353A12'],
        'ID' => ['3692307512', '4AA06E1DB3F998ABDBDA74578D26B0C84700EC5C079751E7C8F1626048DDBCAE'],
        'TH' => ['3692333198', '0ED64C5A89E09B8BE538829B0304FE5F5F7EA3BBE645A341C73ECA49143D2211'],
        'TW' => ['3692312456', '1A062FD700DA8F826AF84A37EE2B62121B79516AF71666949C72FFF42D1C554A'],
        'GLOBAL' => ['3692265171', 'A2A5E3C252A35B2BB30698BD1469A759417A68A069CF6980ED959EB01D352E28'],
    ];

    public function forRegion(string $region): ?Credential
    {
        $group = CredentialGroupResolver::forRegion($region);
        [$uid, $password] = self::ACCOUNTS[$group];
        return new Credential($uid, $password);
    }
}
