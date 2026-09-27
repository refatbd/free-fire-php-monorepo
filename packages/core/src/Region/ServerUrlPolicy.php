<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Region;

use Refatbd\FreeFire\Exception\ProtocolException;

final class ServerUrlPolicy
{
    private const PLAYER_HOSTS = [
        'clientbp.ppmainecoonghj.com',
        'clientbp.ggpolarbear.com',
        'client.ind.freefiremobile.com',
        'client.us.freefiremobile.com',
    ];

    /**
     * Validates a player server returned by Free Fire login against observed
     * HTTPS hosts; reject credentials, paths, queries and non-standard ports.
     */
    public function normalize(string $serverUrl): string
    {
        $serverUrl = trim($serverUrl);
        if ($serverUrl === '') {
            throw new ProtocolException('Login response did not provide a server URL.');
        }
        $parts = parse_url($serverUrl);
        if (!is_array($parts)) {
            throw new ProtocolException('Login response contained an invalid server URL.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        $port = isset($parts['port']) ? (int) $parts['port'] : null;

        if ($scheme !== 'https' || !in_array($host, self::PLAYER_HOSTS, true)) {
            throw new ProtocolException('Only HTTPS Free Fire server URLs are accepted.');
        }
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new ProtocolException('Free Fire server URL contains unsupported components.');
        }
        if ($port !== null && $port !== 443) {
            throw new ProtocolException('Free Fire server URL uses a non-standard port.');
        }
        $path = (string) ($parts['path'] ?? '');
        if ($path !== '' && $path !== '/') {
            throw new ProtocolException('Free Fire server URL contains an unsupported path.');
        }

        return 'https://'.$host;
    }
}
