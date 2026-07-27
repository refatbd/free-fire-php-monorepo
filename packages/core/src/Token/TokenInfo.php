<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Token;
final readonly class TokenInfo
{
    public function __construct(public string $bearerToken, public string $region, public string $serverUrl, public int $expiresAt) {}
    public function isFresh(int $leeway=60): bool { return time()+$leeway < $this->expiresAt; }
}
