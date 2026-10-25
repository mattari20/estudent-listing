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

        // Reject alternate numeric host spellings that permissive URL clients
        // may normalize to an IPv4 address (decimal, hexadecimal, octal-like,
        // shortened, or leading-zero forms). Only canonical IP literals pass
        // through to the public-address classifier below.
        if (
            ! filter_var($normalizedHost, FILTER_VALIDATE_IP)
            && preg_match('/^(?:0x[0-9a-f]+|[0-9]+)(?:\.(?:0x[0-9a-f]+|[0-9]+))*$/i', $normalizedHost) === 1
        ) {
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

    private function isInCidr(string $packedAddress, string $cidr): bool
    {
        [$network, $prefixLength] = explode('/', $cidr, 2);
        $packedNetwork = @inet_pton($network);

        if (! is_string($packedNetwork) || strlen($packedNetwork) !== strlen($packedAddress)) {
            return false;
        }

        $prefixLength = (int) $prefixLength;
        $wholeBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($wholeBytes > 0 && substr($packedAddress, 0, $wholeBytes) !== substr($packedNetwork, 0, $wholeBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;

        return (ord($packedAddress[$wholeBytes]) & $mask) === (ord($packedNetwork[$wholeBytes]) & $mask);
    }

    private function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);

        if (! is_string($packed)) {
            return false;
        }

        // Multicast, class-E, shared, and benchmarking IPv4 ranges are not
        // valid public unicast destinations. PHP's reserved-range flags vary
        // by version and do not consistently reject all special-use ranges.
        if (strlen($packed) === 4) {
            $firstOctet = ord($packed[0]);
            if (
                $firstOctet >= 224
                || $this->isInCidr($packed, '100.64.0.0/10')
                || $this->isInCidr($packed, '198.18.0.0/15')
                || $this->isInCidr($packed, '192.0.0.0/24')
                || $this->isInCidr($packed, '192.88.99.0/24')
            ) {
                return false;
            }
        }

        // IPv6 multicast addresses begin with 0xff and must never be fetched
        // as an official-source destination.
        if (strlen($packed) === 16 && ord($packed[0]) === 0xff) {
            return false;
        }

        // These IPv6 prefixes are not safe direct public destinations for
        // source retrieval: documentation, NAT64 translation, 6to4 tunneling,
        // and deprecated site-local unicast.
        if (
            strlen($packed) === 16
            && (
                $this->isInCidr($packed, '2001:db8::/32')
                || $this->isInCidr($packed, '64:ff9b::/96')
                || $this->isInCidr($packed, '2002::/16')
                || $this->isInCidr($packed, 'fec0::/10')
                || $this->isInCidr($packed, '100::/64')
                || $this->isInCidr($packed, '2001::/23')
                || $this->isInCidr($packed, '2001:2::/48')
            )
        ) {
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
