<?php

namespace Tests\Unit;

use App\Services\InstituteDiscoveryReviewPolicyService;
use PHPUnit\Framework\TestCase;

class InstituteDiscoveryReviewPolicyServiceTest extends TestCase
{
    public function test_proposal_without_explicit_approval_is_blocked(): void
    {
        $result = app(InstituteDiscoveryReviewPolicyService::class)->evaluate([
            'decision' => 'pending',
            'changes' => [
                'name' => [
                    'state' => 'review_required',
                    'blockers' => [],
                    'evidence_url' => 'https://example.edu/programmes',
                    'evidence_excerpt' => 'Official programme name',
                    'source_official' => true,
                ],
            ],
        ]);

        $this->assertFalse($result['eligible']);
        $this->assertSame('blocked', $result['state']);
        $this->assertContains('explicit_approval_required', $result['blockers']);
    }

    public function test_approved_proposal_requires_actor_reason_package_and_official_evidence(): void
    {
        $result = app(InstituteDiscoveryReviewPolicyService::class)->evaluate([
            'decision' => 'approved',
            'decision_by' => 'reviewer-1',
            'decision_at' => '2026-10-25T08:00:00+05:00',
            'decision_reason' => 'Verified against official programme page',
            'review_package_id' => 'review-123',
            'changes' => [
                'name' => [
                    'state' => 'review_required',
                    'blockers' => [],
                    'evidence_url' => 'https://example.edu/programmes',
                    'evidence_excerpt' => 'Official programme name',
                    'source_official' => true,
                ],
            ],
        ]);

        $this->assertTrue($result['eligible']);
        $this->assertSame('eligible_for_promotion', $result['state']);
        $this->assertSame([], $result['blockers']);
    }

    public function test_approval_does_not_override_evidence_or_validation_blockers(): void
    {
        $result = app(InstituteDiscoveryReviewPolicyService::class)->evaluate([
            'decision' => 'approved',
            'decision_by' => 'reviewer-1',
            'decision_at' => '2026-10-25T08:00:00+05:00',
            'decision_reason' => 'Reviewed',
            'review_package_id' => 'review-123',
            'changes' => [
                'campus_id' => [
                    'state' => 'blocked',
                    'blockers' => ['official_evidence_required_for_sensitive_field'],
                    'evidence_url' => 'https://example.edu',
                    'evidence_excerpt' => 'Campus listing',
                    'source_official' => false,
                ],
            ],
        ]);

        $this->assertFalse($result['eligible']);
        $this->assertContains('campus_id:change_not_reviewable', $result['blockers']);
        $this->assertContains('campus_id:validation_blockers_present', $result['blockers']);
        $this->assertContains('campus_id:official_source_required', $result['blockers']);
    }

    public function test_empty_changes_are_blocked(): void
    {
        $result = app(InstituteDiscoveryReviewPolicyService::class)->evaluate([
            'decision' => 'approved',
            'decision_by' => 'reviewer-1',
            'decision_at' => '2026-10-25T08:00:00+05:00',
            'decision_reason' => 'Reviewed',
            'review_package_id' => 'review-123',
            'changes' => [],
        ]);

        $this->assertFalse($result['eligible']);
        $this->assertContains('proposed_changes_missing', $result['blockers']);
    }
}
