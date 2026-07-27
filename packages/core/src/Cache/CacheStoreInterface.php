<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Cache;
interface CacheStoreInterface
{
    public function get(string $key): mixed;
    public function put(string $key, mixed $value, int $ttlSeconds): void;
    public function forget(string $key): void;
    public function acquireLock(string $key, int $ttlSeconds): ?string;
    public function releaseLock(string $key, string $owner): void;
}
