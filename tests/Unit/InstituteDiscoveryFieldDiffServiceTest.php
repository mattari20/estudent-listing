<?php

namespace Tests\Unit;

use App\Services\InstituteDiscoveryFieldDiffService;
use PHPUnit\Framework\TestCase;

class InstituteDiscoveryFieldDiffServiceTest extends TestCase
{
    public function test_unchanged_fields_create_no_change_without_evidence(): void
    {
        $result = (new InstituteDiscoveryFieldDiffService())->compare(
            ['name' => 'BS Computer Science', 'status' => 'active'],
            ['name' => 'BS Computer Science', 'status' => 'active']
        );

        $this->assertSame('no_change', $result['state']);
        $this->assertSame([], $result['changes']);
        $this->assertCount(2, $result['unchanged']);
    }

    public function test_sensitive_change_without_official_evidence_is_blocked(): void
    {
        $result = (new InstituteDiscoveryFieldDiffService())->compare(
            ['campus_id' => 1],
            ['campus_id' => 2],
            ['campus_id' => [
                'url' => 'https://example.edu/programmes',
                'excerpt' => 'Programme offered at this campus',
                'official' => false,
            ]]
        );

        $this->assertSame('blocked', $result['state']);
        $this->assertContains('campus_id:official_evidence_required_for_sensitive_field', $result['blockers']);
    }

    public function test_supported_change_is_proposed_for_review_not_written(): void
    {
        $result = (new InstituteDiscoveryFieldDiffService())->compare(
            ['name' => 'BS CS'],
            ['name' => 'BS Computer Science'],
            ['name' => [
                'url' => 'https://example.edu/programmes',
                'excerpt' => 'Official programme title',
                'official' => true,
            ]]
        );

        $this->assertSame('review_required', $result['state']);
        $this->assertSame('BS CS', $result['changes']['name']['previous_value']);
        $this->assertSame('BS Computer Science', $result['changes']['name']['proposed_value']);
        $this->assertSame('review_required', $result['changes']['name']['state']);
    }

    public function test_missing_evidence_blocks_changed_field(): void
    {
        $result = (new InstituteDiscoveryFieldDiffService())->compare(
            ['name' => 'Old Name'],
            ['name' => 'New Name']
        );

        $this->assertSame('blocked', $result['state']);
        $this->assertContains('name:field_evidence_url_missing_or_invalid', $result['blockers']);
        $this->assertContains('name:field_evidence_excerpt_missing', $result['blockers']);
    }

    public function test_verified_successful_official_source_change_is_refresh_eligible(): void
    {
        $result = (new InstituteDiscoveryFieldDiffService())->compare(
            ['name' => 'Old Name'],
            ['name' => 'New Name'],
            ['name' => [
                'url' => 'https://university.example.edu/about',
                'excerpt' => 'New Name',
                'official' => true,
                'source_identity_verified' => true,
                'fetch_successful' => true,
                'entity_match_confirmed' => true,
                'observed_at' => '2026-10-25T04:00:00Z',
                'content_fingerprint' => hash('sha256', 'verified source snapshot'),
            ]]
        );

        $this->assertSame('refresh_eligible', $result['state']);
        $this->assertSame('refresh_eligible', $result['changes']['name']['state']);
        $this->assertSame('Old Name', $result['changes']['name']['previous_value']);
        $this->assertSame('New Name', $result['changes']['name']['proposed_value']);
    }

    public function test_official_flag_alone_does_not_make_change_refresh_eligible(): void
    {
        $result = (new InstituteDiscoveryFieldDiffService())->compare(
            ['name' => 'Old Name'],
            ['name' => 'New Name'],
            ['name' => [
                'url' => 'https://university.example.edu/about',
                'excerpt' => 'New Name',
                'official' => true,
            ]]
        );

        $this->assertSame('review_required', $result['state']);
        $this->assertSame('review_required', $result['changes']['name']['state']);
    }

    public function test_field_diff_exposes_refresh_policy_blockers_when_official_flag_alone_is_present(): void
    {
        $result = (new InstituteDiscoveryFieldDiffService())->compare(
            ['name' => 'Old Name'],
            ['name' => 'New Name'],
            ['name' => [
                'url' => 'https://university.example.edu/about',
                'excerpt' => 'New Name',
                'official' => true,
            ]]
        );

        $this->assertSame('review_required', $result['state']);
        $this->assertFalse($result['changes']['name']['refresh_policy']['eligible']);
        $this->assertContains(
            'source_identity_not_verified',
            $result['changes']['name']['refresh_policy']['blockers']
        );
        $this->assertContains(
            'source_content_fingerprint_required',
            $result['changes']['name']['refresh_policy']['blockers']
        );
    }
}
