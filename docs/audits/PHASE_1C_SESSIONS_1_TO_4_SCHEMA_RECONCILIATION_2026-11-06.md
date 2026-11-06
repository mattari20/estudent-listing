# Phase 1C Sessions 1–4 — Production Schema Reconciliation Baseline

**Repository:** `mattari20/estudent-listing`  
**Production application:** `https://estudent.pk/institutes/`  
**Verified main revision:** `cfe25b49d2014a6008e72a1276b76f69603fce93`  
**Verified production database:** MariaDB `11.8.9-MariaDB-log`  
**Evidence date:** 2026-11-06  
**Scope:** Read-only production evidence and repository/isolated-CI work only. No production schema or data changes.

## 1. Executive result

Production schema acceptance is **NOT READY**. The latest consolidated read-only preflight completed successfully as a diagnostic, but it found all 18 targeted migration-ledger entries absent and multiple confirmed missing schema objects.

The successful diagnostic conclusion must not be interpreted as schema acceptance. Its read-only transaction was rolled back; no migration, DDL, DML, application-file write, temporary remote script, or persistent report was performed by the production diagnostics.

### Confirmed production facts

- Production DB server: `11.8.9-MariaDB-log`.
- Production database inventory: 52 base tables; all 52 use InnoDB.
- The consolidated target inventory checks 39 named tables: 28 present, 11 missing.
- All 18 targeted migration-ledger entries have count `0`.
- The 50 expected-column checks report 13 missing columns. Checks dependent on absent tables are `NOT_CHECKED`, not pass.
- The 23 expected named-index checks report 13 missing indexes. Checks dependent on absent tables are `NOT_CHECKED`.
- Expected audit triggers: 24; present 0; missing 24.
- Expected named foreign keys in the consolidated target set: 23; present 0; missing 23.
- 18 orphan checks: 7 evaluated (all zero), 11 skipped because prerequisites are missing.
- Duplicate checks: 3 evaluated (all zero); 11 skipped because prerequisites are missing.
- Backfill exposure: 5 open reverification queue rows and 2 open monitoring alerts. Their duplicate-open-identity checks returned zero. No backfill was executed.
- `monitoring_policy_runs` is absent, so heartbeat NULL-row exposure is not checked.

## 2. The 18 migration ledger and object reconciliation

Classification meanings:
- **MISSING:** the expected object was queried and is absent.
- **PRESENT:** the object was observed. This does not imply every dependent object is correct.
- **PARTIAL:** some expected objects exist while other objects for the migration are missing.
- **UNKNOWN:** evidence does not establish exact structural compatibility.
- **NOT_CHECKED:** a prerequisite was absent, so the dependent check was not run.

