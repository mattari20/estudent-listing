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
        if ($host === '' || (filter_var($host, FILTER_VALIDATE_IP) && ! $this->isPublicIp($host))) {
            throw new \InvalidArgumentException('Source host is not publicly routable.');
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);
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
