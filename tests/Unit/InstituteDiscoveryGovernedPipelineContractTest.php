<?php

namespace Tests\Unit;

use App\Services\InstituteDiscoveryPromotionGuard;
use App\Services\InstituteDiscoveryReviewPolicyService;
use PHPUnit\Framework\TestCase;

class InstituteDiscoveryGovernedPipelineContractTest extends TestCase
{
    public function test_missing_approval_and_evidence_cannot_become_promotion_eligible(): void
    {
        $guard = new InstituteDiscoveryPromotionGuard(new InstituteDiscoveryReviewPolicyService());

        $canonical = ['id' => 16, 'name' => 'BSc Applied Computing', 'campus_id' => 12];
        $proposal = [
            'expected_canonical_fingerprint' => $guard->fingerprint($canonical),
            'decision' => 'pending',
            'changes' => [
                'campus_id' => [
                    'state' => 'blocked',
                    'blockers' => ['official_evidence_required_for_sensitive_field'],
                    'evidence_url' => null,
                    'evidence_excerpt' => null,
                    'source_official' => false,
                ],
            ],
        ];

        $result = $guard->evaluate($proposal, $canonical);

        $this->assertFalse($result['eligible']);
        $this->assertSame('blocked', $result['state']);
        $this->assertContains('explicit_approval_required', $result['blockers']);
        $this->assertContains('decision_actor_missing', $result['blockers']);
        $this->assertContains('campus_id:change_not_reviewable', $result['blockers']);
    }

    public function test_record_changed_after_review_is_blocked_even_with_approval(): void
    {
        $guard = new InstituteDiscoveryPromotionGuard(new InstituteDiscoveryReviewPolicyService());

        $reviewedSnapshot = ['id' => 16, 'name' => 'BSc Applied Computing', 'campus_id' => 12];
        $currentSnapshot = ['id' => 16, 'name' => 'BSc Applied Computing', 'campus_id' => 15];

        $proposal = [
            'expected_canonical_fingerprint' => $guard->fingerprint($reviewedSnapshot),
            'decision' => 'approved',
            'decision_by' => 'reviewer-1',
            'decision_at' => '2026-10-25T08:00:00+05:00',
            'decision_reason' => 'Verified against official source',
            'review_package_id' => 'review-36-programme-sample',
            'changes' => [
                'name' => [
                    'state' => 'review_required',
                    'blockers' => [],
                    'evidence_url' => 'https://example.edu/programmes',
                    'evidence_excerpt' => 'Official programme title',
                    'source_official' => true,
                ],
            ],
        ];

        $result = $guard->evaluate($proposal, $currentSnapshot);

        $this->assertFalse($result['eligible']);
        $this->assertSame('blocked_stale_review', $result['state']);
        $this->assertContains('canonical_record_changed_after_review', $result['blockers']);
    }

    public function test_same_canonical_snapshot_and_complete_approval_pass_only_eligibility_gate(): void
    {
        $guard = new InstituteDiscoveryPromotionGuard(new InstituteDiscoveryReviewPolicyService());
        $canonical = ['id' => 16, 'name' => 'BSc Applied Computing', 'campus_id' => 12];

        $proposal = [
            'expected_canonical_fingerprint' => $guard->fingerprint($canonical),
            'decision' => 'approved',
            'decision_by' => 'reviewer-1',
            'decision_at' => '2026-10-25T08:00:00+05:00',
            'decision_reason' => 'Verified against official source',
            'review_package_id' => 'review-36-programme-sample',
            'changes' => [
                'name' => [
                    'state' => 'review_required',
                    'blockers' => [],
                    'evidence_url' => 'https://example.edu/programmes',
                    'evidence_excerpt' => 'Official programme title',
                    'source_official' => true,
                ],
            ],
        ];

        $result = $guard->evaluate($proposal, $canonical);

        $this->assertTrue($result['eligible']);
        $this->assertSame('eligible_for_transactional_promotion', $result['state']);
        $this->assertArrayHasKey('canonical_fingerprint', $result);
    }
}
