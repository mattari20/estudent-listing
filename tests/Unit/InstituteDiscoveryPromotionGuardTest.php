<?php

namespace Tests\Unit;

use App\Services\InstituteDiscoveryPromotionGuard;
use App\Services\InstituteDiscoveryReviewPolicyService;
use PHPUnit\Framework\TestCase;

class InstituteDiscoveryPromotionGuardTest extends TestCase
{
    private function validProposal(string $fingerprint): array
    {
        return [
            'expected_canonical_fingerprint' => $fingerprint,
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
        ];
    }

    public function test_changed_canonical_record_invalidates_stale_review(): void
    {
        $guard = new InstituteDiscoveryPromotionGuard(new InstituteDiscoveryReviewPolicyService());
        $current = ['id' => 7, 'name' => 'Current name'];
        $proposal = $this->validProposal($guard->fingerprint(['id' => 7, 'name' => 'Old name']));

        $result = $guard->evaluate($proposal, $current);

        $this->assertFalse($result['eligible']);
        $this->assertSame('blocked_stale_review', $result['state']);
        $this->assertContains('canonical_record_changed_after_review', $result['blockers']);
    }

    public function test_missing_canonical_fingerprint_fails_closed(): void
    {
        $guard = new InstituteDiscoveryPromotionGuard(new InstituteDiscoveryReviewPolicyService());
        $proposal = $this->validProposal('');
        unset($proposal['expected_canonical_fingerprint']);

        $result = $guard->evaluate($proposal, ['id' => 7, 'name' => 'Current name']);

        $this->assertFalse($result['eligible']);
        $this->assertContains('expected_canonical_fingerprint_missing', $result['blockers']);
    }

    public function test_valid_review_and_unchanged_canonical_record_pass_guard(): void
    {
        $guard = new InstituteDiscoveryPromotionGuard(new InstituteDiscoveryReviewPolicyService());
        $current = ['name' => 'Current name', 'id' => 7];
        $proposal = $this->validProposal($guard->fingerprint(['id' => 7, 'name' => 'Current name']));

        $result = $guard->evaluate($proposal, $current);

        $this->assertTrue($result['eligible']);
        $this->assertSame('eligible_for_transactional_promotion', $result['state']);
    }

    public function test_fingerprint_is_stable_for_key_order(): void
    {
        $guard = new InstituteDiscoveryPromotionGuard(new InstituteDiscoveryReviewPolicyService());

        $this->assertSame(
            $guard->fingerprint(['b' => 2, 'a' => ['y' => 2, 'x' => 1]]),
            $guard->fingerprint(['a' => ['x' => 1, 'y' => 2], 'b' => 2])
        );
    }
}
