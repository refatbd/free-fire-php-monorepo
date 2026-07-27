<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Media;
interface PlayerMediaRendererInterface
{
    /** @param array<string,mixed> $player */
    public function avatar(array $player, int $size = 512): RenderedMedia;
    /** @param array<string,mixed> $player */
    public function banner(array $player, int $width = 1000, int $height = 250, bool $raw = false): RenderedMedia;
    public function available(): bool;
}
