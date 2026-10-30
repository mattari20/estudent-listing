<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SafeSourceHttpRetriever
{
    private const REDIRECT_STATUSES = [301, 302, 303, 307, 308];
    private const MAX_REDIRECTS = 5;

    public function get(string $url, int $timeout, int $connectTimeout, string $userAgent): Response
    {
        return $this->getWithMetadata($url, $timeout, $connectTimeout, $userAgent)['response'];
    }

    /**
     * Fetch through the same URL/DNS/redirect protections while exposing the
     * validated final URL for read-only qualification reports.
     *
     * @return array{response: Response, final_url: string}
     */
    public function getWithMetadata(
        string $url,
        int $timeout,
        int $connectTimeout,
        string $userAgent
    ): array {
        $guard = app(SourceUrlGuard::class);
        $currentUrl = $url;

        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            $validatedAddresses = $guard->validate($currentUrl);
            $currentParts = parse_url($currentUrl);
            $normalizedHost = trim((string) ($currentParts['host'] ?? ''), '[]');
            $requestOptions = [
                'allow_redirects' => false,
                'on_headers' => static function ($response): void {
                    $contentLength = (int) $response->getHeaderLine('Content-Length');
                    if ($contentLength > 5 * 1024 * 1024) {
                        throw new \RuntimeException('Source response exceeds the 5 MiB retrieval limit.');
                    }
                },
            ];
            $curlOptions = [];
            if (extension_loaded('curl') && defined('CURLOPT_MAXFILESIZE_LARGE')) {
                $curlOptions[CURLOPT_MAXFILESIZE_LARGE] = 5 * 1024 * 1024;
            }

            if (! filter_var($normalizedHost, FILTER_VALIDATE_IP)) {
                if (! extension_loaded('curl') || ! defined('CURLOPT_RESOLVE')) {
                    throw new \RuntimeException('Source DNS pinning is unavailable; request blocked.');
                }

                $port = (int) ($currentParts['port'] ?? (strtolower($currentParts['scheme'] ?? '') === 'https' ? 443 : 80));
                $validatedAddress = $validatedAddresses[0] ?? null;
                if (! is_string($validatedAddress) || $validatedAddress === '') {
                    throw new \RuntimeException('Source DNS validation returned no address; request blocked.');
                }
                $curlAddress = filter_var($validatedAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                    ? '['.$validatedAddress.']'
                    : $validatedAddress;
                $curlOptions[CURLOPT_RESOLVE] = [$normalizedHost.':'.$port.':'.$curlAddress];
            }
            if ($curlOptions !== []) {
                $requestOptions['curl'] = $curlOptions;
            }

            $response = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withOptions($requestOptions)
                ->withHeaders(['User-Agent' => $userAgent])
                ->get($currentUrl);

            if (! in_array($response->status(), self::REDIRECT_STATUSES, true)) {
                return ['response' => $response, 'final_url' => $currentUrl];
            }

            if ($redirects === self::MAX_REDIRECTS) {
                throw new \RuntimeException('Source exceeded the maximum of 5 redirects.');
            }

            $location = trim((string) $response->header('Location'));
            if ($location === '') {
                throw new \RuntimeException('Source returned a redirect without a Location header.');
            }

            $nextUrl = $this->resolveLocation($currentUrl, $location);
            $currentParts = parse_url($currentUrl);
            $nextParts = parse_url($nextUrl);

            if (
                strtolower($currentParts['scheme'] ?? '') === 'https'
                && strtolower($nextParts['scheme'] ?? '') !== 'https'
            ) {
                throw new \RuntimeException('HTTPS source redirects may not downgrade to HTTP.');
            }

            $currentUrl = $nextUrl;
        }

        throw new \RuntimeException('Source redirect handling ended unexpectedly.');
    }

    private function resolveLocation(string $baseUrl, string $location): string
    {
        if (preg_match('#^https?://#i', $location) === 1) {
            return $location;
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $location) === 1) {
            throw new \RuntimeException('Source redirect Location uses an unsupported URI scheme.');
        }
        $base = parse_url($baseUrl);
        $scheme = strtolower($base['scheme'] ?? '');
        $authority = $base['host'] ?? '';
        if (isset($base['port'])) {
            $authority .= ':'.$base['port'];
        }
        if (str_starts_with($location, '//')) {
            return $scheme.':'.$location;
        }
        if (str_starts_with($location, '#')) {
            throw new \RuntimeException('Fragment-only source redirects are not supported.');
        }
        $locationParts = parse_url($location);
        if ($locationParts === false) {
            throw new \RuntimeException('Source returned an invalid redirect Location.');
        }
        $path = $locationParts['path'] ?? '';
        if ($path === '') {
            $path = $base['path'] ?? '/';
        } elseif (! str_starts_with($path, '/')) {
            $basePath = $base['path'] ?? '/';
            $path = substr($basePath, 0, strrpos($basePath, '/') + 1).$path;
        }
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }
        $normalizedPath = '/'.implode('/', $segments);
        if (str_ends_with($path, '/') && ! str_ends_with($normalizedPath, '/')) {
            $normalizedPath .= '/';
        }
        $query = array_key_exists('query', $locationParts)
            ? '?'.$locationParts['query']
            : (array_key_exists('path', $locationParts) && $locationParts['path'] === ''
                ? (isset($base['query']) ? '?'.$base['query'] : '')
                : '');
        return $scheme.'://'.$authority.$normalizedPath.$query;
    }
}
