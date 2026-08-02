<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Credentials;

final class EnvironmentCredentialProvider implements CredentialProviderInterface
{
    public function __construct(private readonly string $prefix = 'FREEFIRE') {}

    public function forRegion(string $region): ?Credential
    {
        $regionKey = preg_replace('/[^A-Z0-9]/', '_', strtoupper(trim($region))) ?: 'GLOBAL';
        $groupKey = CredentialGroupResolver::forRegion($region);

        foreach (array_values(array_unique([$regionKey, $groupKey, 'DEFAULT'])) as $key) {
            $uid = getenv("{$this->prefix}_{$key}_UID");
            $password = getenv("{$this->prefix}_{$key}_PASSWORD");
            if (is_string($uid) && is_string($password) && trim($uid) !== '' && trim($password) !== '') {
                return new Credential(trim($uid), trim($password));
            }
        }

        return null;
    }
}
