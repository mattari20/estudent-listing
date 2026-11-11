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

The repository-only change in PR #1057 now routes the read-only workflow catch block through `App\Support\Database\DatabaseMetadataExceptionSanitizer`. The helper returns exception class, SQLSTATE, vendor code and a sanitized message; it redacts configured host/database/user/password values and password-like assignments. Its behavior is covered by `tests/Feature/DatabaseMetadataExceptionSanitizerTest.php` on both MySQL and MariaDB. The workflow exits non-zero on metadata failure. The helper is included in the deployed-file SHA-256 manifest, so the workflow refuses to use an unverified deployed copy. No diagnostic failure was forced against production.

A separate MariaDB portability issue was found while reviewing migration rerun guards: migration `2026_10_23_000001_add_duplicate_rows_to_import_batches_table` expected `int unsigned` literally, while MariaDB 11.8 reports `int(10) unsigned`. The shared type matcher now normalizes integer display widths, and both that migration and the schema-object verifier use it. The isolated MariaDB rerun job is being extended to execute all 18 migration `up()` methods against an already-completed disposable schema so this path is tested rather than inferred.

## 7. Engineering test evidence

### MySQL 8.4.13 isolated recovery

Latest exact-head run [#42248847440](https://github.com/mattari20/estudent-listing/actions/runs/42248847440) passed against disposable MySQL 8.4.

- Clean full 18-migration install and Laravel rerun: PASS.
- All 18 migration-ledger entries present exactly once: PASS.
- Manual reapplication of all 18 migration `up()` methods: PASS.
- Object verifier: 11 critical tables, 34 columns, 19 indexes, 23 exact FKs and 24 trigger definitions: PASS.
- Migration guard, identity-conflict/backfill and sanitizer suite: 20 tests, 48 assertions: PASS.
- Audit-retention/monitoring suite: 24 tests, 104 assertions: PASS.
- Trigger lifecycle verifier: all 24 triggers verified through removal, reinstall and idempotent repeated guard installation: PASS.

The paired MariaDB 11.8.9 job passed the same test counts and acceptance checks in the same exact-head run.

### MariaDB 11.8.9

PR [#1057](https://github.com/mattari20/estudent-listing/pull/1057) adds a disposable `mariadb:11.8.9` CI service and runs the recovery acceptance flow using `phase1c_testb_recovery` / `phase1c_ci`. The latest exact-head workflow [#42248847440](https://github.com/mattari20/estudent-listing/actions/runs/42248847440) passed both MariaDB 11.8.9 and MySQL 8.4 jobs. On MariaDB it passed the clean 18-migration install, Laravel migrator rerun, exactly-once verification for all 18 ledger entries, manual reapplication of all 18 migration `up()` methods, exact schema-object verification, 20 tests / 48 assertions, 24 audit-retention/monitoring tests / 104 assertions, and all 24 trigger lifecycle checks. The MySQL job passed the same acceptance set. Earlier MariaDB failures are superseded by this exact-head result. The original PHP parse error was corrected in commit 31a73de; subsequent identity-default and exact-FK issues were separately diagnosed and corrected as documented in sections 11–13.

## 8. Migration source and guard review findings

- `MigrationSchemaGuard::createTableIfMissing` verifies table existence and required column names; it does not independently validate the full existing table definition, indexes or FKs.
- `ensureNullableUniqueColumn` validates nullable column type on MySQL and checks duplicate values, but its index metadata check does not verify every index attribute (for example prefix length and index type). The non-MySQL named-index path is less exact.
- Migrations 0007 and 0009 contain deterministic, chunked identity backfills and conflict checks against existing mappings. Their production target identity tables are missing, so the live mappings were not verified.
- Migration 0014 has explicit orphan preconditions, MySQL trigger creation/definition checks and restrictive FK logic. The 24 triggers are absent in production; four of its 11 orphan preconditions were skipped.
- Migration 0015 has seven orphan preconditions, a heartbeat backfill from `started_at`, and seven monitoring FKs. All seven orphan checks were skipped because the prerequisite schema is missing.
- The existing MySQL 8.4 tests exercise recovery and trigger lifecycle. MariaDB 11.8.9 evidence remains pending PR #1057.

This source review identifies hardening targets; it does not imply repository implementation hardening is complete.

## 9. Session 1–4 status at report creation

### Session 1 — Definitive production schema reconciliation

- S1.1 Current PR head and repository revision: PASS — PR #1057 is open/unmerged; application/workflow code head `1a91cbc2a51427f6a2776db8fa55ce2a724415a1` is based on main `cfe25b49d2014a6008e72a1276b76f69603fce93`. Subsequent branch commits changed only this authoritative report. Latest exact-head run #42248358275 on the report-only branch head `0f939142114af89fef887fba40f655c230578c78` passed both engines.
- S1.2 Existing work and prior workflow evidence: PASS — existing PR branch history and corrected paired-engine workflow evidence reviewed; latest exact-head run #42248847440 supersedes earlier failed/superseded attempts.
- S1.3 All 18 migration sources reviewed: PASS.
- S1.4 Expected tables individually reconciled: PARTIAL — 39 expected table checks; 11 MISSING and 28 PRESENT. Full per-table definition equivalence for all 28 present tables remains UNKNOWN.
- S1.5 Expected columns individually reconciled: PARTIAL — 50 checks; 13 MISSING and 37 PRESENT. `import_batches.duplicate_rows` is MATCH; full type/nullability/default/auto-increment comparison for all remaining present columns is UNKNOWN.
- S1.6 Expected indexes individually reconciled: PARTIAL — 23 named checks; 13 MISSING and 10 NOT_CHECKED because their owning tables are absent. Exact definitions for the 13 missing names are conclusive; absent-table indexes remain NOT_CHECKED.
- S1.7 Expected foreign keys individually reconciled: PASS — all 23 expected names are MISSING in production; source/target columns and delete/update semantics were independently verified on isolated MySQL 8.4 and MariaDB 11.8.9.
- S1.8 Expected triggers individually reconciled: PASS — all 24 expected triggers are MISSING in production; isolated lifecycle verification confirms the 24 expected definitions and reinstall behavior.
- S1.9 All 18 migration-ledger entries reconciled: PASS — each production count is exactly zero; both isolated engines record each migration exactly once after install/rerun.
- S1.10 Five open reverification records assessed: PARTIAL — count and duplicate-open-identity checks verified; target mapping remains NOT_CHECKED because the identity table is absent.
- S1.11 Two open monitoring alerts assessed: PARTIAL — count and duplicate-open-dedupe-key checks verified; target mapping remains NOT_CHECKED because the identity table is absent.
- S1.12 Duplicate identity checks assessed: PARTIAL — three checks evaluated with zero duplicates; eleven checks NOT_CHECKED because prerequisites are absent.
- S1.13 All orphan checks classified: PASS — seven evaluated with zero orphans; eleven NOT_CHECKED because prerequisites are absent.
- S1.14 All skipped checks have documented reasons: PASS — missing table/column prerequisites are recorded; NOT_CHECKED is not relabeled as MISSING or PASS.
- S1.15 Historical metadata exception accurately classified: PASS — exact historical cause remains UNKNOWN; no cause invented.
- S1.16 Current sanitized diagnostic behavior tested: PASS — sanitizer unit tests cover PDO and non-PDO exception paths, SQLSTATE/vendor fallback and credential redaction; latest read-only diagnostic run #42207248782 succeeded. Historical exception reproduction remains unavailable.
- S1.17 MariaDB identity-conflict regression failures corrected: PASS — MariaDB 11.8.9 exact-head run #42248358275 passed the identity-conflict suite.
- S1.18 MySQL foreign-key verification failures corrected: PASS — MySQL 8.4 exact-head run #42247680217 verified all 23 exact source/target mappings and referential actions.
- S1.19 MariaDB 11.8.9 acceptance workflow passed: PASS — run #42247680217, clean install, rerun, all-18 manual reapplication, object verifier, 20/48 and 24/104 PHPUnit suites, 24 trigger lifecycle checks.
- S1.20 MySQL 8.4 acceptance workflow passed: PASS — run #42247680217, same acceptance scope and test counts.
- S1.21 Existing authoritative report updated: PASS — this file is the existing report; no duplicate report created.
- S1.22 Remaining UNKNOWN and INCOMPATIBLE objects documented: PASS — exact matrix and explicit NOT_CHECKED states are recorded in sections 11–13 below.
- S1.23 Changes committed and evidence recorded: PASS — branch head `e7687c178be2487f797545b2605313d7d08e06ac` was compared with `main` (76 ahead, 0 behind); paired-engine run #42248847440 and other associated checks passed. A subsequent report/workflow evidence refresh is recorded in Section 14.
- S1.24 No production modifications made: PASS — no production schema/data/ledger/deployment/Hostinger/GCU changes; all database compatibility tests used disposable CI services.

### Session 2 — Migration hardening

- S2.1 MigrationSchemaGuard reviewed: PASS (source review).
- S2.2 Table/column structural checks hardened: PARTIAL — exact existing-column contracts added to six guarded table migrations; integer display-width normalization shared with migrations 0002/00023; other migration-specific structural checks remain open.
- S2.3 Index definitions verified/hardened: PARTIAL — exact single-column BTREE uniqueness, prefix length and composite-index equivalence are checked by MigrationSchemaGuard; migration 0004 now uses the shared exact capture-key index guard and checks the observation index prefix/type.
- S2.4 FK definitions verified/hardened: PARTIAL — migrations 0014/0015 now validate parent table/column plus both UPDATE_RULE and DELETE_RULE; isolated object verifier checks all 23 expected FK actions. Full production expected-FK definitions remain missing.
- S2.5 Trigger definitions verified/hardened: PARTIAL — migration source contains definition checks; helper/tests need expanded MariaDB validation.
- S2.6 Backfills deterministic/idempotent: PARTIAL — source and MySQL tests reviewed; production backfills not run.
- S2.7 Identity conflicts handled safely: PARTIAL — migration code checks conflicts; production mapping reconciliation NOT_CHECKED.
- S2.8 Partial/interrupted DDL recovery: PARTIAL — missing/partial table and incompatible-column guards are tested; full interrupted DDL recovery across every migration is not yet proven.
- S2.9 Ledger completion behavior: PASS in isolated MySQL; production ledger entries remain zero.
- S2.10 Regression tests added for every required scenario: PARTIAL — added composite-index, prefixed-index, incompatible-column-type and unsigned-BIGINT metadata coverage; remaining requested failure modes are not all covered.
- S2.11 Relevant test suites: PARTIAL — the existing MySQL and MariaDB suites passed in runs #42202674014 and #42236169585; latest added migration-reapply and FK-action hardening awaits exact-head rerun.
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
- S3.10 Final MariaDB evidence: PARTIAL — run #42236169585 passed the MariaDB 11.8.9 acceptance set; later added all-18 manual migration reapplication and FK update-rule checks await run #42237868615.

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
- S4.11 Final readiness: PARTIAL — the existing read-only preflight evidence supports NOT_READY; a final preflight rerun is intentionally deferred until Sessions 1–3 are complete.

## 10. Final production decision

**NOT_READY**

Production has confirmed missing tables, columns, indexes, all 24 expected triggers and all 23 named foreign keys. Eleven orphan checks and multiple duplicate/backfill checks could not be evaluated because prerequisite objects are absent. The five open queue rows and two open alerts require preserved, deterministic reconciliation.

A separate controlled recovery session is not ready to proceed. Sessions 5–8 remain outside this assignment. This report authorizes no production migration, DDL/DML, deployment, data repair or Hostinger configuration change.


## 11. Confirmed root causes and corrections from latest CI

### MariaDB 11.8.9 — nullable implicit SQL NULL metadata

**Confirmed root cause:** MariaDB 11.8.9 returned the unquoted string `NULL` in `information_schema.COLUMNS.COLUMN_DEFAULT` for nullable columns whose effective default is SQL NULL. For those rows, `COLUMN_DEFAULT IS NULL` was false while `IS_NULLABLE=YES`. The old guard therefore rejected valid definitions for `data_reverification_queue_identities.attribute` and `monitoring_alert_identities.active_alert_id` before the intended identity-conflict assertions could run. The same metadata representation affected `password_reset_tokens.created_at` during migration reapplication.

**Classification:** fixture definitions were correct; migration contracts were correct; conflict assertion precedence was correct; the metadata interpretation in the guard was defective.

**Correction:** the guard recognizes MariaDB's nullable unquoted NULL sentinel, then inspects `SHOW CREATE TABLE` to distinguish an implicit SQL NULL default from the literal string default `'NULL'`. The regression suite proves SQL NULL is accepted and literal string NULL is not. Both original identity-conflict assertions now execute and pass on MariaDB.

### MySQL 8.4 — foreign-key UPDATE_RULE metadata

**Confirmed root cause:** all 23 MySQL 8.4 foreign keys had the expected source and target columns and the expected DELETE_RULE, but MySQL reported `UPDATE_RULE=NO ACTION` because the migrations do not specify an ON UPDATE clause. The migration guard and verifier required the label `RESTRICT` literally. MySQL's `NO ACTION` is equivalent to `RESTRICT` for these InnoDB constraints, so the strict string comparison falsely classified every key as incompatible. The 23 keys were not missing and their source/target column mappings were not wrong.

**Correction:** a shared action matcher normalizes only `NO ACTION` to `RESTRICT`; it keeps `CASCADE` and `SET NULL` distinct. The exact verifier now checks exactly one row per named constraint, source table/column, referenced schema/table/column, ordinal position, DELETE_RULE and UPDATE_RULE. CI logs show all 23 mappings and actions on both engines. MariaDB reports UPDATE_RULE=RESTRICT; MySQL reports UPDATE_RULE=NO ACTION; both are accepted with unchanged delete actions.

## 12. Exact expected foreign-key matrix

All rows below are **MISSING in production** (the production preflight found zero of the 23 expected named constraints). The same definitions were verified on isolated MySQL 8.4 and MariaDB 11.8.9.

| Constraint | Source column | Referenced column | Delete | Update | Production |
|---|---|---|---|---|---|
| identity_decision_row_restrict_fk | identity_resolution_decisions.import_row_id | import_rows.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| verification_decision_assertion_restrict_fk | verification_decisions.assertion_id | assertions.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| import_reconciliation_batch_restrict_fk | import_reconciliation_events.import_batch_id | import_batches.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| import_promotion_decision_row_restrict_fk | import_promotion_decisions.import_row_id | import_rows.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| import_promotion_event_row_restrict_fk | import_promotion_events.import_row_id | import_rows.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| import_promotion_event_batch_restrict_fk | import_promotion_events.import_batch_id | import_batches.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| reverification_event_queue_restrict_fk | reverification_events.queue_id | data_reverification_queue.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| structured_enrichment_row_restrict_fk | structured_enrichment_promotion_events.enrichment_import_row_id | enrichment_import_rows.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| structured_enrichment_batch_restrict_fk | structured_enrichment_promotion_events.import_batch_id | import_batches.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| import_conflict_event_restrict_fk | import_conflict_events.import_conflict_id | import_conflicts.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| governed_run_event_restrict_fk | governed_execution_run_events.run_id | governed_execution_runs.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| ssr_source_fk | source_snapshot_reconciliation_requests.source_id | sources.id | CASCADE | RESTRICT / NO ACTION | MISSING |
| ssr_triggering_snapshot_fk | source_snapshot_reconciliation_requests.triggering_snapshot_id | source_snapshots.id | CASCADE | RESTRICT / NO ACTION | MISSING |
| ssr_current_snapshot_fk | source_snapshot_reconciliation_requests.current_snapshot_id | source_snapshots.id | CASCADE | RESTRICT / NO ACTION | MISSING |
| ssr_expected_previous_fk | source_snapshot_reconciliation_requests.expected_previous_snapshot_id | source_snapshots.id | SET NULL | RESTRICT / NO ACTION | MISSING |
| ssr_recorded_previous_fk | source_snapshot_reconciliation_requests.recorded_previous_snapshot_id | source_snapshots.id | SET NULL | RESTRICT / NO ACTION | MISSING |
| mp_run_policy_fk | monitoring_policy_runs.source_monitoring_policy_id | source_monitoring_policies.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| mp_run_exec_fk | monitoring_policy_runs.execution_run_id | governed_execution_runs.id | SET NULL | RESTRICT / NO ACTION | MISSING |
| mp_source_run_fk | monitoring_policy_run_sources.monitoring_policy_run_id | monitoring_policy_runs.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| mp_source_source_fk | monitoring_policy_run_sources.source_id | sources.id | RESTRICT | RESTRICT / NO ACTION | MISSING |
| mp_source_retrieval_fk | monitoring_policy_run_sources.retrieval_run_id | source_retrieval_runs.id | SET NULL | RESTRICT / NO ACTION | MISSING |
| mp_source_observation_fk | monitoring_policy_run_sources.observation_id | source_change_observations.id | SET NULL | RESTRICT / NO ACTION | MISSING |
| mai_active_alert_fk | monitoring_alert_identities.active_alert_id | monitoring_alerts.id | SET NULL | RESTRICT / NO ACTION | MISSING |

## 13. Final object classification matrix and Session 1 decision

| Object group | Expected | Production result | Classification / limitation |
|---|---:|---|---|
| Target tables | 39 | 28 present, 11 absent | 11 MISSING; 28 UNKNOWN for full table-definition equivalence |
| Checked columns | 50 | 37 present, 13 absent | 13 MISSING; 1 MATCH (`import_batches.duplicate_rows`); remaining present definitions UNKNOWN |
| Named indexes | 23 | 13 absent; 10 depend on absent tables | 13 MISSING; 10 NOT_CHECKED |
| Named foreign keys | 23 | 0 present | 23 MISSING; exact expected definitions verified in isolated CI |
| Audit triggers | 24 | 0 present | 24 MISSING; event/timing/table/body verified in isolated CI |
| Migration ledger entries | 18 | 0 recorded | 18 MISSING in production; exactly once in both isolated engines |
| Reverification identity mappings | 5 open rows | target identity table absent | NOT_CHECKED; rows preserved |
| Monitoring alert identity mappings | 2 open alerts | target identity table absent | NOT_CHECKED; alerts preserved |
| Orphan checks | 18 | 7 zero; 11 skipped | 7 MATCH (zero orphans); 11 NOT_CHECKED |
| Duplicate checks | 14 | 3 zero; 11 skipped | 3 MATCH (zero duplicates); 11 NOT_CHECKED |

### The 28 target tables observed present

`assertions`, `change_events`, `data_reverification_queue`, `enrichment_import_rows`, `evidence`, `identity_match_candidates`, `identity_resolution_decisions`, `import_batches`, `import_conflicts`, `import_promotion_decisions`, `import_promotion_events`, `import_reconciliation_events`, `import_rows`, `institute_discovery_run_outcomes`, `institutions`, `migrations`, `monitoring_alerts`, `official_source_refresh_events`, `provenance_review_events`, `reverification_events`, `security_audit_events`, `sources`, `source_change_observations`, `source_monitoring_policies`, `source_retrieval_runs`, `source_snapshots`, `users`, `verification_decisions`.

All 28 were observed as InnoDB. Their complete table-level definition equivalence (including all options and full column/index definitions) was not established for every table, so their full-definition status remains UNKNOWN.

### The 24 expected production audit triggers

All are MISSING in production. The names below are the exact expected trigger names derived from migration 0014; each pair is BEFORE UPDATE / BEFORE DELETE respectively:

| Table | UPDATE trigger | DELETE trigger |
|---|---|---|
| security_audit_events | audit_immut_5e1df25372fff553_u | audit_immut_5e1df25372fff553_d |
| verification_decisions | audit_immut_493307493f91dfc1_u | audit_immut_493307493f91dfc1_d |
| provenance_review_events | audit_immut_b04f820f0d4b68b3_u | audit_immut_b04f820f0d4b68b3_d |
| import_promotion_decisions | audit_immut_f9de606bfd4c935d_u | audit_immut_f9de606bfd4c935d_d |
| import_promotion_events | audit_immut_f8fe18d3e78fdef2_u | audit_immut_f8fe18d3e78fdef2_d |
| reverification_events | audit_immut_72992154e9e26525_u | audit_immut_72992154e9e26525_d |
| structured_enrichment_promotion_events | audit_immut_3ba3ddd28550296d_u | audit_immut_3ba3ddd28550296d_d |
| official_source_refresh_events | audit_immut_67d791570898e61c_u | audit_immut_67d791570898e61c_d |
| import_conflict_events | audit_immut_e6d938d7ffee7729_u | audit_immut_e6d938d7ffee7729_d |
| import_reconciliation_events | audit_immut_17f41e0249a768b1_u | audit_immut_17f41e0249a768b1_d |
| governed_execution_run_events | audit_immut_0c11b0a7b877443d_u | audit_immut_0c11b0a7b877443d_d |

Two additional expected triggers on `identity_resolution_decisions` are `identity_decision_update_guard` (BEFORE UPDATE) and `identity_decision_delete_guard` (BEFORE DELETE).

The production database remains **NOT_READY**. CI proves the migration chain and verifier behavior on clean disposable MySQL/MariaDB services; it does not repair or prove production schema conformity. Session 1 is **PARTIALLY_COMPLETE** because the remaining full structural definitions for 28 present tables and 36 other present columns are UNKNOWN, ten index checks depend on missing tables, and the five queue rows/two alerts cannot be mapped while their identity tables are absent. No production recovery is authorized by this report.


## 14. Session 1 execution refresh — verified 2026-11-06

This addendum supersedes stale PR-description and workflow references in earlier sections where they conflict with the live GitHub evidence below. It does not change any production classification or authorize production recovery.

### 14.1 Live repository baseline

- Repository: `mattari20/estudent-listing`.
- Existing PR: [#1057](https://github.com/mattari20/estudent-listing/pull/1057), branch `phase1c/sessions-1-4-mariadb-metadata-diagnostics-2026-11-06`.
- Verified branch head before this report refresh: `e7687c178be2487f797545b2605313d7d08e06ac`.
- Verified base/main SHA: `cfe25b49d2014a6008e72a1276b76f69603fce93`.
- GitHub compare: 76 commits ahead, 0 behind; no merge-base divergence.
- The report-only commit `e7687c1` corrected the earlier stale S1.1 SHA reference. The application/workflow code head remains `1a91cbc2a51427f6a2776db8fa55ce2a724415a1`; no application code changed after that tested code head.
- The PR description was stale at the time of inspection: it still listed the older code head as the PR head and linked older run IDs. It is being refreshed to point to the verified current report and evidence.

### 14.2 Latest exact-head paired-engine acceptance

[Run #42248847440](https://github.com/mattari20/estudent-listing/actions/runs/42248847440) is associated with the verified branch head `e7687c178be2487f797545b2605313d7d08e06ac`. Both jobs completed successfully:

| Engine | Job result | Verified checks |
|---|---|---|
| MariaDB 11.8.9 | PASS | Clean 18-migration install; Laravel rerun; all 18 ledger entries exactly once; manual reapplication of all 18 migration `up()` methods; 11 critical tables, 34 columns, 19 indexes, 23 exact FKs, 24 trigger definitions; 20 tests / 48 assertions; 24 tests / 104 assertions; all 24 trigger lifecycle checks |
| MySQL 8.4.13 | PASS | Same acceptance set; all 23 FK mappings/actions printed; MySQL `UPDATE_RULE=NO ACTION` accepted as equivalent to `RESTRICT` only for the update action |

Other successful workflows associated with the same branch commit:
- [Test #42248847644](https://github.com/mattari20/estudent-listing/actions/runs/42248847644) — PASS.
- [Isolated MySQL Approval Migration Validation #42248847557](https://github.com/mattari20/estudent-listing/actions/runs/42248847557) — PASS.
- [Phase 1C Integrated MySQL Migration Acceptance #42248847533](https://github.com/mattari20/estudent-listing/actions/runs/42248847533) — PASS.
- [Laravel CI #42248847464](https://github.com/mattari20/estudent-listing/actions/runs/42248847464) — PASS.

The paired-engine job logs independently show MariaDB server `11.8.9-MariaDB-ubu2404` and MySQL server `8.4.13`, all 23 FK rows, both PHPUnit summaries, and the 24-trigger lifecycle result. This is isolated engineering acceptance only; it does not prove production equivalence.

### 14.3 Structural production reconciliation still outstanding

The live production evidence currently available in this report remains the prior read-only snapshot. The production-readiness workflow source was inspected: it validates deployed migration/helper hashes and reports server version, table count, table size and storage engines, but it does not emit the full per-table `SHOW CREATE TABLE` / `information_schema.COLUMNS` / `STATISTICS` comparison required by Tasks A–C. No fresh full structural production snapshot was available during this execution, and no production SQL was issued.

Therefore these classifications remain unchanged and must not be promoted:
- 28 present target tables: table-definition equivalence UNKNOWN.
- 36 present expected columns: exact definitions UNKNOWN (only `import_batches.duplicate_rows` is MATCH).
- 13 missing named indexes: production absence is established; the previous report did not preserve a complete 23-row definition matrix. The expected migration sources were inspected, but the 10 absent-parent checks remain NOT_CHECKED in production.
- Five open queue rows and two alerts: counts and duplicate-open checks remain known from the prior read-only snapshot; record-by-record source/evidence/identity mapping remains NOT_CHECKED. No row payloads were retrieved in this execution.
- 11 orphan checks and 11 duplicate checks remain NOT_CHECKED because prerequisites are missing.

A fresh production structural diagnostic requires the protected `production-readonly-diagnostics` workflow-dispatch environment and its authorized read-only Hostinger connection. That dispatch was not available through the connected GitHub actions controls in this execution. The missing metadata must be collected by that approved read-only route; do not use ad-hoc SQL credentials or production writes.

### 14.4 Production decision and Session 1 outcome

- Production readiness: **NOT_READY**.
- Session 1: **SESSION_1_PARTIALLY_COMPLETE**.
- No production migration, DDL, DML, data modification, identity backfill, deployment, Hostinger configuration change, backup creation, database restart or recreation was performed.
- PR #1057 remains the sole PR in scope; it must remain open and unmerged.
- Sessions 2–4 were not started as separate execution work.


## 15. Protected read-only production evidence collection — execution boundary (2026-11-06)

### 15.1 Existing protected diagnostic inspected

The only confirmed protected production-readiness workflow present on this branch is:

`.github/workflows/phase-1c-testb-production-readiness-preflight.yml`

Its configured controls are preserved:
- Trigger: `workflow_dispatch` only.
- Job environment: `production-readonly-diagnostics`.
- Workflow permissions: `contents: read`.
- Dispatch must target `refs/heads/main`.
- `target_sha` must equal the immutable dispatch event SHA and the current remote `main` SHA.
- An operation reason must match the validator's safe ASCII format.
- An exact operation-specific confirmation phrase is required:
  `AUTHORIZE-PHASE1C-TESTB-PRODUCTION-READINESS-READONLY`.
- Hostinger SSH credentials are read only from the protected environment secrets:
  `PRODUCTION_READONLY_HOSTINGER_SSH_HOST`,
  `PRODUCTION_READONLY_HOSTINGER_SSH_USER`,
  `PRODUCTION_READONLY_HOSTINGER_SSH_PRIVATE_KEY`,
  `PRODUCTION_READONLY_HOSTINGER_SSH_PORT`.
- The remote check uses the existing project path default:
  `/home/u537436044/domains/estudent.pk/public_html/institutes`.
- The PHP diagnostic sets a read-only transaction and rolls it back. Existing error handling uses `DatabaseMetadataExceptionSanitizer`.

The validator `scripts/ci/validate-production-dispatch.sh` was inspected. It rejects pull-request refs and any target SHA that is not the current `main` SHA. This is an intentional authorization boundary and was not modified or bypassed.

### 15.2 Existing workflow is insufficient for the requested evidence

The current workflow only:
- checks deployed file hashes and application revision;
- checks backup-tool/candidate-directory capabilities without creating a backup;
- reads database server version, total table count, total database size and storage-engine counts;
- rolls back the read-only metadata transaction;
- explicitly reports that no persistent report was written.

It does **not** query or emit:
- per-table `SHOW CREATE TABLE` definitions or table options;
- the requested per-column `information_schema.COLUMNS` fields;
- the complete 23-index `information_schema.STATISTICS` matrix;
- per-constraint foreign-key metadata for all present tables;
- record-by-record sanitized queue/alert relationship metadata;
- the 18 orphan and 14 duplicate check results as a complete current matrix;
- a durable sanitized evidence artifact.

No other production-inventory workflow path or separate protected full-inventory workflow was confirmed in the accessible branch sources. Candidate workflow paths guessed during inspection returned NOT_FOUND and were not treated as evidence.

### 15.3 Prior approved preflight evidence

Existing run [#42207248782](https://github.com/mattari20/estudent-listing/actions/runs/42207248782) was inspected. Its job completed successfully and its logs show:
- protected dispatch validator passed for main SHA `cfe25b49d2014a6008e72a1276b76f69603fce93`;
- protected Hostinger credentials were present (values were not retrieved or printed);
- deployed file hashes had zero mismatches;
- MariaDB reported `11.8.9-MariaDB-log`;
- 52 base tables were observed, all InnoDB;
- the read-only metadata transaction was rolled back;
- no backup, production data/schema change, or persistent report was created.

This prior run confirms that the existing preflight path has previously completed under its protected authorization. It does not supply the missing structural or row-mapping evidence.

### 15.4 Exact reason the requested full diagnostic was not dispatched

The connected GitHub Actions controls available to this execution expose workflow-run/job/artifact inspection and rerun operations, but do not expose the `workflow_dispatch` action. No SSH credential values or direct Hostinger access are available through this execution.

More importantly, the existing workflow validator requires the workflow to run from current `main`. The requested expanded diagnostic code does not exist on `main`; the current PR branch is 79 commits ahead and must remain unmerged under this task's explicit restriction. A workflow changed only on PR #1057 cannot pass the existing main-only dispatch guard. Dispatching the current main workflow would only repeat the limited preflight described in Section 15.2 and would not satisfy this task.

Therefore:
- no new protected production workflow was dispatched;
- no production database connection was opened by this execution;
- no production metadata or application-row query was issued;
- no ad-hoc SSH, credentials, secrets, alternate environment or bypass was attempted;
- no production diagnostic result is inferred from the prior aggregate inventory.

This is an authorization/availability boundary, not a production query failure. The required full evidence remains NOT_CHECKED.

### 15.5 Manual protected dispatch procedure — only after the full diagnostic is approved and present on main

Do **not** dispatch the currently deployed preflight expecting it to collect the missing structural evidence. Once a separately approved full read-only diagnostic implementation is present on current `main`, an authorized operator must:

1. Open GitHub repository `mattari20/estudent-listing` → **Actions**.
2. Select **Phase 1C Test B — Production Readiness Preflight (Read-only)** (or its explicitly approved replacement name, if changed in the reviewed implementation).
3. Select **Run workflow** with branch `main`.
4. Set `project_path` to the existing approved project path:
   `/home/u537436044/domains/estudent.pk/public_html/institutes`.
5. Set `target_sha` to the exact current `main` SHA displayed by GitHub at dispatch time. At this report's baseline it is `cfe25b49d2014a6008e72a1276b76f69603fce93`; re-read it at dispatch and do not reuse a stale SHA.
6. Supply a safe operation reason, for example:
   `Phase1C-TestB-structural-metadata-readonly-2026-11-06`.
7. Enter the exact confirmation phrase:
   `AUTHORIZE-PHASE1C-TESTB-PRODUCTION-READINESS-READONLY`.
8. If GitHub pauses at the `production-readonly-diagnostics` environment gate, only the configured authorized environment reviewer may approve it. Do not bypass or weaken that gate.
9. Confirm the run's exact SHA and sanitized artifact. The diagnostic must verify its production database identity and read-only transaction before collecting any metadata. Stop if any prerequisite, environment approval, database identity check or read-only assertion fails.

The procedure above is a handoff instruction, not evidence that a dispatch or approval occurred in this execution. This task's no-merge restriction means the expanded workflow cannot be dispatched from PR #1057 until its separately authorized integration onto `main`.

### 15.6 Session 1 outcome after this inspection

- Structural table definitions: 28 present tables remain UNKNOWN for exact equivalence.
- Column definitions: 36 present expected columns remain UNKNOWN; only the previously verified `import_batches.duplicate_rows` is MATCH.
- Indexes: 13 remain confirmed MISSING; ten checks remain NOT_CHECKED because their parent tables are absent. The complete requested definition matrix has not been collected.
- Five queue records: aggregate count and duplicate-open result remain known; individual mapping remains NOT_CHECKED.
- Two alerts: aggregate count and duplicate-open result remain known; individual mapping remains NOT_CHECKED.
- Orphan checks: seven previously evaluated at zero; eleven NOT_CHECKED.
- Duplicate checks: three previously evaluated at zero; eleven NOT_CHECKED.
- No disposable-database test was added for a new structural collector because no collector was implemented or executed. The existing MySQL 8.4 and MariaDB 11.8.9 acceptance remains valid for the prior code and is not represented as testing this unimplemented collector.
- No code, production configuration, database object or production record was changed by this evidence-collection attempt.

**Decision remains: `SESSION_1_PARTIALLY_COMPLETE`; production `NOT_READY`.** The exact blocker is the absence of an authorized, main-resident full structural diagnostic plus the lack of a workflow-dispatch capability in the connected GitHub controls. PR #1057 remains open and unmerged.


---

## 14. Final engineering closure and latest protected production evidence — 2026-11-11

This section is an additive closure record. Sections 1–13 preserve the historical 2026-11-06 baseline and its then-current counts. The newer protected run below is the latest evidence and supersedes those earlier production counts where the scopes overlap. Historical values have not been rewritten or relabelled.

### 14.1 Engineering delivery status

The following repository work is merged into `main`:

- **PR #1050 and PR #1051:** the two Test B migration-hardening and isolated MySQL recovery implementation packages.
- **PR #1057:** MariaDB 11.8.9 migration recovery acceptance; the paired MySQL/MariaDB acceptance evidence recorded in the historical report remains engineering/CI evidence, not production migration evidence.
- **PR #1063:** metadata-visibility classification and fail-closed remote evidence cleanup.
- **PR #1064:** definition-level reconciliation evidence contract for the 50 expected columns, 23 expected foreign keys, 24 expected triggers and 18 migration-ledger entries. Its PR description records disposable MySQL 8.4.13 and MariaDB 11.8.9 acceptance.
- **PR #1065:** Hostinger ECDSA host-identity alignment and regression coverage. Merged to `main`; the GitHub PR page showed four checks passed. The merge commit is `0a5e680db4f0ef9a49dd55fd146f68c00c3a0661`.

Engineering implementation and isolated-engine acceptance for the recorded scope are complete. This does not mean the production schema has been migrated or accepted.

### 14.2 Latest protected production diagnostic

- Workflow run: [#42965183303](https://github.com/mattari20/estudent-listing/actions/runs/42965183303)
- Result: **SUCCESS**; the single `collect` job succeeded.
- Event/ref: manually dispatched from `main`.
- Exact target/source SHA: `0a5e680db4f0ef9a49dd55fd146f68c00c3a0661`.
- Production database identity: verified; engine MariaDB; server `11.8.9-MariaDB-log`.
- Metadata visibility: complete; trigger metadata visibility: complete.
- Transaction mode: `READ ONLY`; outcome: `ROLLED_BACK`.
- Artifact: `phase1c-readonly-diagnostic-42965183303`.
- Artifact SHA-256 (GitHub artifact archive digest): `a7fc5ab49900148ec925307a765d8427a8215731ac2e92296b4e3249f09d79ab`.
- Evidence JSON SHA-256 (from the evidence manifest): `c4cfd921a0112bbc6948116c7009fd35f3a7c33a9fc12eb950abd96c5a95dbf4`.
- Evidence manifest target SHA and run ID match the workflow target and run.

The artifact contains the sanitized evidence JSON, manifest and evidence checksum. The artifact is retained by GitHub Actions for a limited period; preserve the downloaded artifact alongside this repository record if long-term audit retention is required. No production schema/data mutation is represented or authorized by this diagnostic.

### 14.3 Latest exact reconciliation counts

The latest artifact records the following state counts:

| Inventory | Expected / checked | PASS | MISSING | NOT_CHECKED |
|---|---:|---:|---:|---:|
| Target tables | 39 | 28 | 11 | 0 |
| Expected columns | 50 | 2 | 48 | 0 |
| Expected named indexes | 23 | 0 | 23 | 0 |
| Expected foreign keys | 23 | 0 | 23 | 0 |
| Expected audit triggers | 24 | 0 | 24 | 0 |
| Migration-ledger entries | 18 | 0 | 18 | 0 |
| Orphan checks | 18 | 7 | 0 | 11 |
| Duplicate checks | 14 | 3 | 0 | 11 |

Additional evidence:

- Five open reverification queue records and two open monitoring alerts were observed. Their per-record mapping states are `NOT_CHECKED`; do not treat their existence or aggregate count as a completed identity reconciliation.
- Metadata visibility was complete, so the artifact's absent-object classifications are not downgraded to `UNKNOWN` for lack of metadata visibility.
- `registry_counts` reports 39 tables, 50 expected columns, 23 indexes, 23 foreign keys, 24 triggers, 18 migrations, 18 orphan checks and 14 duplicate checks.
- The 48 missing columns include expected columns on absent tables as recorded in the per-object evidence; they must not be conflated with the historical 13 missing columns on tables that existed in the earlier report.

### 14.4 Final status and interpretation

**Engineering / CI:** COMPLETE for the merged implementation and recorded disposable-engine acceptance.

**Protected production diagnostic:** COMPLETE; run #42965183303 succeeded and produced a checksum-verified sanitized evidence artifact.

**Production schema acceptance:** **NOT READY / NOT ACCEPTED.** The latest diagnostic explicitly reports missing production schema objects and migration-ledger entries, plus uncompleted orphan, duplicate and per-record mapping checks. A successful diagnostic run means the collector completed safely; it does not mean the production schema passed acceptance.

### 14.5 Required future recovery gates — no execution authorization

Before any production migration or repair is considered, a separate change-control/recovery plan must:

1. Preserve and review the latest evidence artifact and this report; confirm the exact deployed application revision and migration source hashes again.
2. Verify a current production backup and complete a successful restore rehearsal in an isolated environment. A backup checksum alone is not a restore test.
3. Reconcile all 18 migration source files, dependencies, current production objects, existing data and the five queue / two alert records. Resolve every `NOT_CHECKED` item with safe evidence; never infer a pass from a skipped check.
4. Produce a migration-by-migration execution order, expected DDL/data effects, conflict handling, preconditions, postconditions and explicit stop/rollback criteria. Identity backfills require deterministic mapping and preservation of historical records.
5. Validate the exact recovery sequence and rerun/idempotency behavior on disposable MariaDB 11.8.9 matching production, with MySQL 8.4 regression coverage retained.
6. Obtain a separate explicit production change authorization and required protected-environment approval. Re-check the exact `main` SHA and production identity immediately before execution.
7. Execute only the separately approved plan, then rerun the protected read-only diagnostic and reconcile every expected object and data-integrity check before declaring production acceptance.

Until all gates pass, do not run migrations, DDL, DML, identity backfills, data cleanup, production repairs or deployments under this closure record. This report update itself is documentation-only and does not authorize any production write.

### 14.6 Closure decision

**Phase 1C engineering implementation and protected diagnostic delivery are closed for this milestone. Production schema remediation and final production acceptance remain open, separately gated work.** Test 1A / Test 1B engineering acceptance is not being restarted by this documentation update; the remaining work is production recovery/readiness, not another repetition of completed disposable Test B acceptance.
