<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Protocol;
use Refatbd\FreeFire\Protocol\Wire\WireEncoder;
final class PlayerRequestCodec
{
    public function encode(string $uid, int $callSignSource = 7): string
    {
        return WireEncoder::uint(1, $uid).WireEncoder::uint(2, $callSignSource);
    }
}
