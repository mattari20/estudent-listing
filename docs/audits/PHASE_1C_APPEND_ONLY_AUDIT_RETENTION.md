# Phase 1C — Append-only audit retention safeguards

Date: 2026-11-04  
Repository: `mattari20/estudent-listing`  
Implementation branch: `phase-1c-append-only-audit-retention`

## Scope

This migration protects immutable decision/event history from direct UPDATE/DELETE and from destructive parent deletion. It preserves the existing nullable `import_reconciliation_events.import_row_id` relationship: when an import row is deleted, the event remains and only that nullable association may become NULL. The reconciliation event's other content and its batch ownership remain immutable.

The protected direct-mutation scope is:

- `security_audit_events`
- `verification_decisions`
- `provenance_review_events`
- `import_promotion_decisions`
- `import_promotion_events`
- `reverification_events`
- `structured_enrichment_promotion_events`
- `official_source_refresh_events`
- `import_conflict_events`
- `import_reconciliation_events`
- `governed_execution_run_events`
- `identity_resolution_decisions` (with the narrow one-time key-enrichment exception below)

Mutable operational state (data-quality lifecycle, monitoring checkpoints/alerts, current conflict state and other mutable projections) is not classified as immutable event history.

## Safeguards implemented

### SQLite

- BEFORE UPDATE and BEFORE DELETE triggers protect the eleven scoped event tables.
- `import_reconciliation_events` has a narrow UPDATE exception: `import_row_id` may transition from a non-NULL value to NULL while the primary key, batch ID, event type, state, severity, subject, description, metadata, actor, occurrence time and timestamps remain unchanged. This preserves the existing `ON DELETE SET NULL` relationship without permitting event-content rewrites.
- Identity-resolution decisions allow only a one-time NULL-to-non-NULL `decision_identity_key` enrichment while the primary key and all decision content remain unchanged.
- Explicit parent-delete guards protect rows whose deletion would remove retained decisions/events. Reconciliation-event batch deletion is blocked; deleting its associated import row preserves the event and nulls only the row association.

### MySQL

- BEFORE UPDATE and BEFORE DELETE triggers protect the same eleven event tables (22 triggers).
- The reconciliation-event UPDATE trigger applies the same narrow nullable-row-detachment exception as SQLite; all other event-content changes are rejected.
- Separate identity-decision UPDATE/DELETE triggers allow only one-time NULL-to-non-NULL `decision_identity_key` enrichment while the primary key and every other historical field remain unchanged (2 triggers).
- Restrictive foreign keys protect identity-resolution decisions, verification decisions, reconciliation batch history, import-promotion decisions/events, reverification events, structured-enrichment promotion events, import-conflict events and governed execution-run events from destructive parent deletion.
- `import_reconciliation_events.import_row_id` retains its existing SET NULL behavior; `import_reconciliation_events.import_batch_id` is RESTRICT.
- A preflight checks affected child-to-parent relationships and fails closed if orphan history exists. No historical rows are rewritten or deleted.

### Model-level protection

The `PreventsAuditMutation` guard is used by the scoped event models, including `ImportReconciliationEvent`, to reject ordinary Eloquent update/delete calls. Database triggers provide the separate direct-SQL enforcement; model events are not treated as a substitute.

## Driver and privilege design

The first isolated MySQL trigger attempt failed with MySQL error 1419 because binary logging was enabled and the isolated CI user lacked the required trigger-creation privilege. The isolated CI MySQL service now starts with `--log-bin-trust-function-creators=1` so the migration can create its triggers in that disposable CI service. This setting is not applied to production and no production privilege or configuration was changed.

## Regression coverage

`tests/Feature/AppendOnlyAuditRetentionTest.php` covers:

- Eloquent update/delete guards.
- Direct SQL UPDATE/DELETE rejection on append-only audit rows.
- One-time legacy decision-key enrichment.
- Rejection of repeated enrichment, primary-key mutation and unrelated decision-content changes.
- Parent-row and batch deletion retention.
- Reconciliation-event direct SQL UPDATE/DELETE rejection.
- Preservation of reconciliation history when its import row is deleted: only `import_row_id` becomes NULL.
- Rejection of batch deletion while reconciliation history remains.
- Transaction rollback without deleting pre-existing audit history.

`tests/Support/verify-append-only-audit-triggers.php` verifies all 24 named MySQL triggers exist, are removed by migration rollback, are reinstalled by migration up, and remain stable on repeated guard installation.

The focused MySQL test and trigger lifecycle proof passed on the previously validated code head `8f92cd47f7b12a5430ad3bbdd7677540d2718447`: run #41790446802 recorded 8 tests / 22 assertions and verified 22 triggers. The reconciliation-event extension in the current PR head adds one focused test and two triggers; its new exact-head Test/Laravel/isolated-MySQL results must be used for the updated implementation. Do not treat the earlier run as proof of the new reconciliation-event extension.

## Limits and safety

- The nullable import-row association can be detached only by the narrowly permitted non-NULL-to-NULL transition; the event itself and batch ownership remain immutable.
- MySQL trigger installation requires appropriate trigger-creation capability in the database environment. This was proved only in isolated CI; production trigger presence/privileges remain unverified.
- This migration does not make all mutable operational projections append-only and does not claim production verification.

No production migration, database access, Hostinger, production Artisan command, deployment or history rewrite was performed. GCU rows #348–353 and batches #19–23 remain protected. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data.


## Final reconciliation-event coverage and exact-head validation (2026-11-04)

This section supersedes the preceding note that the reconciliation-event extension still awaited validation.

**Final validated code/documentation head before this addendum:** `7d761c069aaea72bf33fb75bf6bd5a3df1a03d7f`.

- Test #41793120096 — PASS, **896 tests / 3,944 assertions**.
- Laravel CI #41793120081 — PASS.
- Institutes Directory Project Controller #41793120151 — PASS.
- Isolated MySQL Approval Migration Validation #41793120124 — PASS.
- Focused MySQL `AppendOnlyAuditRetentionTest.php` — **9 tests / 28 assertions**.
- MySQL trigger lifecycle proof — **24 named triggers** verified; rollback removed them; migration up reinstalled them; repeated installation remained idempotent.
- The same isolated MySQL run passed the existing chronological retrieval, candidate batch, review-package, identity conflict, operator transition and monitoring checkpoint recovery/multiprocess steps.
- The reconciliation-event test passed on the same MySQL service: direct SQL event-content UPDATE and DELETE were rejected; deleting the parent import row preserved the event and nulled only `import_row_id`; deleting the parent batch was rejected.
- The new `ImportReconciliationEvent` model guard, SQLite/MySQL triggers and narrow FK null-detachment exception are therefore IMPLEMENTED and TESTED on this exact head.

The documentation-only addendum that records this validation does not change the tested migration, model, test or trigger-proof code. Its own PR-head CI is still required before merge. No production migration, trigger, privilege, database or Hostinger operation was performed.
