<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Credentials;

final class ChainCredentialProvider implements CredentialProviderInterface
{
    /** @param list<CredentialProviderInterface> $providers */
    public function __construct(private readonly array $providers) {}

    public function forRegion(string $region): ?Credential
    {
        foreach ($this->providers as $provider) {
            if (($credential = $provider->forRegion($region)) !== null) {
                return $credential;
            }
        }
        return null;
    }
}
