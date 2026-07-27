<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Http;

use Refatbd\FreeFire\Exception\TransportException;

final class StreamHttpTransport implements HttpTransportInterface
{
    public function send(HttpRequest $request): HttpResponse
    {
        $userAgent = 'Dalvik/2.1.0 (Linux; U; Android 13; CPH2095 Build/RKQ1.211119.001)';
        $headerLines = [];
        foreach ($request->headers as $name => $value) {
            if (strtolower($name) === 'connection') {
                $value = 'close';
            }
            if (strtolower($name) === 'user-agent') {
                $userAgent = $value;
            }
            $headerLines[] = "{$name}: {$value}";
        }

        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($request->method),
                'header' => implode("\r\n", $headerLines),
                'user_agent' => $userAgent,
                'content' => $request->body,
                'timeout' => $request->timeout,
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
                'protocol_version' => 1.1,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $handle = @fopen($request->url, 'rb', false, $context);
        $rawHeaders = $http_response_header ?? [];
        [$status, $headers] = $this->parseHeaders($rawHeaders);

        if (!is_resource($handle)) {
            throw new TransportException(
                $status > 0
                    ? "HTTP request returned status {$status} without a readable response body."
                    : 'HTTP request failed before a response was received.'
            );
        }

        try {
            $declaredLength = $this->declaredContentLength($headers);
            if ($declaredLength !== null && $declaredLength > $request->maxResponseBytes) {
                throw new TransportException('HTTP response exceeds the configured size limit.');
            }

            $body = stream_get_contents($handle, $request->maxResponseBytes + 1);
            if ($body === false) {
                throw new TransportException('Could not read the HTTP response body.');
            }
            if (strlen($body) > $request->maxResponseBytes) {
                throw new TransportException('HTTP response exceeds the configured size limit.');
            }
        } finally {
            fclose($handle);
        }

        $encoding = strtolower(trim($headers['content-encoding'][0] ?? ''));
        if ($encoding === 'gzip') {
            if (!function_exists('gzdecode')) {
                throw new TransportException('Received a gzip response but gzip decoding is unavailable.');
            }
            $decoded = @gzdecode($body);
            if ($decoded === false) {
                throw new TransportException('Could not decode the gzip response.');
            }
            if (strlen($decoded) > $request->maxResponseBytes) {
                throw new TransportException('Decoded HTTP response exceeds the configured size limit.');
            }
            $body = $decoded;
        }

        return new HttpResponse($status, $headers, $body);
    }

    /**
     * PHP stream wrappers may expose multiple response blocks, for example
     * "100 Continue" followed by the real response. The last HTTP block wins.
     *
     * @param list<string> $rawHeaders
     * @return array{0:int,1:array<string,list<string>>}
     */
    private function parseHeaders(array $rawHeaders): array
    {
        $status = 0;
        $headers = [];

        foreach ($rawHeaders as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})(?:\s|$)#i', $line, $match)) {
                $status = (int) $match[1];
                $headers = [];
                continue;
            }

            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = array_map('trim', explode(':', $line, 2));
            if ($name !== '') {
                $headers[strtolower($name)][] = $value;
            }
        }

        return [$status, $headers];
    }

    /** @param array<string,list<string>> $headers */
    private function declaredContentLength(array $headers): ?int
    {
        $value = $headers['content-length'][0] ?? null;
        if ($value === null || !preg_match('/^\d+$/', trim($value))) {
            return null;
        }
        return (int) trim($value);
    }
}
