<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Protocol;

use Refatbd\FreeFire\Protocol\Wire\WireEncoder;

final class LoginRequestCodec
{
    public function encode(
        string $openId,
        string $loginToken,
        string $openIdType = '4',
        string $originPlatformType = '4',
    ): string {
        return WireEncoder::string(22, $openId)
            .WireEncoder::string(23, $openIdType)
            .WireEncoder::string(29, $loginToken)
            .WireEncoder::string(99, $originPlatformType);
    }
}
