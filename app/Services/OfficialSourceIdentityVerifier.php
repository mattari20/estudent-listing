<?php

namespace App\Services;

use App\Models\Source;

/**
 * Verifies that a source is active and belongs to an explicitly configured
 * official-source profile before its observations can drive discovery.
 */
class OfficialSourceIdentityVerifier
{
    /**
     * @param array<string, mixed> $profiles
     * @return array{verified:bool,profile_key:?string,blockers:array<string>,reason:string}
     */
    public function verify(Source $source, ?array $profiles = null): array
    {
        $profiles ??= config('institute_agent.profiles', []);
        $blockers = [];

        if ($source->status !== 'active') {
            $blockers[] = 'source_not_active';
        }
        if (! in_array(strtolower((string) $source->source_type), ['official', 'institution'], true)) {
            $blockers[] = 'source_type_not_authoritative';
        }
        if (! is_string($source->url) || ! $this->validHttpUrl($source->url)) {
            $blockers[] = 'source_url_invalid';
        }

        $matchedProfile = null;
        if ($blockers === []) {
            foreach ($profiles as $profileKey => $definition) {
                $configuredUrl = $definition['source']['url'] ?? null;
                if (! is_string($configuredUrl) || ! $this->validHttpUrl($configuredUrl)) {
                    continue;
                }

                if ($this->normalizeUrl($configuredUrl) === $this->normalizeUrl($source->url)) {
                    $matchedProfile = (string) $profileKey;
                    break;
                }
            }
        }

        if ($matchedProfile === null) {
            $blockers[] = 'source_not_bound_to_verified_profile';
        }

        $blockers = array_values(array_unique($blockers));
        $verified = $blockers === [];

        return [
            'verified' => $verified,
            'profile_key' => $verified ? $matchedProfile : null,
            'blockers' => $blockers,
            'reason' => $verified
                ? 'Active authoritative source exactly matches a configured official-source profile.'
                : 'Source identity is not sufficiently verified to drive canonical discovery updates.',
        ];
    }

    private function validHttpUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            && is_string(parse_url($url, PHP_URL_HOST))
            && parse_url($url, PHP_URL_HOST) !== '';
    }

    private function normalizeUrl(string $url): string
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = rtrim((string) ($parts['path'] ?? ''), '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$port.$path.$query;
    }
}
