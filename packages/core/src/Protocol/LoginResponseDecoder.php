<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Protocol;
use Refatbd\FreeFire\Exception\ProtocolException;
use Refatbd\FreeFire\Protocol\Wire\WireDecoder;
final class LoginResponseDecoder
{
    public function decode(string $bytes): LoginResponse
    {
        $map=[]; foreach (WireDecoder::fields($bytes) as $f) $map[$f['field']]=$f['value'];
        $token=is_string($map[8]??null)?$map[8]:'';
        $server=is_string($map[10]??null)?$map[10]:'';
        if ($token==='' || $server==='') throw new ProtocolException('MajorLogin response did not contain token/server URL.');
        return new LoginResponse($token, is_string($map[2]??null)?$map[2]:'', $server, is_int($map[9]??null)?$map[9]:0);
    }
}
