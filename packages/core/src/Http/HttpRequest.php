<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Http;

final readonly class HttpRequest
{
    /** @param array<string,string> $headers */
    public function __construct(
        public string $method,
        public string $url,
        public array $headers = [],
        public string $body = '',
        public float $timeout = 10.0,
        public int $maxResponseBytes = 10_485_760,
    ) {
        if ($this->timeout <= 0) {
            throw new \InvalidArgumentException('HTTP timeout must be greater than zero.');
        }
        if ($this->maxResponseBytes < 1) {
            throw new \InvalidArgumentException('Maximum response bytes must be greater than zero.');
        }
    }
}
