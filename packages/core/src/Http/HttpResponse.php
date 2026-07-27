<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Http;
final readonly class HttpResponse
{
    /** @param array<string,list<string>> $headers */
    public function __construct(public int $status, public array $headers, public string $body) {}
    public function json(): array
    {
        $decoded=json_decode($this->body,true,512,JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) throw new \JsonException('Expected a JSON object.');
        return $decoded;
    }
}
