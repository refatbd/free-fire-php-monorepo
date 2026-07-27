<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Protocol;

interface ProtocolProfileInterface
{
    public function obVersion(): string;
    public function userAgent(): string;
    public function unityVersion(): string;
    public function guestTokenUrl(): string;
    public function majorLoginUrl(): string;
    public function playerShowPath(): string;

    /** @return class-string */
    public function playerResponseMessageClass(): string;
    public function clientId(): string;
    public function clientSecret(): string;
    public function loginOpenIdType(): string;
    public function loginOriginPlatformType(): string;
    public function playerCallSignSource(): int;
    public function encryptionKey(): string;
    public function encryptionIv(): string;
    public function fallbackTokenTtl(): int;

    /** @return array<string,string> */
    public function binaryHeaders(): array;
}
