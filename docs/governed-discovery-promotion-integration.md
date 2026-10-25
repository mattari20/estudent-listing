# Governed discovery promotion integration contract

The discovery pipeline is composed from four independent, testable stages:

1. Candidate quality validation rejects malformed candidates and records stable blocker codes.
2. Identity resolution matches only exact normalized identifiers/names scoped to the parent institution; ambiguous and unknown records remain unresolved.
3. Field diffing requires per-field evidence and blocks sensitive relationship/status changes without official evidence.
4. Review policy and promotion guard require explicit approval metadata and an unchanged canonical fingerprint before a proposal is eligible for a later transactional promotion.

## Important boundary

`eligible_for_transactional_promotion` is a precondition, not a database write and not proof that a write has occurred. The eventual promoter must re-fetch the canonical row inside a database transaction, lock it where supported, compare the reviewed fingerprint again, enforce unique constraints and foreign-key relationships, write only approved fields, and persist an audit event with before/after values. If the row changed or any constraint fails, the transaction must abort.

## Integration acceptance cases

- Pending review or missing reviewer/reason/timestamp/package is blocked.
- AI confidence alone cannot substitute for field evidence.
- Campus/programme relationship changes require official evidence.
- A canonical record changed after review invalidates the review package.
- A duplicate/ambiguous identity does not auto-create a canonical entity.
- The same snapshot is idempotent and does not create duplicate candidates.
- Source fetch failure/empty snapshots never delete or deactivate canonical entities.
- A successful eligibility result still requires transactional revalidation at write time.

The current tests validate the policy contract without writing canonical data. The existing StructuredEnrichmentPromoter must be wired to this guard in a separate, compatibility-aware change after its row/review metadata contract is migrated; do not assume this document or eligibility service alone gates legacy promotion paths.
