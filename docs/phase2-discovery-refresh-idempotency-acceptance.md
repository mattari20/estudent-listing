# Discovery batch refresh: idempotency acceptance criteria

The existing refresh command requires `--confirm-new-batch` and a valid `--supersedes-batch-id`; it is intended to create a new immutable review batch and retain the prior batch. These safeguards must be preserved.

## Required tests
- Missing confirmation creates no new batch.
- Missing, invalid, or unknown superseded batch ID creates no new batch.
- A valid refresh retains the prior batch and its source provenance.
- The new batch records its source snapshot and superseded-batch relationship.
- A failed agent run does not mark the prior batch as replaced or delete any rows.
- Repeated runs create distinct immutable batches only after explicit confirmation; they must not mutate or overwrite the earlier batch.
- No canonical institutions, campuses, programmes, or offerings are approved/promoted by refresh.

## Required run summary
Report prior batch ID, new batch ID, snapshot ID, candidate count, duplicate count, review-package status, and explicit false values for canonical mutations and automatic promotion.

## Safety
This is an acceptance checklist, not evidence that all tests have already been implemented or run. Do not run refresh against production data until the profile and prior batch are reviewed.