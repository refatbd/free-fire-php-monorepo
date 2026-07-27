<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Protocol\Profiles;

use Refatbd\FreeFire\Exception\ConfigurationException;
use Refatbd\FreeFire\Protocol\ProtocolProfileInterface;

final class Ob54ProtocolProfile implements ProtocolProfileInterface
{
    public function obVersion(): string { return 'OB54'; }
    public function userAgent(): string { return 'Dalvik/2.1.0 (Linux; U; Android 13; CPH2095 Build/RKQ1.211119.001)'; }
    public function unityVersion(): string { return '2018.4.11f1'; }
    public function guestTokenUrl(): string { return 'https://ffmconnect.live.gop.garenanow.com/oauth/guest/token/grant'; }
    public function majorLoginUrl(): string { return 'https://loginbp.ggblueshark.com/MajorLogin'; }
    public function playerShowPath(): string { return '/GetPlayerPersonalShow'; }
    public function playerResponseMessageClass(): string
    {
        return 'Refatbd\\FreeFire\\Protocol\\Generated\\Ob54\\AccountPersonalShow\\AccountPersonalShowInfo';
    }
    public function clientId(): string { return '100067'; }
    public function clientSecret(): string { return '2ee44819e9b4598845141067b281621874d0d5d7af9d8f7e00c1e54715b7d1e3'; }
    public function loginOpenIdType(): string { return '4'; }
    public function loginOriginPlatformType(): string { return '4'; }
    public function playerCallSignSource(): int { return 7; }
    public function encryptionKey(): string { return $this->decode('WWcmdGMlREV1aDYlWmNeOA==', 'key'); }
    public function encryptionIv(): string { return $this->decode('Nm95WkRyMjJFM3ljaGpNJQ==', 'IV'); }
    public function fallbackTokenTtl(): int { return 25_200; }

    public function binaryHeaders(): array
    {
        return [
            'User-Agent' => $this->userAgent(),
            'Connection' => 'Keep-Alive',
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/octet-stream',
            'Expect' => '100-continue',
            'X-Unity-Version' => $this->unityVersion(),
            'X-GA' => 'v1 1',
            'ReleaseVersion' => $this->obVersion(),
        ];
    }

    private function decode(string $value, string $label): string
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false || strlen($decoded) !== 16) {
            throw new ConfigurationException("Invalid OB54 encryption {$label}.");
        }
        return $decoded;
    }
}
