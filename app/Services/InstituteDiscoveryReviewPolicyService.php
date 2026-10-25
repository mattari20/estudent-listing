<?php

namespace App\Services;

/**
 * Central policy gate for proposed discovery changes.
 *
 * This service decides whether a proposal is eligible to enter the existing
 * promotion path. It does not write canonical records or itself approve data.
 */
class InstituteDiscoveryReviewPolicyService
{
    /**
     * @param array<string, mixed> $proposal
     * @return array{eligible:bool,state:string,blockers:array<string>,reason:string}
     */
    public function evaluate(array $proposal): array
    {
        $blockers = [];

        if (($proposal['decision'] ?? null) !== 'approved') {
            $blockers[] = 'explicit_approval_required';
        }

        if (empty($proposal['decision_by']) || ! is_string($proposal['decision_by'])) {
            $blockers[] = 'decision_actor_missing';
        }

        if (empty($proposal['decision_at'])) {
            $blockers[] = 'decision_timestamp_missing';
        }

        if (empty($proposal['decision_reason']) || ! is_string($proposal['decision_reason'])) {
            $blockers[] = 'decision_reason_missing';
        }

        if (empty($proposal['review_package_id'])) {
            $blockers[] = 'review_package_missing';
        }

        if (! is_array($proposal['changes'] ?? null) || $proposal['changes'] === []) {
            $blockers[] = 'proposed_changes_missing';
        } else {
            foreach ($proposal['changes'] as $field => $change) {
                if (! is_array($change)) {
                    $blockers[] = $field.':invalid_change_contract';
                    continue;
                }

                if (($change['state'] ?? null) !== 'review_required') {
                    $blockers[] = $field.':change_not_reviewable';
                }

                if (($change['blockers'] ?? []) !== []) {
                    $blockers[] = $field.':validation_blockers_present';
                }

                if (empty($change['evidence_url']) || empty($change['evidence_excerpt'])) {
                    $blockers[] = $field.':evidence_incomplete';
                }

                if (($change['source_official'] ?? false) !== true) {
                    $blockers[] = $field.':official_source_required';
                }
            }
        }

        $blockers = array_values(array_unique($blockers));

        return [
            'eligible' => $blockers === [],
            'state' => $blockers === [] ? 'eligible_for_promotion' : 'blocked',
            'blockers' => $blockers,
            'reason' => $blockers === []
                ? 'Approved proposal passed policy checks; promotion must still revalidate current canonical versions transactionally.'
                : 'Proposal failed one or more governed review requirements.',
        ];
    }
}
