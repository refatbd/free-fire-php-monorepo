<?php
declare(strict_types=1);

namespace Refatbd\FreeFire;

use Psr\Log\LoggerInterface;
use Refatbd\FreeFire\Cache\FileCacheStore;
use Refatbd\FreeFire\Credentials\BundledCredentialProvider;
use Refatbd\FreeFire\Credentials\ChainCredentialProvider;
use Refatbd\FreeFire\Credentials\EnvironmentCredentialProvider;
use Refatbd\FreeFire\Http\StreamHttpTransport;
use Refatbd\FreeFire\Media\AstcencProcessDecoder;
use Refatbd\FreeFire\Media\FontResolver;
use Refatbd\FreeFire\Media\GdPlayerMediaRenderer;
use Refatbd\FreeFire\Media\MediaService;
use Refatbd\FreeFire\Media\OfficialAssetDownloader;
use Refatbd\FreeFire\Media\OfficialAssetPolicy;
use Refatbd\FreeFire\Media\OfficialImageLoader;
use Refatbd\FreeFire\Player\GoogleProtobufPlayerResponseDecoder;
use Refatbd\FreeFire\Protocol\BuiltInProtocolProfiles;
use Refatbd\FreeFire\Protocol\ProtocolProfileInterface;
use Refatbd\FreeFire\Token\TokenManager;

final class FreeFireFactory
{
    public static function make(
        string $cacheDirectory,
        ?LoggerInterface $logger = null,
        ?ProtocolProfileInterface $profile = null,
    ): FreeFireClient {
        $profile ??= BuiltInProtocolProfiles::get('OB54');
        $cache = new FileCacheStore($cacheDirectory);
        $http = new StreamHttpTransport();
        $credentials = new ChainCredentialProvider([
            new EnvironmentCredentialProvider(),
            new BundledCredentialProvider(),
        ]);
        $tokens = new TokenManager($profile, $credentials, $http, $cache, logger: $logger);

        return new FreeFireClient(
            $profile,
            $tokens,
            $http,
            new GoogleProtobufPlayerResponseDecoder($profile->playerResponseMessageClass()),
            $cache,
        );
    }

    /** @param list<string>|null $officialAssetBases */
    public static function makeMedia(
        string $cacheDirectory,
        string $temporaryDirectory,
        string $astcencBinary = 'astcenc',
        ?string $fontPath = null,
        int $quality = 92,
        ?array $officialAssetBases = null,
        ?ProtocolProfileInterface $profile = null,
    ): MediaService {
        $profile ??= BuiltInProtocolProfiles::get('OB54');
        $cache = new FileCacheStore($cacheDirectory);
        $decoder = new AstcencProcessDecoder($astcencBinary);
        $policy = new OfficialAssetPolicy($officialAssetBases);
        $downloader = new OfficialAssetDownloader(
            policy: $policy,
            cache: $cache,
            cacheNamespace: $profile->obVersion(),
        );
        $loader = new OfficialImageLoader($downloader, $decoder, $temporaryDirectory);
        $renderer = new GdPlayerMediaRenderer(
            $loader,
            new FontResolver(array_values(array_filter([$fontPath]))),
            $quality,
        );

        return new MediaService($renderer, $cache, $profile->obVersion());
    }
}
