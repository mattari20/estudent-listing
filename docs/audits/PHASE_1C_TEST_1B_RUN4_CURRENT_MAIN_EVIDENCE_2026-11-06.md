# Phase 1C Test 1B — Current-main All-18 Read-only Preflight (Run 4)

**Repository:** `mattari20/estudent-listing`  
**Inspected main revision:** `912d56380836bcf41a0a54ac8054e0dea57dc38b`  
**Workflow run:** [#42150990085](https://github.com/mattari20/estudent-listing/actions/runs/42150990085)  
**Workflow:** `.github/workflows/phase-1c-test-1b-consolidated-all18-preflight.yml`  
**Run event:** `workflow_dispatch`  
**Run conclusion:** `success`  
**Execution window:** 2026-11-06 02:32:41Z–02:33:48Z  
**Duration:** 1m 7s  
**Evidence type:** Protected, aggregate-only, read-only production diagnostic.

## Executive disposition

| Gate | Result |
|---|---|
| GitHub workflow execution | PASS |
| Exact-main dispatch guard | PASS |
| PHP syntax and protected credentials | PASS |
| Remote aggregate read-only diagnostic | PASS |
| Production schema acceptance | **BLOCKED** |
| All-18 migration ledger reconciliation | **BLOCKED — 18/18 entries absent** |
| Migration readiness | **NOT ESTABLISHED** |
| Test 1B closure | **OPEN** |

A successful workflow conclusion proves that the diagnostic executed. It does not mean the production schema satisfies the migration contracts.

## Current production inventory

| Check | Observed result |
|---|---:|
| Expected tables inspected | 39 |
| Tables present | 28 |
| Tables missing | 11 |
| Expected column checks | 50 |
| Expected columns missing | 13 |
| Expected index checks | 23 |
| Expected indexes missing | 13 |
| Pending migration ledger entries | 18 |
| Ledger entries recorded | 0 |
| Expected audit triggers | 24 |
| Expected audit triggers present | 0 |
| Expected named foreign keys | 23 |
| Expected named foreign keys present | 0 |

All 18 selected migration ledger lookups returned zero:

- `2026_10_17_000001_create_password_reset_tokens_table`
- `2026_10_17_000002_add_must_change_password_to_users_table`
- `2026_10_23_000001_add_duplicate_rows_to_import_batches_table`
- `2026_10_25_000001_create_structured_enrichment_promotion_events_table`
- `2026_11_03_000001_create_governed_execution_leases_table`
- `2026_11_03_000002_create_governed_execution_runs_table`
- `2026_11_03_000003_add_source_retrieval_idempotency_key`
- `2026_11_03_000004_add_snapshot_and_observation_idempotency`
- `2026_11_03_000005_add_discovery_outcome_idempotency_key`
- `2026_11_03_000006_add_change_event_source_link_identity`
- `2026_11_03_000007_create_data_reverification_queue_identities_table`
- `2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency`
- `2026_11_04_000010_create_monitoring_policy_run_checkpoints`
- `2026_11_04_000011_add_discovery_candidate_persistence_identity_keys`
- `2026_11_04_000012_add_identity_conflict_persistence_keys_and_events`
- `2026_11_04_000013_create_source_snapshot_reconciliation_requests_table`
- `2026_11_04_000014_enforce_append_only_audit_retention`
- `2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat`

## Missing table inventory

The 11 absent tables are:

- `password_reset_tokens`
- `data_reverification_queue_identities`
- `monitoring_alert_identities`
- `monitoring_policy_runs`
- `monitoring_policy_run_sources`
- `governed_execution_leases`
- `governed_execution_runs`
- `governed_execution_run_events`
- `structured_enrichment_promotion_events`
- `import_conflict_events`
- `source_snapshot_reconciliation_requests`

## Missing expected columns

The 13 missing expected columns are:

- `users.must_change_password`
- `source_retrieval_runs.idempotency_key`
- `source_snapshots.capture_key`
- `institute_discovery_run_outcomes.idempotency_key`
- `change_events.source_change_assertion_link_id`
- `security_audit_events.monitoring_idempotency_key`
- `import_batches.discovery_identity_key`
- `evidence.discovery_identity_key`
- `import_rows.discovery_identity_key`
- `assertions.discovery_identity_key`
- `identity_match_candidates.identity_key`
- `identity_resolution_decisions.decision_identity_key`
- `import_conflicts.identity_key`

Some expected columns and indexes are reported as `NOT_CHECKED_TABLE_MISSING`; those are not included as missing-column counts because their parent table does not exist.

## Data preconditions and backfill exposure

Three duplicate checks were actually evaluated and returned zero duplicate groups:

| Check | Result |
|---|---:|
| Duplicate current snapshot IDs | 0 |
| Duplicate open reverification identities | 0 |
| Duplicate open monitoring alert dedupe keys | 0 |

Other duplicate-key checks were `NOT_CHECKED_PREREQUISITE_MISSING`; they must not be interpreted as zero.

Aggregate backfill exposure:

| Migration | Rows potentially affected by source backfill | Result |
|---|---:|---|
| 000007 reverification identities | 5 open queue rows | Counted only; not copied |
| 000009 monitoring alert identities | 2 open alerts | Counted only; not copied |
| 000015 heartbeat | Not checked | Required table/columns missing |

## Orphan preconditions

18 relationships were considered:

- 7 evaluated; all returned zero orphans.
- 11 skipped because prerequisite tables or columns were missing.
- 0 nonzero orphan counts among evaluated checks.

The seven evaluated zero counts are limited to those relationships only. The 11 skipped checks are `NOT_CHECKED`, not passes.

## Trigger and foreign-key results

- Expected audit trigger names: 0 of 24 present.
- Expected named foreign keys: 0 of 23 present.

This preflight checks expected names and selected FK identity metadata. It does **not** prove complete column-definition parity, composite FK rules, index column order/uniqueness, or trigger body/timing/event equivalence. Those details require the separate structural metadata audit.

## Safety and non-actions confirmed by the run

The job log explicitly reports:

- `read_only_transaction=ROLLED_BACK`
- `production_database_changed=false`
- `production_application_files_changed=false`
- `production_migrations=NOT_RUN`
- `ddl_dml_executed=NO`
- No application row payloads emitted.
- No persistent report written.
- No remote temporary diagnostic script file created.

No migration, DDL, DML, deployment, trigger installation, FK modification, production repair, dummy-data creation, candidate staging, approval, import, promotion, or publication was performed.

## Required next step — structural metadata audit

The repository already contains a separate protected read-only workflow:

`.github/workflows/phase-1c-test-1b-structural-metadata-audit.yml`

It inspects `information_schema` and emits aggregate schema metadata only:

- Table engine, collation, row format and table type.
- Column type, nullability, default, extra attributes, charset and collation.
- Index uniqueness, ordered columns, prefix lengths, collation and index type.
- FK local/referenced columns and update/delete rules.
- Trigger event, target table, timing and action-statement SHA-256.

Dispatch against the exact current main SHA only after checking that main has not advanced. Required confirmation phrase:

`AUTHORIZE-PHASE1C-TEST1B-STRUCTURAL-METADATA-READONLY`

This metadata capture is necessary to compare existing objects and partial schema against migration source. It is not a migration or remediation operation.

## Decision

**Test 1B remains OPEN / BLOCKED.** The latest production preflight reconfirms substantial schema gaps and confirms the diagnostic itself is safe. Do not run migrations or attempt schema repair based on missing ledger entries alone. First capture exact structural metadata, reconcile partial objects and migration dependencies, then prepare a separately reviewed remediation proposal with backup/restore evidence and explicit operation-by-operation approval.
