<?php

namespace App\Services;

class SourceUrlGuard
{
    public function validate(string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid source URL.');
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Only HTTP and HTTPS sources are allowed.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new \InvalidArgumentException('Source URLs must not contain embedded credentials.');
        }

        $host = $parts['host'] ?? '';
        $normalizedHost = trim($host, '[]');

        if ($normalizedHost === '') {
            throw new \InvalidArgumentException('Source host is not publicly routable.');
        }

        if (filter_var($normalizedHost, FILTER_VALIDATE_IP)) {
            if (! $this->isPublicIp($normalizedHost)) {
                throw new \InvalidArgumentException('Source host is not publicly routable.');
            }

            return;
        }

        $records = dns_get_record($normalizedHost, DNS_A | DNS_AAAA);
        foreach ($records ?: [] as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip && ! $this->isPublicIp($ip)) {
                throw new \InvalidArgumentException('Source host resolves to a non-public address.');
            }
        }
    }

    private function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);

        if (! is_string($packed)) {
            return false;
        }

        // Multicast and class-E IPv4 ranges are not valid unicast destinations
        // for official-source retrieval. FILTER_FLAG_NO_RES_RANGE alone does
        // not consistently reject these ranges across PHP versions.
        if (strlen($packed) === 4) {
            $firstOctet = ord($packed[0]);
            if ($firstOctet >= 224) {
                return false;
            }
        }

        // IPv6 multicast addresses begin with 0xff and must never be fetched
        // as an official-source destination.
        if (strlen($packed) === 16 && ord($packed[0]) === 0xff) {
            return false;
        }

        // IPv4-mapped IPv6 literals can encode a private IPv4 destination,
        // e.g. ::ffff:127.0.0.1 or its hexadecimal equivalent ::ffff:7f00:1.
        // Classify the embedded IPv4 address with the same public-range policy.
        if (
            strlen($packed) === 16
            && substr($packed, 0, 10) === str_repeat("\0", 10)
            && substr($packed, 10, 2) === "\xff\xff"
        ) {
            $embeddedIpv4 = inet_ntop(substr($packed, 12, 4));
            if (! is_string($embeddedIpv4) || ! $this->isPublicIp($embeddedIpv4)) {
                return false;
            }
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
