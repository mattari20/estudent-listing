# Governed discovery promotion integration contract

The discovery pipeline is composed from independently testable stages:

1. Candidate quality validation rejects malformed candidates and records stable blocker codes.
2. Identity resolution matches exact normalized identifiers/names within the parent institution; ambiguous and unknown records remain unresolved.
3. Field diffing requires per-field evidence and blocks sensitive relationship/status changes without official evidence.
4. Review policy and the promotion guard require explicit approval metadata and an unchanged canonical fingerprint before a proposal is eligible for promotion.

## Promotion boundary

The legacy `StructuredEnrichmentPromoter` now runs promotion inside a database transaction, validates required payload fields and positive relationship IDs before any write, locks and re-checks the import row's approval state, and invokes `InstituteDiscoveryPromotionGuard` for discovery-sourced rows carrying `_provenance` or `_governed_review` metadata. Governed discovery rows without a review contract fail closed. Internal metadata is removed before canonical model attributes are written.

Legacy/manual import rows without discovery metadata retain their prior approval-based behavior for compatibility. This is an intentional migration boundary, not a claim that every legacy import is governed. A later migration should classify all source types explicitly and move legacy sources to the same evidence/review contract.

The guard compares the proposal's expected canonical fingerprint with the current target record snapshot while the transaction is active. A successful eligibility result is still not proof that a database write succeeded; uniqueness and foreign-key constraints remain authoritative.

## Acceptance cases

- Pending review or missing reviewer/reason/timestamp/package is blocked.
- AI confidence alone cannot substitute for field evidence.
- Campus/programme relationship changes require official evidence.
- A canonical record changed after review invalidates the review package.
- A discovery-sourced row without a review contract is blocked.
- A duplicate/ambiguous identity does not auto-create a canonical entity.
- The same snapshot is idempotent and does not create duplicate candidates.
- Source fetch failure/empty snapshots never delete or deactivate canonical entities.
- A successful eligibility result still requires transactional revalidation at write time.

## Remaining safeguards

- Add integration tests against the application's actual database schema for campus, program, and offering promotion paths.
- Persist immutable before/after audit events for canonical writes.
- Replace metadata-presence detection with an explicit, persisted source-kind column/contract after migration planning.
- Keep production writes disabled until the live deployment revision and read-only integrity checks are reconciled.


## Transactional promotion audit trail

The structured enrichment promoter writes one `structured_enrichment_promotion_events` row after each successful canonical promotion. The event records the import row and batch, entity type, canonical model class and ID, actor type, reason, before/after canonical snapshots, governed-discovery flag, review-package ID (when applicable), and event timestamp.

The audit insert is part of the same database transaction as the canonical write. If it fails, the canonical promotion must roll back as well. The audit event is a success event only; blocked attempts are not represented by this table in this implementation. Do not describe this table as a complete security-grade immutable log until deletion/retention behavior and failure-event coverage have been reviewed.

### Migration and release order

1. Run the migration in the controlled deployment pipeline before releasing code that writes these events.
2. Run the focused campus, program, offering, and structured enrichment promotion tests.
3. Verify event count and before/after snapshots in the test/staging environment.
4. Deploy only when the deployed revision and migration state are confirmed; run read-only integrity checks before authorizing any production data writes.

The audit table is separate from the legacy import-promotion event table because structured enrichment rows live in `enrichment_import_rows`, not the legacy `import_rows` table.
