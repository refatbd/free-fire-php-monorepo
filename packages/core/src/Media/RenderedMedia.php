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

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'data' => base64_encode($this->data),
            'contentType' => $this->contentType,
            'source' => $this->source,
            'officialBanner' => $this->officialBanner,
            'officialAvatar' => $this->officialAvatar,
            '_b64' => true,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function __unserialize(array $payload): void
    {
        $this->data = !empty($payload['_b64'])
            ? (base64_decode((string) ($payload['data'] ?? ''), true) ?: '')
            : (string) ($payload['data'] ?? '');
        $this->contentType = (string) ($payload['contentType'] ?? 'image/webp');
        $this->source = (string) ($payload['source'] ?? 'local-fallback');
        $this->officialBanner = (bool) ($payload['officialBanner'] ?? false);
        $this->officialAvatar = (bool) ($payload['officialAvatar'] ?? false);
    }
}
