<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Crypto;
use Refatbd\FreeFire\Exception\ProtocolException;
final class AesCbcCipher
{
    public function encrypt(string $plaintext, string $key, string $iv): string
    {
        if (strlen($key) !== 16 || strlen($iv) !== 16) {
            throw new ProtocolException('AES-128-CBC requires a 16-byte key and IV.');
        }
        $padding = 16 - (strlen($plaintext) % 16);
        $padded = $plaintext . str_repeat(chr($padding), $padding);
        $encrypted = openssl_encrypt($padded, 'aes-128-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        if ($encrypted === false) {
            throw new ProtocolException('OpenSSL could not encrypt the protocol payload.');
        }
        return $encrypted;
    }
}
