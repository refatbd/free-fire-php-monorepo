<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Media;
final readonly class RenderedMedia
{
    public function __construct(
        public string $data,
        public string $contentType = 'image/webp',
        public string $source = 'local-fallback',
        public bool $officialBanner = false,
        public bool $officialAvatar = false,
    ) {}
}
