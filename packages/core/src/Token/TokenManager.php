<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Token;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Refatbd\FreeFire\Cache\CacheStoreInterface;
use Refatbd\FreeFire\Credentials\CredentialProviderInterface;
use Refatbd\FreeFire\Crypto\AesCbcCipher;
use Refatbd\FreeFire\Exception\ConfigurationException;
use Refatbd\FreeFire\Exception\ProtocolException;
use Refatbd\FreeFire\Exception\TransportException;
use Refatbd\FreeFire\Http\HttpRequest;
use Refatbd\FreeFire\Http\HttpTransportInterface;
use Refatbd\FreeFire\Protocol\LoginRequestCodec;
use Refatbd\FreeFire\Protocol\LoginResponseDecoder;
use Refatbd\FreeFire\Protocol\ProtocolProfileInterface;
use Refatbd\FreeFire\Region\RegionRegistry;

final class TokenManager
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly ProtocolProfileInterface $profile,
        private readonly CredentialProviderInterface $credentials,
        private readonly HttpTransportInterface $http,
        private readonly CacheStoreInterface $cache,
        private readonly RegionRegistry $regions = new RegionRegistry(),
        private readonly AesCbcCipher $cipher = new AesCbcCipher(),
        private readonly LoginRequestCodec $loginCodec = new LoginRequestCodec(),
        private readonly LoginResponseDecoder $loginDecoder = new LoginResponseDecoder(),
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function get(string $region, bool $forceRefresh = false): TokenInfo
    {
        $region = $this->regions->normalize($region);
        $key = "freefire:{$this->profile->obVersion()}:token:{$region}";

        if (!$forceRefresh && ($cached = $this->cache->get($key)) instanceof TokenInfo && $cached->isFresh()) {
            return $cached;
        }

        $owner = $this->cache->acquireLock($key, 30);
        if ($owner === null) {
            for ($attempt = 0; $attempt < 10; $attempt++) {
                usleep(150_000);
                $cached = $this->cache->get($key);
                if ($cached instanceof TokenInfo && $cached->isFresh(0)) {
                    return $cached;
                }
            }
            throw new TransportException('Token refresh is already running and did not complete in time.');
        }

        try {
            if (!$forceRefresh && ($cached = $this->cache->get($key)) instanceof TokenInfo && $cached->isFresh()) {
                return $cached;
            }

            $credential = $this->credentials->forRegion($region)
                ?? throw new ConfigurationException("No credential is configured for {$region}.");
            $guestBody = $credential->asFormBody().'&'.http_build_query([
                'response_type' => 'token',
                'client_type' => '2',
                'client_secret' => $this->profile->clientSecret(),
                'client_id' => $this->profile->clientId(),
            ], '', '&', PHP_QUERY_RFC3986);

            $guest = $this->http->send(new HttpRequest(
                'POST',
                $this->profile->guestTokenUrl(),
                [
                    'User-Agent' => $this->profile->userAgent(),
                    'Connection' => 'Keep-Alive',
                    'Accept-Encoding' => 'gzip',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                $guestBody,
                10,
                1_048_576,
            ));
            if ($guest->status < 200 || $guest->status >= 300) {
                throw new TransportException("Guest token endpoint returned HTTP {$guest->status}.");
            }

            try {
                $json = $guest->json();
            } catch (\JsonException $e) {
                throw new ProtocolException('Guest token response was not valid JSON.', 0, $e);
            }
            $access = (string) ($json['access_token'] ?? '');
            $openId = (string) ($json['open_id'] ?? '');
            if ($access === '' || $openId === '') {
                throw new ProtocolException('Guest token response is missing access_token/open_id.');
            }

            $plain = $this->loginCodec->encode(
                $openId,
                $access,
                $this->profile->loginOpenIdType(),
                $this->profile->loginOriginPlatformType(),
            );
            $encrypted = $this->cipher->encrypt(
                $plain,
                $this->profile->encryptionKey(),
                $this->profile->encryptionIv(),
            );
            $login = $this->http->send(new HttpRequest(
                'POST',
                $this->profile->majorLoginUrl(),
                $this->profile->binaryHeaders(),
                $encrypted,
                10,
                4_194_304,
            ));
            if ($login->status < 200 || $login->status >= 300) {
                throw new TransportException("MajorLogin returned HTTP {$login->status}.");
            }

            $decoded = $this->loginDecoder->decode($login->body);
            $reportedTtl = $decoded->ttl > 0
                ? min($decoded->ttl, $this->profile->fallbackTokenTtl())
                : $this->profile->fallbackTokenTtl();
            $effectiveTtl = max(300, $reportedTtl);
            $info = new TokenInfo(
                'Bearer '.$decoded->token,
                $decoded->lockRegion,
                $decoded->serverUrl,
                time() + $effectiveTtl,
            );
            $this->cache->put($key, $info, $effectiveTtl);
            $this->logger->info('Free Fire token refreshed.', [
                'region' => $region,
                'protocol' => $this->profile->obVersion(),
                'expiresAt' => $info->expiresAt,
            ]);

            return $info;
        } catch (\Throwable $e) {
            $this->logger->warning('Free Fire token refresh failed.', [
                'region' => $region,
                'protocol' => $this->profile->obVersion(),
                'exception' => $e::class,
            ]);
            throw $e;
        } finally {
            $this->cache->releaseLock($key, $owner);
        }
    }
}
