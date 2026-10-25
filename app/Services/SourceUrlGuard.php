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
        // PHP may retain square brackets around IPv6 literals in parse_url().
        // Strip them before IP classification so ::1 and unique-local IPv6
        // addresses cannot bypass the non-public address check.
        $normalizedHost = trim($host, '[]');

        if ($normalizedHost === '') {
            throw new \InvalidArgumentException('Source host is not publicly routable.');
        }

        if (filter_var($normalizedHost, FILTER_VALIDATE_IP)) {
            if (! $this->isPublicIp($normalizedHost)) {
                throw new \InvalidArgumentException('Source host is not publicly routable.');
            }

            // Literal IPs need no DNS lookup; validate the address directly.
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
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
