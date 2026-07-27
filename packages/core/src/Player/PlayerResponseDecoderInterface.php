<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Player;
interface PlayerResponseDecoderInterface { /** @return array<string,mixed> */ public function decode(string $bytes): array; }
