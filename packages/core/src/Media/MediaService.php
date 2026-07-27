<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Media;

use Refatbd\FreeFire\Cache\CacheStoreInterface;

final class MediaService
{
    public function __construct(
        private readonly PlayerMediaRendererInterface $renderer,
        private readonly CacheStoreInterface $cache,
        private readonly string $obVersion = 'OB54',
        private readonly int $ttl = 300,
    ) {}

    /** @param array<string,mixed> $player */
    public function avatar(array $player, int $size = 512): RenderedMedia
    {
        $size = max(128, min($size, 1024));
        $identity = [
            MediaVersion::avatar($player, $this->obVersion),
            $size,
        ];

        return $this->remember('avatar', $identity, fn () => $this->renderer->avatar($player, $size));
    }

    /** @param array<string,mixed> $player */
    public function banner(array $player, int $width = 1000, int $height = 250, bool $raw = false): RenderedMedia
    {
        $width = max(800, min($width, 1600));
        $height = max(200, min($height, 400));
        $identity = [
            MediaVersion::banner($player, $this->obVersion),
            $width,
            $height,
            $raw ? 'raw' : 'composited',
        ];

        return $this->remember('banner', $identity, fn () => $this->renderer->banner($player, $width, $height, $raw));
    }

    /** @param list<int|string> $identity */
    private function remember(string $type, array $identity, callable $render): RenderedMedia
    {
        $version = preg_replace('/[^A-Za-z0-9_.-]/', '_', $this->obVersion) ?: 'unknown';
        $key = 'freefire:media:'.$version.':'.$type.':'.hash(
            'sha256',
            json_encode($identity, JSON_THROW_ON_ERROR),
        );
        $cached = $this->cache->get($key);
        if ($cached instanceof RenderedMedia) {
            return $cached;
        }

        $result = $render();
        if (!$result instanceof RenderedMedia) {
            throw new \UnexpectedValueException('Media renderer returned an invalid value.');
        }
        $this->cache->put($key, $result, max(1, $this->ttl));
        return $result;
    }
}
