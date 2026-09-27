<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Protocol;
use Refatbd\FreeFire\Exception\ProtocolException;
use Refatbd\FreeFire\Exception\UnrecognizedLoginResponseException;
use Refatbd\FreeFire\Protocol\Wire\WireDecoder;
final class LoginResponseDecoder
{
    public function decode(string $bytes, string $version = 'OB54'): LoginResponse
    {
        $prefix = $version === 'OB55' ? 64 : 0;
        if (strlen($bytes) <= $prefix || strlen($bytes) > 4_194_304) {
            throw new ProtocolException('MajorLogin response has invalid framing.');
        }
        $fields = WireDecoder::fields(substr($bytes, $prefix));
        $map = [];
        foreach ($fields as $field) {
            $map[$field['field']] = $field;
        }
        $stringField = static fn (int $number): string =>
            isset($map[$number]) && $map[$number]['wire'] === 2 && is_string($map[$number]['value'])
                ? $map[$number]['value'] : '';
        $token = $stringField(8);
        $region = $stringField(2);
        $server = $stringField(10);
        if ($token === '' || $region === '' || $server === '') {
            if ($version === 'OB55' && isset($map[13])) {
                throw new UnrecognizedLoginResponseException('MajorLogin returned no usable token; the OB55 response meaning is unverified.');
            }
            throw new ProtocolException('MajorLogin response did not contain a usable token, region and server URL.');
        }
        $ttl = isset($map[9]) && $map[9]['wire'] === 0 && is_int($map[9]['value'])
            ? $map[9]['value'] : 0;
        return new LoginResponse($token, $region, $server, $ttl);
    }
}
