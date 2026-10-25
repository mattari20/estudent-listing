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
}
