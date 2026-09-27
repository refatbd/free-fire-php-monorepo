<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Credentials;

use Refatbd\FreeFire\Exception\ConfigurationException;

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
            if ($uid === false && $password === false) {
                continue;
            }
            if (!is_string($uid) || !is_string($password) || trim($uid) === '' || trim($password) === '') {
                throw new ConfigurationException("Complete credentials are required for {$key}.");
            }
            try {
                return new Credential(trim($uid), trim($password));
            } catch (\InvalidArgumentException $error) {
                throw new ConfigurationException("Invalid credentials for {$key}.", 0, $error);
            }
        }

        return null;
    }
}
