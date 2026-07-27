<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Media;

use Refatbd\FreeFire\Cache\CacheStoreInterface;
use Refatbd\FreeFire\Exception\MediaException;

final class OfficialAssetDownloader
{
    public function __construct(
        private readonly OfficialAssetPolicy $policy = new OfficialAssetPolicy(),
        private readonly AstcHeaderParser $parser = new AstcHeaderParser(),
        private readonly float $timeout = 5.0,
        private readonly ?CacheStoreInterface $cache = null,
        private readonly int $cacheTtl = 21_600,
        private readonly string $cacheNamespace = 'default',
    ) {}

    /** @return array{data:string,url:string} */
    public function download(string|int $itemId): array
    {
        $safeId = $this->policy->itemId($itemId);
        $namespace = preg_replace('/[^A-Za-z0-9_.-]/', '_', $this->cacheNamespace) ?: 'default';
        $baseHash = substr(hash('sha256', implode('|', $this->policy->bases())), 0, 16);
        $cacheKey = 'freefire:official-asset:'.$namespace.':'.$baseHash.':'.$safeId;
        $cached = $this->cache?->get($cacheKey);
        if (is_array($cached) && is_string($cached['data'] ?? null) && is_string($cached['url'] ?? null)) {
            try {
                $this->parser->validateAsset($cached['data']);
                if ($this->policy->isAllowedUrl($cached['url'])) {
                    return ['data' => $cached['data'], 'url' => $cached['url']];
                }
            } catch (\Throwable) {
                $this->cache?->forget($cacheKey);
            }
        }

        $lastError = null;
        foreach ($this->policy->urls($safeId) as $url) {
            if (!$this->policy->isAllowedUrl($url)) {
                continue;
            }
            try {
                $data = $this->downloadOne($url);
                $this->parser->validateAsset($data);
                $result = ['data' => $data, 'url' => $url];
                $this->cache?->put($cacheKey, $result, max(60, $this->cacheTtl));
                return $result;
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }
        throw new MediaException('Official Free Fire asset is unavailable.', 0, $lastError);
    }

    private function downloadOne(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/octet-stream,*/*;q=0.8\r\nUser-Agent: FreeFireInfoSite/official-media\r\nConnection: close\r\nAccept-Encoding: identity",
                'user_agent' => 'FreeFireInfoSite/official-media',
                'timeout' => $this->timeout,
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
                'protocol_version' => 1.1,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $handle = @fopen($url, 'rb', false, $context);
        $headers = $http_response_header ?? [];
        $status = 0;
        foreach ($headers as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $match)) {
                $status = (int) $match[1];
            }
            if (stripos($line, 'Content-Length:') === 0) {
                $length = (int) trim(substr($line, 15));
                if ($length > OfficialAssetPolicy::MAX_BYTES) {
                    if (is_resource($handle)) {
                        fclose($handle);
                    }
                    throw new MediaException('Official asset exceeds the size limit.');
                }
            }
        }
        if (!is_resource($handle) || $status < 200 || $status >= 300) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new MediaException("Official asset request returned HTTP {$status}.");
        }

        $data = stream_get_contents($handle, OfficialAssetPolicy::MAX_BYTES + 1);
        fclose($handle);
        if ($data === false) {
            throw new MediaException('Could not read official asset response.');
        }
        if (strlen($data) > OfficialAssetPolicy::MAX_BYTES) {
            throw new MediaException('Official asset exceeds the size limit.');
        }
        return $data;
    }
}
