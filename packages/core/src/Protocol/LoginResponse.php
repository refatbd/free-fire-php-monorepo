<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Protocol;
final readonly class LoginResponse
{
    public function __construct(public string $token, public string $lockRegion, public string $serverUrl, public int $ttl) {}
}
