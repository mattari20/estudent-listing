<?php

namespace Tests\Feature;

use App\Services\OfficialSourceRefreshPolicyService;
use Tests\TestCase;

class OfficialSourceRefreshPolicyServiceTest extends TestCase
{
    public function test_complete_verified_official_evidence_is_eligible(): void
    {
        $decision = app(OfficialSourceRefreshPolicyService::class)->evaluate($this->validProposal());

        $this->assertTrue($decision['eligible']);
        $this->assertSame('refresh_eligible', $decision['state']);
        $this->assertSame([], $decision['blockers']);
    }

    public function test_unverified_source_and_entity_identity_are_blocked(): void
    {
        $proposal = $this->validProposal();
        $proposal['source_official'] = false;
        $proposal['source_identity_verified'] = false;
        $proposal['entity_match_confirmed'] = false;

        $decision = app(OfficialSourceRefreshPolicyService::class)->evaluate($proposal);

        $this->assertFalse($decision['eligible']);
        $this->assertContains('official_source_not_verified', $decision['blockers']);
        $this->assertContains('source_identity_not_verified', $decision['blockers']);
        $this->assertContains('canonical_entity_match_not_confirmed', $decision['blockers']);
    }

    public function test_evidence_url_with_credentials_or_non_http_scheme_is_blocked(): void
    {
        $service = app(OfficialSourceRefreshPolicyService::class);

        $withCredentials = $service->evaluate(array_merge($this->validProposal(), [
            'evidence_url' => 'https://user:secret@university.example.edu/campuses',
        ]));
        $nonHttp = $service->evaluate(array_merge($this->validProposal(), [
            'evidence_url' => 'javascript:alert(1)',
        ]));

        $this->assertFalse($withCredentials['eligible']);
        $this->assertContains('valid_field_evidence_url_required', $withCredentials['blockers']);
        $this->assertFalse($nonHttp['eligible']);
        $this->assertContains('valid_field_evidence_url_required', $nonHttp['blockers']);
    }

    public function test_timestamp_without_explicit_timezone_is_blocked(): void
    {
        $decision = app(OfficialSourceRefreshPolicyService::class)->evaluate(array_merge(
            $this->validProposal(),
            ['observed_at' => '2026-10-25 04:00:00']
        ));

        $this->assertFalse($decision['eligible']);
        $this->assertContains('source_observation_timestamp_required', $decision['blockers']);
    }

    public function test_no_change_ambiguous_identity_and_conflicting_source_are_blocked(): void
    {
        $proposal = $this->validProposal();
        $proposal['proposed_value'] = $proposal['previous_value'];
        $proposal['entity_identity_ambiguous'] = true;
        $proposal['source_conflict'] = true;

        $decision = app(OfficialSourceRefreshPolicyService::class)->evaluate($proposal);

        $this->assertFalse($decision['eligible']);
        $this->assertContains('no_material_field_change', $decision['blockers']);
        $this->assertContains('ambiguous_entity_identity', $decision['blockers']);
        $this->assertContains('conflicting_official_evidence', $decision['blockers']);
    }

    public function test_missing_excerpt_and_fingerprint_are_blocked(): void
    {
        $proposal = $this->validProposal();
        $proposal['evidence_excerpt'] = '  ';
        $proposal['content_fingerprint'] = null;

        $decision = app(OfficialSourceRefreshPolicyService::class)->evaluate($proposal);

        $this->assertFalse($decision['eligible']);
        $this->assertContains('field_evidence_excerpt_required', $decision['blockers']);
        $this->assertContains('source_content_fingerprint_required', $decision['blockers']);
    }

    private function validProposal(): array
    {
        return [
            'source_official' => true,
            'source_identity_verified' => true,
            'fetch_successful' => true,
            'entity_match_confirmed' => true,
            'evidence_url' => 'https://university.example.edu/campuses',
            'evidence_excerpt' => 'The campus name is shown on the official campus page.',
            'observed_at' => '2026-10-25T04:00:00Z',
            'content_fingerprint' => hash('sha256', 'official campus page'),
            'previous_value' => 'Old Campus Name',
            'proposed_value' => 'New Campus Name',
            'entity_identity_ambiguous' => false,
            'source_conflict' => false,
        ];
    }
}
