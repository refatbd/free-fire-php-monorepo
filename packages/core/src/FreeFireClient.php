<?php
declare(strict_types=1);

namespace Refatbd\FreeFire;

use Refatbd\FreeFire\Cache\CacheStoreInterface;
use Refatbd\FreeFire\Crypto\AesCbcCipher;
use Refatbd\FreeFire\Exception\InvalidInputException;
use Refatbd\FreeFire\Exception\TransportException;
use Refatbd\FreeFire\Http\HttpRequest;
use Refatbd\FreeFire\Http\HttpTransportInterface;
use Refatbd\FreeFire\Player\PlayerResponseDecoderInterface;
use Refatbd\FreeFire\Protocol\PlayerRequestCodec;
use Refatbd\FreeFire\Protocol\ProtocolProfileInterface;
use Refatbd\FreeFire\Region\RegionRegistry;
use Refatbd\FreeFire\Region\ServerUrlPolicy;
use Refatbd\FreeFire\Token\TokenManager;
use Refatbd\FreeFire\Validation\InputValidator;

final class FreeFireClient
{
    public function __construct(
        private readonly ProtocolProfileInterface $profile,
        private readonly TokenManager $tokens,
        private readonly HttpTransportInterface $http,
        private readonly PlayerResponseDecoderInterface $decoder,
        private readonly CacheStoreInterface $cache,
        private readonly RegionRegistry $regions = new RegionRegistry(),
        private readonly AesCbcCipher $cipher = new AesCbcCipher(),
        private readonly PlayerRequestCodec $requestCodec = new PlayerRequestCodec(),
        private readonly ServerUrlPolicy $serverUrls = new ServerUrlPolicy(),
        private readonly int $playerCacheTtl = 300,
    ) {}

    /** @return array<string,mixed> */
    public function player(string|int $uid, ?string $region = null): array
    {
        $uid = InputValidator::uid($uid);

        if ($region !== null && trim($region) !== '' && strtoupper(trim($region)) !== 'AUTO') {
            try {
                return $this->fetchPlayerSingleRegion($uid, $region);
            } catch (\Throwable $e) {
                // If specific region gateway login fails, fallback to multi-gateway scanning
            }
        }

        $candidateRegions = ['BD', 'SG', 'IND', 'BR', 'VN', 'ID', 'TH', 'TW'];

        // 1. Check cache
        foreach ($candidateRegions as $r) {
            $cacheKey = "freefire:{$this->profile->obVersion()}:player:{$r}:{$uid}";
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached) && !empty($cached['basicInfo']['nickname'])) {
                return $cached;
            }
        }

        // 2. Scan gateway regions
        foreach ($candidateRegions as $r) {
            try {
                $data = $this->fetchPlayerSingleRegion($uid, $r);
                if (!empty($data['basicInfo']['nickname'])) {
                    return $data;
                }
            } catch (\Throwable $e) {
                // Continue scanning candidate region
            }
        }

        throw new InvalidInputException("Player account not found for UID '{$uid}'.");
    }

    /** @return array<string,mixed> */
    private function fetchPlayerSingleRegion(string $uid, string $region): array
    {
        $region = $this->regions->normalize($region);
        $cacheKey = "freefire:{$this->profile->obVersion()}:player:{$region}:{$uid}";

        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $token = $this->tokens->get($region);
        $server = $this->serverUrls->normalize($token->serverUrl);
        $plain = $this->requestCodec->encode($uid, $this->profile->playerCallSignSource());
        $encrypted = $this->cipher->encrypt(
            $plain,
            $this->profile->encryptionKey(),
            $this->profile->encryptionIv(),
        );
        $headers = $this->profile->binaryHeaders();
        $headers['Authorization'] = $token->bearerToken;

        $response = $this->http->send(new HttpRequest(
            'POST',
            $server.$this->profile->playerShowPath(),
            $headers,
            $encrypted,
            10,
            4_194_304,
        ));
        if ($response->status < 200 || $response->status >= 300) {
            throw new TransportException("Player endpoint returned HTTP {$response->status}.");
        }

        $data = $this->decoder->decode($response->body);
        if (empty($data['basicInfo']['nickname'])) {
            throw new InvalidInputException("No player found for UID '{$uid}' in region {$region}.");
        }

        $data['mediaInfo'] = [
            'policy' => 'official-free-fire-cdn-only-with-local-fallback',
            'obVersion' => $this->profile->obVersion(),
        ];
        $this->cache->put($cacheKey, $data, $this->playerCacheTtl);

        return $data;
    }
}
