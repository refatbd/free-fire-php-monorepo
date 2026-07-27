<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Media;

use Refatbd\FreeFire\Exception\MediaException;

final class AstcHeaderParser
{
    public const MAGIC = "\x13\xAB\xA1\x5C";
    public const HEADER_SIZE = 16;
    public const MAX_DIMENSION = 4096;

    public function parse(string $data): AstcHeader
    {
        if (strlen($data) < self::HEADER_SIZE) {
            throw new MediaException('ASTC asset is shorter than its header.');
        }
        if (substr($data, 0, 4) !== self::MAGIC) {
            throw new MediaException('Asset does not contain a valid ASTC header.');
        }

        $header = new AstcHeader(
            ord($data[4]),
            ord($data[5]),
            ord($data[6]),
            $this->u24(substr($data, 7, 3)),
            $this->u24(substr($data, 10, 3)),
            $this->u24(substr($data, 13, 3)),
        );

        if ($header->blockWidth < 1 || $header->blockWidth > 12
            || $header->blockHeight < 1 || $header->blockHeight > 12) {
            throw new MediaException('Unsupported ASTC block size.');
        }
        if ($header->blockDepth !== 1 || $header->depth !== 1) {
            throw new MediaException('Only 2D ASTC textures are supported.');
        }
        if ($header->width < 1 || $header->width > self::MAX_DIMENSION
            || $header->height < 1 || $header->height > self::MAX_DIMENSION) {
            throw new MediaException('ASTC dimensions exceed safety limits.');
        }

        return $header;
    }

    public function validateAsset(string $data): AstcHeader
    {
        $header = $this->parse($data);
        $blocksX = intdiv($header->width + $header->blockWidth - 1, $header->blockWidth);
        $blocksY = intdiv($header->height + $header->blockHeight - 1, $header->blockHeight);
        $minimumBytes = self::HEADER_SIZE + ($blocksX * $blocksY * 16);

        if (strlen($data) < $minimumBytes) {
            throw new MediaException('ASTC asset is truncated for its declared dimensions.');
        }

        return $header;
    }

    private function u24(string $value): int
    {
        return ord($value[0]) | (ord($value[1]) << 8) | (ord($value[2]) << 16);
    }
}
