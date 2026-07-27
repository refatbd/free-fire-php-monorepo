<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Credentials;
interface CredentialProviderInterface { public function forRegion(string $region): ?Credential; }
