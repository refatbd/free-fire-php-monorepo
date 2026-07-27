<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Media;
final readonly class AstcHeader { public function __construct(public int $blockWidth,public int $blockHeight,public int $blockDepth,public int $width,public int $height,public int $depth){} }
