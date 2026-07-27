<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Region;

use Refatbd\FreeFire\Exception\ProtocolException;

final class ServerUrlPolicy
{
    /**
     * Validates a server base returned by the trusted Free Fire login response.
     * It deliberately rejects local/private literal IPs, credentials, fragments,
     * queries, non-HTTPS schemes and non-standard ports.
     */
    public function normalize(string $serverUrl): string
    {
        $serverUrl = trim($serverUrl);
        if ($serverUrl === '') {
            throw new ProtocolException('Login response did not provide a server URL.');
        }
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $serverUrl)) {
            $serverUrl = 'https://'.$serverUrl;
        }

        $parts = parse_url($serverUrl);
        if (!is_array($parts)) {
            throw new ProtocolException('Login response contained an invalid server URL.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        $port = isset($parts['port']) ? (int) $parts['port'] : null;

        if ($scheme !== 'https' || $host === '') {
            throw new ProtocolException('Only HTTPS Free Fire server URLs are accepted.');
        }
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new ProtocolException('Free Fire server URL contains unsupported components.');
        }
        if ($port !== null && $port !== 443) {
            throw new ProtocolException('Free Fire server URL uses a non-standard port.');
        }
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            throw new ProtocolException('Local server URLs are not accepted.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $public = filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
            if ($public === false) {
                throw new ProtocolException('Private or reserved server IP addresses are not accepted.');
            }
        } elseif (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new ProtocolException('Free Fire server URL contains an invalid hostname.');
        }

        $path = (string) ($parts['path'] ?? '');
        $path = $path === '/' ? '' : rtrim($path, '/');

        return 'https://'.$host.$path;
    }
}
