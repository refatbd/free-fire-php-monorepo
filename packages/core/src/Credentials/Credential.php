<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Credentials;

final readonly class Credential
{
    public function __construct(public string $uid, public string $password)
    {
        if (!preg_match('/^\d{5,20}$/', $uid)) {
            throw new \InvalidArgumentException('Credential UID is invalid.');
        }
        if ($password === '') {
            throw new \InvalidArgumentException('Credential password cannot be empty.');
        }
    }

    public function asFormBody(): string
    {
        return http_build_query(['uid' => $this->uid, 'password' => $this->password], '', '&', PHP_QUERY_RFC3986);
    }
}
