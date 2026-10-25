<?php

namespace Tests\Unit;

use App\Models\Source;
use App\Services\OfficialSourceIdentityVerifier;
use PHPUnit\Framework\TestCase;

class OfficialSourceIdentityVerifierTest extends TestCase
{
    public function test_active_authoritative_source_bound_to_configured_profile_is_verified(): void
    {
        $source = new Source([
            'source_type' => 'official',
            'name' => 'Example University Official Site',
            'url' => 'https://university.example.edu/programs/',
            'status' => 'active',
        ]);

        $result = (new OfficialSourceIdentityVerifier())->verify($source, [
            'example-university' => [
                'source' => ['url' => 'https://university.example.edu/programs'],
            ],
        ]);

        $this->assertTrue($result['verified']);
        $this->assertSame('example-university', $result['profile_key']);
        $this->assertSame([], $result['blockers']);
    }

    public function test_lookalike_host_does_not_match_configured_official_profile(): void
    {
        $source = new Source([
            'source_type' => 'official',
            'name' => 'Lookalike Source',
            'url' => 'https://university.example.edu.attacker.test/programs',
            'status' => 'active',
        ]);

        $result = (new OfficialSourceIdentityVerifier())->verify($source, [
            'example-university' => [
                'source' => ['url' => 'https://university.example.edu/programs'],
            ],
        ]);

        $this->assertFalse($result['verified']);
        $this->assertContains('source_not_bound_to_verified_profile', $result['blockers']);
    }

    public function test_inactive_or_non_authoritative_source_is_blocked(): void
    {
        $source = new Source([
            'source_type' => 'web',
            'name' => 'Third-party directory',
            'url' => 'https://directory.example.test/university',
            'status' => 'inactive',
        ]);

        $result = (new OfficialSourceIdentityVerifier())->verify($source, [
            'example-university' => [
                'source' => ['url' => 'https://directory.example.test/university'],
            ],
        ]);

        $this->assertFalse($result['verified']);
        $this->assertContains('source_not_active', $result['blockers']);
        $this->assertContains('source_type_not_authoritative', $result['blockers']);
    }

    public function test_credentials_in_source_url_are_not_verified_as_official(): void
    {
        $url = 'https://user:secret@university.example.edu/programs';
        $source = new Source([
            'source_type' => 'official',
            'name' => 'Credential-bearing URL',
            'url' => $url,
            'status' => 'active',
        ]);

        $result = (new OfficialSourceIdentityVerifier())->verify($source, [
            'example-university' => ['source' => ['url' => $url]],
        ]);

        $this->assertFalse($result['verified']);
        $this->assertContains('source_url_invalid', $result['blockers']);
        $this->assertContains('source_not_bound_to_verified_profile', $result['blockers']);
    }

    public function test_private_ipv4_source_url_is_not_verified_as_official(): void
    {
        $url = 'http://127.0.0.1/programs';
        $source = new Source([
            'source_type' => 'official',
            'name' => 'Private network URL',
            'url' => $url,
            'status' => 'active',
        ]);

        $result = (new OfficialSourceIdentityVerifier())->verify($source, [
            'example-university' => ['source' => ['url' => $url]],
        ]);

        $this->assertFalse($result['verified']);
        $this->assertContains('source_url_invalid', $result['blockers']);
    }

    public function test_private_ipv6_source_url_is_not_verified_as_official(): void
    {
        $url = 'http://[::1]/programs';
        $source = new Source([
            'source_type' => 'official',
            'name' => 'Private IPv6 URL',
            'url' => $url,
            'status' => 'active',
        ]);

        $result = (new OfficialSourceIdentityVerifier())->verify($source, [
            'example-university' => ['source' => ['url' => $url]],
        ]);

        $this->assertFalse($result['verified']);
        $this->assertContains('source_url_invalid', $result['blockers']);
    }
}
