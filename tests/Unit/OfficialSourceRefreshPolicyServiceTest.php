<?php

namespace Tests\Unit;

use App\Services\OfficialSourceRefreshPolicyService;
use PHPUnit\Framework\TestCase;

class OfficialSourceRefreshPolicyServiceTest extends TestCase
{
    public function test_verified_official_field_change_is_refresh_eligible(): void
    {
        $result = (new OfficialSourceRefreshPolicyService())->evaluate($this->validProposal());

        $this->assertTrue($result['eligible']);
        $this->assertSame('refresh_eligible', $result['state']);
        $this->assertSame([], $result['blockers']);
    }

    public function test_official_flag_without_source_identity_and_fetch_proof_is_blocked(): void
    {
        $proposal = $this->validProposal();
        $proposal['source_identity_verified'] = false;
        $proposal['fetch_successful'] = false;

        $result = (new OfficialSourceRefreshPolicyService())->evaluate($proposal);

        $this->assertFalse($result['eligible']);
        $this->assertContains('source_identity_not_verified', $result['blockers']);
        $this->assertContains('source_fetch_not_successful', $result['blockers']);
    }

    public function test_ambiguous_identity_and_conflicting_official_evidence_are_blocked(): void
    {
        $proposal = $this->validProposal();
        $proposal['entity_identity_ambiguous'] = true;
        $proposal['source_conflict'] = true;

        $result = (new OfficialSourceRefreshPolicyService())->evaluate($proposal);

        $this->assertFalse($result['eligible']);
        $this->assertContains('ambiguous_entity_identity', $result['blockers']);
        $this->assertContains('conflicting_official_evidence', $result['blockers']);
    }

    public function test_unchanged_field_is_not_eligible_for_refresh(): void
    {
        $proposal = $this->validProposal();
        $proposal['proposed_value'] = $proposal['previous_value'];

        $result = (new OfficialSourceRefreshPolicyService())->evaluate($proposal);

        $this->assertFalse($result['eligible']);
        $this->assertContains('no_material_field_change', $result['blockers']);
    }

    public function test_missing_malformed_or_timezone_free_observation_timestamp_is_blocked(): void
    {
        foreach ([null, '', 'not-a-date', '2026-10-25 04:00:00'] as $timestamp) {
            $proposal = $this->validProposal();
            $proposal['observed_at'] = $timestamp;

            $result = (new OfficialSourceRefreshPolicyService())->evaluate($proposal);

            $this->assertFalse($result['eligible']);
            $this->assertContains('source_observation_timestamp_required', $result['blockers']);
        }
    }

    public function test_evidence_url_with_embedded_credentials_is_blocked(): void
    {
        $proposal = $this->validProposal();
        $proposal['evidence_url'] = 'https://user:secret@university.example.edu/admissions';

        $result = (new OfficialSourceRefreshPolicyService())->evaluate($proposal);

        $this->assertFalse($result['eligible']);
        $this->assertContains('valid_field_evidence_url_required', $result['blockers']);
    }

    public function test_timestamp_with_explicit_timezone_remains_eligible(): void
    {
        $proposal = $this->validProposal();
        $proposal['observed_at'] = '2026-10-25T04:00:00+05:00';

        $result = (new OfficialSourceRefreshPolicyService())->evaluate($proposal);

        $this->assertTrue($result['eligible']);
    }

    private function validProposal(): array
    {
        return [
            'source_official' => true,
            'source_identity_verified' => true,
            'fetch_successful' => true,
            'entity_match_confirmed' => true,
            'evidence_url' => 'https://university.example.edu/admissions',
            'evidence_excerpt' => 'Applications close on 15 November.',
            'observed_at' => '2026-10-25T04:00:00Z',
            'content_fingerprint' => hash('sha256', 'verified official page'),
            'previous_value' => '10 November',
            'proposed_value' => '15 November',
        ];
    }
}
