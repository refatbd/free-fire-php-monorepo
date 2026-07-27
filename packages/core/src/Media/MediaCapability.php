<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Media;

final readonly class MediaCapability implements \JsonSerializable
{
    public function __construct(
        public bool $astcDecoder,
        public bool $gd,
        public bool $imagick,
        public bool $webp,
        public string $decoderDriver,
        public string $rendererDriver,
    ) {}

    public static function detect(?AstcDecoderInterface $decoder = null): self
    {
        $decoder ??= new AstcencProcessDecoder();
        $gd = extension_loaded('gd');
        $imagick = extension_loaded('imagick');
        $gdWebp = $gd && function_exists('imagewebp');

        // The current production renderer is GD. Imagick is reported for
        // diagnostics but does not count as an active rendering driver yet.
        return new self(
            $decoder->available(),
            $gd,
            $imagick,
            $gdWebp,
            $decoder->name(),
            $gdWebp ? 'gd-webp' : 'unavailable',
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'astcDecoder' => $this->astcDecoder,
            'gd' => $this->gd,
            'imagick' => $this->imagick,
            'webp' => $this->webp,
            'decoderDriver' => $this->decoderDriver,
            'rendererDriver' => $this->rendererDriver,
            'fullOfficialMedia' => $this->astcDecoder && $this->rendererDriver === 'gd-webp',
        ];
    }
}
