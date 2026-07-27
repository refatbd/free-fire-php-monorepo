<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Credentials;

final class EnvironmentCredentialProvider implements CredentialProviderInterface
{
    public function __construct(private readonly string $prefix = 'FREEFIRE') {}

    public function forRegion(string $region): ?Credential
    {
        $key = preg_replace('/[^A-Z0-9]/', '_', strtoupper($region)) ?: 'GLOBAL';
        $uid = getenv("{$this->prefix}_{$key}_UID") ?: getenv("{$this->prefix}_DEFAULT_UID");
        $password = getenv("{$this->prefix}_{$key}_PASSWORD") ?: getenv("{$this->prefix}_DEFAULT_PASSWORD");
        return is_string($uid) && is_string($password) && $uid !== '' && $password !== ''
            ? new Credential($uid, $password)
            : null;
    }
}
