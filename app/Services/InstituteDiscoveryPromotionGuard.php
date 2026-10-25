<?php

namespace App\Services;

/**
 * Fail-closed guard to revalidate a reviewed proposal immediately before a
 * promotion service attempts a canonical write. This class performs no writes.
 */
class InstituteDiscoveryPromotionGuard
{
    public function __construct(
        private readonly InstituteDiscoveryReviewPolicyService $reviewPolicy
    ) {
    }

    /**
     * @param array<string, mixed> $proposal
     * @param array<string, mixed> $currentCanonical
     * @return array{eligible:bool,state:string,blockers:array<string>,canonical_fingerprint:string}
     */
    public function evaluate(array $proposal, array $currentCanonical): array
    {
        $fingerprint = $this->fingerprint($currentCanonical);
        $expectedFingerprint = (string) ($proposal['expected_canonical_fingerprint'] ?? '');

        if ($expectedFingerprint === '' || ! hash_equals($fingerprint, $expectedFingerprint)) {
            return [
                'eligible' => false,
                'state' => 'blocked_stale_review',
                'blockers' => [$expectedFingerprint === '' ? 'expected_canonical_fingerprint_missing' : 'canonical_record_changed_after_review'],
                'canonical_fingerprint' => $fingerprint,
            ];
        }

        $policy = $this->reviewPolicy->evaluate($proposal);

        return [
            'eligible' => $policy['eligible'],
            'state' => $policy['eligible'] ? 'eligible_for_transactional_promotion' : $policy['state'],
            'blockers' => $policy['blockers'],
            'canonical_fingerprint' => $fingerprint,
        ];
    }

    public function fingerprint(array $canonical): string
    {
        $canonical = $this->sortRecursively($canonical);

        return hash('sha256', json_encode(
            $canonical,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ));
    }

    private function sortRecursively(array $values): array
    {
        ksort($values);

        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = $this->sortRecursively($value);
            }
        }
        unset($value);

        return $values;
    }
}