Every ledger count below is the exact observed count from run [#42207681817](https://github.com/mattari20/estudent-listing/actions/runs/42207681817).

| # | Migration | Ledger | Observed schema state |
|---:|---|---:|---|
| 1 | `2026_10_17_000001_create_password_reset_tokens_table` | 0 | `password_reset_tokens` table MISSING; its three column checks and PRIMARY index are NOT_CHECKED because the table is absent. |
| 2 | `2026_10_17_000002_add_must_change_password_to_users_table` | 0 | `users.must_change_password` MISSING. |
| 3 | `2026_10_23_000001_add_duplicate_rows_to_import_batches_table` | 0 | `import_batches.duplicate_rows` PRESENT as `int(10) unsigned NOT NULL DEFAULT 0`, matching the migration's expected unsigned integer/default. This migration's ledger entry is still absent. |
| 4 | `2026_10_25_000001_create_structured_enrichment_promotion_events_table` | 0 | `structured_enrichment_promotion_events` table MISSING; dependent columns and constraints NOT_CHECKED. |
| 5 | `2026_11_03_000001_create_governed_execution_leases_table` | 0 | `governed_execution_leases` table MISSING; expected task-key unique and expiry indexes NOT_CHECKED. |
| 6 | `2026_11_03_000002_create_governed_execution_runs_table` | 0 | `governed_execution_runs` and `governed_execution_run_events` tables MISSING; dependent columns/FKs NOT_CHECKED. |
| 7 | `2026_11_03_000003_add_source_retrieval_idempotency_key` | 0 | `source_retrieval_runs.idempotency_key` MISSING; expected unique index MISSING. |
| 8 | `2026_11_03_000004_add_snapshot_and_observation_idempotency` | 0 | `source_snapshots.capture_key` MISSING and its unique index MISSING. `source_change_observations.current_snapshot_id` PRESENT; expected unique index MISSING. Duplicate current-snapshot IDs: 0. |
| 9 | `2026_11_03_000005_add_discovery_outcome_idempotency_key` | 0 | `institute_discovery_run_outcomes.idempotency_key` MISSING; expected unique index MISSING. |
| 10 | `2026_11_03_000006_add_change_event_source_link_identity` | 0 | `change_events.source_change_assertion_link_id` MISSING; expected unique index MISSING. |
| 11 | `2026_11_03_000007_create_data_reverification_queue_identities_table` | 0 | `data_reverification_queue_identities` table MISSING. Five open queue rows are present for potential identity backfill; duplicate-open-identity groups: 0. Mapping/backfill reconciliation is NOT_CHECKED because the target table is absent. |
| 12 | `2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency` | 0 | `monitoring_alert_identities` table MISSING; `security_audit_events.monitoring_idempotency_key` and its unique index MISSING. Two open alerts are present for potential identity backfill; duplicate-open-dedupe-key groups: 0. Mapping/backfill reconciliation is NOT_CHECKED because the target table is absent. |
| 13 | `2026_11_04_000010_create_monitoring_policy_run_checkpoints` | 0 | `monitoring_policy_runs` and `monitoring_policy_run_sources` tables MISSING; dependent columns and indexes NOT_CHECKED. |
| 14 | `2026_11_04_000011_add_discovery_candidate_persistence_identity_keys` | 0 | Four identity columns MISSING: `import_batches.discovery_identity_key`, `evidence.discovery_identity_key`, `import_rows.discovery_identity_key`, `assertions.discovery_identity_key`. Their four named unique indexes are MISSING. |
| 15 | `2026_11_04_000012_add_identity_conflict_persistence_keys_and_events` | 0 | `identity_match_candidates.identity_key`, `identity_resolution_decisions.decision_identity_key`, and `import_conflicts.identity_key` MISSING; their unique indexes MISSING. `import_conflict_events` table and its idempotency key/index NOT_CHECKED because the table is absent. |
| 16 | `2026_11_04_000013_create_source_snapshot_reconciliation_requests_table` | 0 | `source_snapshot_reconciliation_requests` table MISSING; dependent columns, indexes and FKs NOT_CHECKED. |
| 17 | `2026_11_04_000014_enforce_append_only_audit_retention` | 0 | All 24 expected audit triggers MISSING. Of the 11 retention orphan preconditions, 7 were evaluated and returned zero; 4 were NOT_CHECKED because their child/parent prerequisites are absent. Expected restrictive FKs are included in the 23-name missing-FK inventory. |
| 18 | `2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat` | 0 | `monitoring_policy_runs` is absent, so `heartbeat_at` and NULL-heartbeat exposure are NOT_CHECKED. All seven monitoring orphan checks were NOT_CHECKED because prerequisite monitoring tables/columns are absent. Expected monitoring FKs are included in the 23-name missing-FK inventory. |

## 3. Exact missing-object inventories

### Missing tables (11)

1. `password_reset_tokens`
2. `data_reverification_queue_identities`
3. `monitoring_alert_identities`
4. `monitoring_policy_runs`
5. `monitoring_policy_run_sources`
6. `governed_execution_leases`
7. `governed_execution_runs`
8. `governed_execution_run_events`
9. `structured_enrichment_promotion_events`
10. `import_conflict_events`
11. `source_snapshot_reconciliation_requests`

### Missing columns (13, on tables that exist)

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

Additional expected columns on missing tables are NOT_CHECKED, not included in the 13.

### Missing named indexes (13, on existing tables)

- `source_retrieval_runs.source_retrieval_runs_idempotency_key_unique`
- `source_snapshots.source_snapshots_capture_key_unique`
- `source_change_observations.source_observations_current_snapshot_unique`
- `institute_discovery_run_outcomes.institute_discovery_run_outcomes_idempotency_key_unique`
- `change_events.change_events_source_link_unique`
- `security_audit_events.security_audit_events_monitoring_idempotency_key_unique`
- `import_batches.import_batches_discovery_identity_unique`
- `evidence.evidence_discovery_identity_unique`
- `import_rows.import_rows_discovery_identity_unique`
- `assertions.assertions_discovery_identity_unique`
- `identity_match_candidates.identity_match_candidates_identity_key_unique`
- `identity_resolution_decisions.identity_resolution_decisions_identity_key_unique`
- `import_conflicts.import_conflicts_identity_key_unique`

Ten additional expected index checks are NOT_CHECKED because their tables are absent.

### Foreign keys and triggers

- Consolidated expected named FKs: 23; present 0; missing 23.
- Existing unrelated FK metadata: 32 FK columns and 32 FK rule rows were captured in the structural metadata snapshot. These do not satisfy the absent expected named constraints.
- Expected audit triggers: 24; present 0.
- Structural audit trigger metadata count: 0.
- Trigger definitions are therefore MISSING, not merely body-incompatible.

## 4. Data, identity and orphan preconditions

- Open reverification queue rows: 5.
- Duplicate open reverification identity groups: 0.
- Open monitoring alerts: 2.
- Duplicate open monitoring dedupe-key groups: 0.
- Duplicate current-snapshot identity groups: 0.
- Other duplicate checks that depend on missing columns/tables: NOT_CHECKED.
- Orphan preconditions: 18 total; 7 evaluated and zero; 11 NOT_CHECKED due missing prerequisites.
- Heartbeat NULL-row count: NOT_CHECKED because `monitoring_policy_runs` is absent.
- No identity mapping backfill, heartbeat backfill, data cleanup, DDL or DML was performed.

The five queue rows and two monitoring alerts must be preserved and reconciled deterministically in any separately authorized recovery session. This report does not authorize that work.

## 5. Production revision, file integrity and backup evidence

- Current `main`: `cfe25b49d2014a6008e72a1276b76f69603fce93`.
- Production deployed Git revision reported: `cfe25b49d2014a6008e72a1276b76f69603fce93`.
- Production application revision fingerprint: `34f7e87e385859a7e038a0a2043149559d5a384b`.
- Deployed file integrity: all 18 targeted migration files plus `app/Support/Database/MigrationSchemaGuard.php` matched their source SHA-256 hashes; failures 0.
- Production DB engine/version: MariaDB 11.8.9; 52 InnoDB tables.

Backup run [#42197114974](https://github.com/mattari20/estudent-listing/actions/runs/42197114974) completed successfully:
- Private compressed database backup created outside the application directory.
- gzip integrity: PASS.
- SHA-256 manifest verification: PASS.
- Private file permissions: 0600.
- Restore test: NOT_YET_PERFORMED.

Backup integrity is not a restore test. No additional backup is requested by this report.

## 6. Diagnostic failure visibility

The production readiness workflow's earlier implementation caught metadata exceptions, printed only `database_metadata=FAILED_SAFE`, and exited with code 0. That code path suppressed the exception class, SQLSTATE/vendor code and message.

The latest readiness run [#42207248782](https://github.com/mattari20/estudent-listing/actions/runs/42207248782) did not reproduce that failure: it successfully reported MariaDB 11.8.9, 52 tables and 52 InnoDB tables. Earlier visible runs #2 and #1 failed before the metadata query (checkout/repository-context failure and git-repository path failure respectively). The historical exception that prompted the `FAILED_SAFE` message is therefore not recoverable from the available logs; its exact root cause remains **UNKNOWN**, and no cause is guessed.

The repository-only change in PR #1057 replaces the swallowed success path with sanitized exception class, SQLSTATE, vendor code and sanitized message, and a non-zero exit. If a future approved read-only diagnostic encounters the error, the actual exception will be visible without exposing connection credentials.

## 7. Engineering test evidence

### MySQL 8.4.13 isolated recovery

Run [#42202674014](https://github.com/mattari20/estudent-listing/actions/runs/42202674014) completed successfully against disposable MySQL 8.4.13.

- Clean full migration-chain install and rerun: passed.
- Exactly-once ledger verification for all 18: passed.
- Object verifier: 11 critical tables, 34 columns, 19 indexes, 23 exact FKs and 24 trigger definitions verified.
- Migration guard/backfill suite: 6 tests, 11 assertions passed.
- Audit-retention/monitoring suite: 24 tests, 104 assertions passed.
- Trigger lifecycle verifier: passed.
- Combined PHPUnit count across those two invocations: 30 tests, 115 assertions.

This does not prove MariaDB compatibility.

### MariaDB 11.8.9

PR [#1057](https://github.com/mattari20/estudent-listing/pull/1057) adds a disposable `mariadb:11.8.9` CI service and runs the same recovery acceptance flow using `phase1c_testb_recovery` / `phase1c_ci`. Its status and test counts must be updated only after the final exact-head workflow result is available. Initial MariaDB run [#42235535943](https://github.com/mattari20/estudent-listing/actions/runs/42235535943) provisioned MariaDB 11.8.9, verified the exact server/database/user, completed the clean 18-migration install, reran the migrator, and verified all 18 ledger entries exactly once. It then failed in the object-verifier step because the verifier expected `int unsigned` while MariaDB reports `int(10) unsigned` for `import_batches.duplicate_rows`. The migration itself succeeded; this was a verifier portability defect. Integer display-width normalization has now been added to the migration guard and schema-object verifier, with new guard regression coverage. The exact-head rerun is pending; MariaDB acceptance is not yet claimed.

## 8. Migration source and guard review findings

- `MigrationSchemaGuard::createTableIfMissing` verifies table existence and required column names; it does not independently validate the full existing table definition, indexes or FKs.
- `ensureNullableUniqueColumn` validates nullable column type on MySQL and checks duplicate values, but its index metadata check does not verify every index attribute (for example prefix length and index type). The non-MySQL named-index path is less exact.
- Migrations 0007 and 0009 contain deterministic, chunked identity backfills and conflict checks against existing mappings. Their production target identity tables are missing, so the live mappings were not verified.
- Migration 0014 has explicit orphan preconditions, MySQL trigger creation/definition checks and restrictive FK logic. The 24 triggers are absent in production; four of its 11 orphan preconditions were skipped.
- Migration 0015 has seven orphan preconditions, a heartbeat backfill from `started_at`, and seven monitoring FKs. All seven orphan checks were skipped because the prerequisite schema is missing.
- The existing MySQL 8.4 tests exercise recovery and trigger lifecycle. MariaDB 11.8.9 evidence remains pending PR #1057.

This source review identifies hardening targets; it does not imply repository implementation hardening is complete.

## 9. Session 1–4 status at report creation

### Session 1 — Schema reconciliation

- S1.1 Repository/current revision: PASS.
- S1.2 All 18 migration files inspected: PASS.
- S1.3 Table inventory: PARTIAL — 39 target tables checked; 11 missing.
- S1.4 Column inventory: PARTIAL — 50 expected columns checked; 13 missing; exact full-definition comparison not complete for every expected column.
- S1.5 Index inventory: PARTIAL — 23 named indexes checked; 13 missing; full exact-definition comparison not complete for every expected index.
- S1.6 FK inventory: PARTIAL — 23 expected names absent; actual FK metadata captured, but complete per-definition source comparison is not complete.
- S1.7 Trigger inventory: PARTIAL — all 24 expected triggers absent; actual trigger metadata count 0.
- S1.8 Ledger: PASS — all 18 exact counts observed as zero.
- S1.9 Five reverification rows: PARTIAL — count and duplicate precheck verified; identity mappings NOT_CHECKED because target table missing.
- S1.10 Two monitoring alerts: PARTIAL — count and duplicate precheck verified; identity mappings NOT_CHECKED because target table missing.
- S1.11 Duplicate/orphan checks: PARTIAL — 3 duplicate checks and 7 orphan checks evaluated; remaining checks NOT_CHECKED.
- S1.12 Historical suppressed metadata exception: BLOCKED — current diagnostic succeeds; historical exception detail unavailable. New sanitized error reporting is in PR #1057.
- S1.13 Definitive report: PARTIAL — this report records current evidence and explicit unknowns; complete exact object-by-object structural comparison remains open.

### Session 2 — Migration hardening

- S2.1 MigrationSchemaGuard reviewed: PASS (source review).
- S2.2 Table/column structural checks hardened: PARTIAL — integer display-width normalization added for MySQL 8/MariaDB 11 metadata; full table/column contract enforcement remains open.
- S2.3 Index definitions verified/hardened: PARTIAL — exact single-column BTREE uniqueness, prefix length and composite-index equivalence are now checked by MigrationSchemaGuard.
- S2.4 FK definitions verified/hardened: NOT_CHECKED.
- S2.5 Trigger definitions verified/hardened: PARTIAL — migration source contains definition checks; helper/tests need expanded MariaDB validation.
- S2.6 Backfills deterministic/idempotent: PARTIAL — source and MySQL tests reviewed; production backfills not run.
- S2.7 Identity conflicts handled safely: PARTIAL — migration code checks conflicts; production mapping reconciliation NOT_CHECKED.
- S2.8 Partial/interrupted DDL recovery: NOT_CHECKED.
- S2.9 Ledger completion behavior: PASS in isolated MySQL; production ledger entries remain zero.
- S2.10 Regression tests added for every required scenario: PARTIAL — added composite-index, prefixed-index, incompatible-column-type and unsigned-BIGINT metadata coverage; remaining requested failure modes are not all covered.
- S2.11 Relevant test suites: PARTIAL — existing MySQL tests pass; MariaDB pending.
- S2.12 Changes committed/patch prepared: PARTIAL — PR #1057 contains workflow/diagnostic changes, not full MigrationSchemaGuard hardening.

### Session 3 — MariaDB compatibility

- S3.1 MariaDB 11.8.x CI configured: PARTIAL — PR #1057 adds job; exact-head checks pending.
- S3.2 Clean install: PASS — MariaDB 11.8.9 completed the full migration chain in run #42235535943.
- S3.3 Rerun/idempotency: PASS — second migrator pass and exactly-once ledger check passed in run #42235535943.
- S3.4 Partial schema recovery: NOT_CHECKED on MariaDB.
- S3.5 Identity backfills: NOT_CHECKED on MariaDB.
- S3.6 Trigger lifecycle: NOT_CHECKED on MariaDB.
- S3.7 Interrupted execution: NOT_CHECKED on MariaDB.
- S3.8 Ledger consistency: PASS — all 18 migration entries verified exactly once on MariaDB 11.8.9 in run #42235535943.
- S3.9 Production isolation: PASS by workflow design; job uses disposable CI service.
- S3.10 Final MariaDB evidence: NOT_CHECKED.

### Session 4 — Production preflight

- S4.1 Deployed revision: PASS — equals current main.
- S4.2 Migration/helper hashes: PASS — 19/19 matched.
- S4.3 Final schema inventory: PARTIAL — confirmed missing objects above.
- S4.4 Ledger: PASS — all 18 entries absent, exact count 0.
- S4.5 Identity/duplicate checks: PARTIAL — only prerequisite-ready checks evaluated.
- S4.6 Orphan/skipped checks: PARTIAL — 7 evaluated, 11 skipped.
- S4.7 Backup evidence: PASS for gzip/SHA256; restore test NOT_YET_PERFORMED.
- S4.8 Migration execution order: PARTIAL — source dependencies reviewed; final order needs full preflight/parent-table reconciliation.
- S4.9 Production impact: PARTIAL — known DDL/FK/trigger/backfill impact documented in source; actual recovery sizing/lock analysis not complete.
- S4.10 Remaining blockers: PASS — documented above.
- S4.11 Final readiness: PASS — decision is NOT_READY.

## 10. Final production decision

**NOT_READY**

Production has confirmed missing tables, columns, indexes, all 24 expected triggers and all 23 named foreign keys. Eleven orphan checks and multiple duplicate/backfill checks could not be evaluated because prerequisite objects are absent. The five open queue rows and two open alerts require preserved, deterministic reconciliation.

A separate controlled recovery session is not ready to proceed. Sessions 5–8 remain outside this assignment. This report authorizes no production migration, DDL/DML, deployment, data repair or Hostinger configuration change.
