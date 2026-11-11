# Phase 1D.1 — Final Independent Review and Formal Closeout

**Repository:** `mattari20/estudent-listing`  
**Review baseline:** `main` at `679d8fddc8ebe44aaf725cd5efc26ef4a89094e9`  
**Inventory PR:** [#1067](https://github.com/mattari20/estudent-listing/pull/1067)  
**Inventory merge commit:** `679d8fddc8ebe44aaf725cd5efc26ef4a89094e9`  
**Inventory document:** [PHASE_1D_1_PRODUCTION_MIGRATION_RECONCILIATION_INVENTORY.md](PHASE_1D_1_PRODUCTION_MIGRATION_RECONCILIATION_INVENTORY.md)  
**Review date:** 2026-11-11

## 1. Executive summary

An independent repository-source review was performed against the merged Phase 1D.1 inventory and the actual migration definitions, migration registries, read-only diagnostic registry, JSON evidence schema, foreign-key/trigger acceptance registry and migration schema guard.

The review found **two documentation/source-mapping discrepancies** in the inventory:

1. The named-index row for `password_reset_tokens.PRIMARY` incorrectly listed `id`; migration #1 defines `email` as the primary key and does not define an `id` column.
2. The expected-column row for `monitoring_policy_runs.heartbeat_at` incorrectly attributed the column to migrations #13 and #18; the actual column is introduced only by migration #18. Migration #13 creates the monitoring policy/run/checkpoint tables but does not add this heartbeat column.

Both discrepancies have been corrected in the inventory on this closeout branch. The source registries themselves were not modified. These differences expose a source-registry consistency issue: the diagnostic's named-index registry still expects `PRIMARY(id)` for the password reset table, while the migration source and the corrected inventory say `PRIMARY(email)`. That registry mismatch is recorded for follow-up; it is not silently changed in this documentation-only task.

All 18 migration identifiers in the preflight registry were matched to their source files. The special data effects in #11, #12 and #18 were confirmed directly in source. Migration #17 defines 22 audit-table triggers plus two identity-decision guards, including the one permitted reconciliation-detach update contract. The inventory's six counts (39/50/23/23/24/18) were retained as the diagnostic registry's expected counts; the 23 named indexes are not an exhaustive enumeration of every index created by all table-creation migrations.

**Phase 1D.1 status: PENDING FORMAL CLOSEOUT PR MERGE AND REQUIRED CHECKS.** The source review and documentation correction are complete on this branch, but this closeout document has not yet been merged. Production schema acceptance and production recovery readiness are explicitly not established.

## 2. PR #1067 and baseline verification

| Item | Verified result |
|---|---|
| Latest `main` at review start | `679d8fddc8ebe44aaf725cd5efc26ef4a89094e9` |
| PR #1067 | Closed and merged |
| Merge time | 2026-11-11 06:27:30 UTC |
| Merge commit | `679d8fddc8ebe44aaf725cd5efc26ef4a89094e9` |
| Inventory path on main | Present |
| PR #1067 changed files | One inventory document |
| PR #1067 additions/deletions | 428 additions / 0 deletions |
| Merge-commit checks | Four completed checks: test, command, test, build — all success |

The verified inventory merge commit is the baseline for this closeout branch. The closeout branch is created from that exact SHA. No prior inventory report, Phase 1C evidence artifact or historical audit document was overwritten.

## 3. Independent review methodology

The review used the repository's current `main` tree and retrieved the complete content of the following authoritative sources:

- [All-18 migration preflight registry](../../scripts/diagnostics/phase1c-test1b-consolidated-all18-preflight.php)
- [Read-only diagnostic and object registries](../../scripts/phase1c/readonly-schema-diagnostic.php)
- [Read-only evidence JSON schema](../../scripts/phase1c/readonly-diagnostic.schema.json)
- [FK and trigger acceptance registry](../../tests/Support/verify-phase1c-testb-migration-objects.php)
- [MigrationSchemaGuard](../../app/Support/Database/MigrationSchemaGuard.php)
- [Phase 1C closure report](PHASE_1C_SESSIONS_1_TO_4_SCHEMA_RECONCILIATION_2026-11-06.md)
- [Phase 1C all-18 migration source review](PHASE_1C_TEST_1B_ALL_18_MIGRATION_SOURCE_REVIEW_2026-11-06.md)
- All 18 actual PHP migration source files under `database/migrations/`.

For each migration, the source `up()` and `down()` paths were inspected for table/column/index/constraint operations, data writes or backfills, guards, preconditions and rollback/destructive behavior. Registry values were checked against actual definitions, not inferred from timestamps or names. The production artifact was treated only as an observation of the earlier read-only production snapshot; it was not re-executed or reconnected to.

The two source-review bundle filenames mentioned in the earlier implementation handoff are not tracked at the repository root on the verified `main` tree. The independent review therefore relied on the actual tracked source files and the tracked Phase 1C reports, not on absent bundle files.

## 4. Independent review of all 18 migrations

All 18 identifiers from the preflight registry resolve to existing migration files. The table below summarizes the reviewed source operations and the important `down()` effect. It is a review index, not a production execution plan.

| # | Migration source | `up()` source review | `down()` effect / operational caution |
|---:|---|---|---|
| 1 | [2026_10_17_000001_create_password_reset_tokens_table.php](../../database/migrations/2026_10_17_000001_create_password_reset_tokens_table.php) | Guarded table creation. Columns: `email` primary key, `token`, nullable `created_at`. | Drops the entire `password_reset_tokens` table and its contents. Source defines PRIMARY(email); diagnostic index registry says PRIMARY(id), a confirmed registry mismatch. |
| 2 | [2026_10_17_000002_add_must_change_password_to_users_table.php](../../database/migrations/2026_10_17_000002_add_must_change_password_to_users_table.php) | Adds `users.must_change_password` as boolean/TINYINT(1) NOT NULL DEFAULT 0 when absent; if present, checks exact MySQL type/nullability/default. | Drops the column and its values. Existing incompatible metadata causes a fail-closed exception. |
| 3 | [2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php](../../database/migrations/2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php) | Adds unsigned INT `import_batches.duplicate_rows` DEFAULT 0 when absent; validates existing MySQL metadata. | Drops the column and any values held in it. |
| 4 | [2026_10_25_000001_create_structured_enrichment_promotion_events_table.php](../../database/migrations/2026_10_25_000001_create_structured_enrichment_promotion_events_table.php) | Guarded creation of structured enrichment promotion event history, including row and batch FKs initially declared CASCADE, snapshots/metadata and three table indexes. | Drops the event table and its history. Migration #17 later replaces the two retention FKs with RESTRICT contracts. |
| 5 | [2026_11_03_000001_create_governed_execution_leases_table.php](../../database/migrations/2026_11_03_000001_create_governed_execution_leases_table.php) | Creates governed execution leases with unique task key and expiry index. | Drops the lease table and active lease records. |
| 6 | [2026_11_03_000002_create_governed_execution_runs_table.php](../../database/migrations/2026_11_03_000002_create_governed_execution_runs_table.php) | Creates governed execution runs and run-event history; run events reference runs with an initial CASCADE FK. | Drops run-event history and then run records. Migration #17 later replaces the run-event FK with RESTRICT. |
| 7 | [2026_11_03_000003_add_source_retrieval_idempotency_key.php](../../database/migrations/2026_11_03_000003_add_source_retrieval_idempotency_key.php) | Uses `MigrationSchemaGuard::ensureNullableUniqueString` for a nullable VARCHAR(64) idempotency key and named unique index. | Drops the unique index and column, discarding stored idempotency values. |
| 8 | [2026_11_03_000004_add_snapshot_and_observation_idempotency.php](../../database/migrations/2026_11_03_000004_add_snapshot_and_observation_idempotency.php) | Adds snapshot capture identity and unique current-snapshot identity. Checks duplicate non-NULL observation snapshot IDs and exact existing index metadata before accepting/reconciling. | Drops the relevant unique indexes and capture-key column. Existing data values in the removed column are lost. |
| 9 | [2026_11_03_000005_add_discovery_outcome_idempotency_key.php](../../database/migrations/2026_11_03_000005_add_discovery_outcome_idempotency_key.php) | Uses the schema guard to add nullable unique VARCHAR(64) idempotency key. | Drops the unique index and column. |
| 10 | [2026_11_03_000006_add_change_event_source_link_identity.php](../../database/migrations/2026_11_03_000006_add_change_event_source_link_identity.php) | Uses the schema guard to add nullable unique unsigned BIGINT source-link identity. | Drops the unique index and column. |
| 11 | [2026_11_03_000007_create_data_reverification_queue_identities_table.php](../../database/migrations/2026_11_03_000007_create_data_reverification_queue_identities_table.php) | Checks duplicate open rows grouped by subject type, subject ID and attribute; creates/verifies identity table; chunks open source rows in batches of 500; derives SHA-256 identity key from those three fields; checks existing key/queue mapping conflicts and inserts missing mappings. | Drops the entire identity mapping table. This removes the generated mapping rows; it does not drop the source queue records. |
| 12 | [2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency.php](../../database/migrations/2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency.php) | Checks duplicate open alerts grouped by dedupe key; creates/verifies identity table; chunks open alerts by 500; maps dedupe key to active alert ID; rejects conflicting key/alert mappings; adds nullable unique audit idempotency key. | Drops audit idempotency index/column and the identity table. Generated mapping rows and the added column values are removed; source alerts remain. |
| 13 | [2026_11_04_000010_create_monitoring_policy_run_checkpoints.php](../../database/migrations/2026_11_04_000010_create_monitoring_policy_run_checkpoints.php) | Creates monitoring policy runs and source checkpoint tables, with run/source uniqueness and source/status indexes. It does not add `heartbeat_at`. | Drops source checkpoints and monitoring policy runs, including their records. |
| 14 | [2026_11_04_000011_add_discovery_candidate_persistence_identity_keys.php](../../database/migrations/2026_11_04_000011_add_discovery_candidate_persistence_identity_keys.php) | Adds nullable unique 64-character identity keys to import batches, evidence, import rows and assertions through the schema guard. | Drops the four unique indexes and identity columns; persisted identity values are lost. |
| 15 | [2026_11_04_000012_add_identity_conflict_persistence_keys_and_events.php](../../database/migrations/2026_11_04_000012_add_identity_conflict_persistence_keys_and_events.php) | Adds nullable unique identity keys to candidates, decisions and conflicts; creates conflict-event history with initial CASCADE FK, unique idempotency key and conflict/time index. | Drops conflict-event history and then removes the identity-key indexes/columns. This is destructive to the new event history and identity values. |
| 16 | [2026_11_04_000013_create_source_snapshot_reconciliation_requests_table.php](../../database/migrations/2026_11_04_000013_create_source_snapshot_reconciliation_requests_table.php) | Guarded creation of reconciliation request table with five FKs: three CASCADE and two SET NULL; unique identity key and two supporting indexes. | Drops the entire reconciliation-request table and its records. |
| 17 | [2026_11_04_000014_enforce_append_only_audit_retention.php](../../database/migrations/2026_11_04_000014_enforce_append_only_audit_retention.php) | Performs eleven orphan preconditions, adds eleven RESTRICT/RESTRICT retention FKs, installs 22 BEFORE UPDATE/DELETE audit triggers and two identity-decision guards. Reconciliation-event UPDATE guard permits only the defined nullable `import_row_id` detach while requiring the other values unchanged. | Drops MySQL trigger guards and parent guards, then changes the eleven retention FKs back to CASCADE. This weakens retention enforcement and restores cascading delete behavior; rollback is not a harmless metadata reversal. |
| 18 | [2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat.php](../../database/migrations/2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat.php) | Performs seven orphan preconditions; adds seven monitoring FKs with RESTRICT or SET NULL actions; adds nullable TIMESTAMP heartbeat when absent; updates every NULL heartbeat to `started_at`. | Drops the seven FKs and heartbeat column. The heartbeat values are removed with the column; the source `started_at` remains. |

### Migration review findings

- The source preflight registry contains exactly 18 unique identifiers and every identifier resolves to a tracked migration source file.
- The inventory's #11 description is directionally correct but should be read as a real data backfill, not merely a schema/table addition. It writes identity rows for open queue records.
- The inventory's #12 description is likewise a real data backfill for open monitoring alerts.
- #18's heartbeat update is a DML operation inside the migration: `UPDATE monitoring_policy_runs SET heartbeat_at = started_at WHERE heartbeat_at IS NULL`.
- #17 is not only trigger installation. It performs eleven orphan checks, adds eleven restrictive FKs and installs the trigger guards. Its `down()` changes the FK delete semantics back to CASCADE and removes guard protection.
- Several `down()` methods drop tables, columns, identity values or event history. A down migration must not be treated as a safe rollback strategy for production without a separate data-preservation plan.
- MySQL-family DDL implicit commits mean that migration-level transaction assumptions cannot be used as proof that a partially executed migration is atomically reversible.
- Source existence and a zero production ledger count are distinct facts: the source files exist; the production artifact reports no matching ledger rows for the 18 target migrations.

## 5. Six inventory-category review results

| Inventory category | Expected registry count | Independent review |
|---|---:|---|
| Tables | 39 | Count and names cross-checked against the read-only diagnostic registry and source associations. Table presence is not equivalent to full table-definition equivalence. |
| Columns | 50 | Count and expected column/table names cross-checked against the diagnostic registry. Corrected heartbeat source mapping from #13/#18 to #18 only. |
| Named indexes | 23 | Count and named entries cross-checked against the diagnostic registry. Corrected `password_reset_tokens.PRIMARY` column from `id` to `email` in the inventory because migration #1 defines PRIMARY(email). |
| Foreign keys | 23 | Names, source/target table and column, ordinal, and referential actions cross-checked against the acceptance registry and source migrations. #4/#6/#12 initially declare CASCADE relationships that #17 later changes to RESTRICT for the listed retention contracts. #16 declares three CASCADE and two SET NULL; #18 declares four RESTRICT and three SET NULL. All expected UPDATE actions are RESTRICT. |
| Triggers | 24 | Confirmed from #17 and diagnostic/acceptance registry: 11 audit tables × UPDATE/DELETE = 22, plus two identity-decision guards. The reconciliation-detach UPDATE exception is preserved. |
| Migration-ledger entries | 18 | All 18 registry identifiers and corresponding source files confirmed. Production evidence shows zero matching ledger rows for each target. |

### Index registry inconsistency

The actual migration #1 defines `password_reset_tokens.email` as the primary key. The diagnostic registry at `scripts/phase1c/readonly-schema-diagnostic.php` line 198 expects `PRIMARY(id)`. The inventory now reports PRIMARY(email), based on the migration source. The diagnostic registry and its historical evidence remain unchanged in this documentation closeout. A follow-up must decide whether the registry expectation is wrong or whether an intended schema design change is missing; no migration or registry code is changed here.

The 23 expected indexes are the explicit named-index reconciliation registry. They are not a complete count of every index created by every migration's table definitions. Table-creation migrations #4, #5, #6, #13, #15 and #16 define additional indexes/primary keys outside that 23-entry diagnostic subset. Do not interpret the number 23 as the total number of all indexes introduced by the 18 migration files.

## 6. Special risk review

### A. Five open reverification records — protected

Migration #11:
- Checks for duplicate open records grouped by `subject_type`, `subject_id` and `attribute`; it throws before proceeding if duplicates exist.
- Creates the identity key as SHA-256 of the JSON tuple `[subject_type, (int) subject_id, attribute]`.
- Backfills open queue rows in 500-row chunks.
- Rejects a key that maps to conflicting source values and rejects an existing queue-ID mapping with a different identity key.
- Inserts identity rows; it does not rewrite the source queue rows.

The production artifact reported five open records but did not establish individual mappings. They remain protected and individually `NOT_CHECKED`. No production rows were inspected or changed in this review.

### B. Two open monitoring alerts — protected

Migration #12:
- Checks duplicate open alerts grouped by `dedupe_key`.
- Backfills open alerts in 500-row chunks, mapping each `dedupe_key` to `active_alert_id`.
- Rejects an existing dedupe key pointing to another alert and rejects an alert ID already associated with another key.
- Adds a separate nullable unique `security_audit_events.monitoring_idempotency_key`.

The production artifact reported two open alerts but did not establish their individual mappings. They remain protected and individually `NOT_CHECKED`. No production alerts or history were inspected or changed.

### C. Heartbeat backfill

Migration #18 adds nullable `monitoring_policy_runs.heartbeat_at` if absent, checks existing MySQL metadata when present, and then updates all rows where heartbeat is NULL to `started_at`. The exact production affected-row count and value distribution are not in the sanitized evidence. This impact remains unknown and requires a separately authorized Phase 1D.2 investigation.

### D. Audit retention and triggers

Migration #17 defines eleven audit tables and creates 22 triggers (one BEFORE UPDATE and one BEFORE DELETE per table), plus:
- `identity_decision_update_guard`: allows only one-time transition of `decision_identity_key` from NULL to non-NULL while requiring all other guarded columns unchanged.
- `identity_decision_delete_guard`: rejects deletes.

For `import_reconciliation_events`, the UPDATE trigger permits only `import_row_id` changing from non-NULL to NULL while all listed other columns remain unchanged. This special reconciliation-detach contract must be preserved exactly. The 24 count is 22 audit-table guards plus two identity-decision guards, not 24 audit pairs.

### E. Foreign-key contracts

The 23 constraints were cross-checked across:
- #17: eleven retention FKs with RESTRICT delete and RESTRICT update;
- #16: five reconciliation request FKs (three CASCADE delete, two SET NULL delete; RESTRICT update);
- #18: seven monitoring FKs (four RESTRICT delete, three SET NULL delete; RESTRICT update).

The initial CASCADE FKs created by #4, #6 and #15 are replaced/validated by #17 retention FK operations where applicable. Do not weaken or casually alter the source/target mapping or CASCADE/SET NULL/RESTRICT behavior.

## 7. Production evidence observed versus checks not performed

The production evidence run is the prior protected read-only run #42965183303. It reported MariaDB 11.8.9, complete metadata visibility, a READ ONLY transaction and ROLLED_BACK outcome. The artifact's archive and evidence checksums were verified and recorded in the merged inventory.

| Production observation | Reported result |
|---|---:|
| Expected tables | 39; 28 present, 11 missing |
| Expected columns | 50; 2 present, 48 missing |
| Expected named indexes | 23; 0 present, 23 missing |
| Expected FKs | 23; 0 present, 23 missing |
| Expected triggers | 24; 0 present, 24 missing |
| Target migration ledger | 18; 0 matching entries, 18 missing |
| Orphan checks | 18 total; 7 passed, 11 not checked |
| Duplicate checks | 14 total; 3 passed, 11 not checked |
| Open reverification records | 5; individual identity mappings not checked |
| Open monitoring alerts | 2; individual identity mappings not checked |

A successful diagnostic workflow is evidence of successful read-only collection, not production schema acceptance. This closeout does not repeat the diagnostic or connect to production.

## 8. Outstanding production-only unknowns transferred to Phase 1D.2

The following are not documentation errors and cannot be resolved from repository source or aggregate evidence:

1. **Eleven skipped orphan checks:** obtain approved, sanitized per-relationship results once prerequisite objects are available.
2. **Eleven skipped duplicate checks:** obtain approved, sanitized per-key duplicate results before any unique constraint work.
3. **Five queue identity mappings:** establish whether each open record has exactly one deterministic mapping; preserve source records and event history.
4. **Two alert identity mappings:** establish exact dedupe-key-to-alert mappings; preserve both alerts and history.
5. **Full existing table equivalence:** compare every present production table's complete definition, not only the 50 target columns.
6. **Full migration ledger:** inspect the complete ledger for unrelated, duplicate, unexpected or anomalous entries outside the 18 target identifiers.
7. **Production data profile:** row counts, NULL distributions, duplicate distributions, index selectivity and table sizes are not present in this artifact.
8. **Heartbeat impact:** quantify rows for which #18 would populate `heartbeat_at` from `started_at`, including NULL `started_at` cases.
9. **DDL locks and duration:** estimate per-statement metadata locks, online DDL support, replication impact and safe stop points using an approved environment.
10. **Backup/restore readiness:** intentionally deferred; no backup or restore work was done.
11. **Index registry conflict:** reconcile diagnostic expected PRIMARY(id) versus migration-defined PRIMARY(email) for `password_reset_tokens` in a separately reviewed source/registry change.

These are Phase 1D.2 investigation requirements. None is silently marked PASS.

## 9. Corrections made

| Inventory location | Previous value | Corrected value | Authoritative source |
|---|---|---|---|
| §4.3, index #1 | `password_reset_tokens.PRIMARY(id)` | `password_reset_tokens.PRIMARY(email)` | Migration #1: `$table->string('email')->primary()`; no `id` column is defined. |
| §4.2, column #29 | `monitoring_policy_runs.heartbeat_at` source #13 / #18 | Source #18 only | Migration #18 adds `heartbeat_at`; migration #13 creates monitoring policy run/checkpoint tables without that column. |

No migration, diagnostic, registry, application, test or production file was changed. The historical Phase 1C report and production evidence remain unchanged.

## 10. Safety and scope confirmation

- Repository documentation review and documentation corrections only.
- No production database connection or SQL execution.
- No production migrations, DDL, DML, INSERT, UPDATE, DELETE, repairs or backfills.
- No production workflow dispatch.
- No backup, restore, deployment or production change authorization.
- No application code or migration implementation changes.
- No Test 1A or Test 1B restart.
- Five open reverification records and two open monitoring alerts remain protected.
- No production schema acceptance or recovery readiness is claimed.

## 11. Final Phase 1D.1 acceptance checklist

- [x] PR #1067 verified merged at `679d8fddc8ebe44aaf725cd5efc26ef4a89094e9`.
- [x] Merged inventory read in full.
- [x] All 18 migration source files matched to the preflight registry and inspected.
- [x] Migration `up()` operations, data writes/backfills and important `down()` effects reviewed.
- [x] All six inventory category counts and registries cross-checked.
- [x] Special #11, #12 and #18 data-impact behavior verified directly from source.
- [x] #17 trigger count and reconciliation-detach contract verified.
- [x] All 23 FK contracts cross-checked against registry and source migrations.
- [x] Two verified documentation discrepancies corrected on this branch.
- [x] Production-only unknowns explicitly transferred to Phase 1D.2.
- [x] Production remained untouched.
- [ ] Closeout PR created, reviewed, required checks passed and merged to `main`.

**Phase status:** Independent source review and corrections are complete. Formal Phase 1D.1 closeout remains pending this document's PR review, required CI checks and merge. Even after closeout, production acceptance and recovery execution remain separate, unapproved work.

## 12. Recommended next Phase 1D.2 task

Start **Phase 1D.2 — Production Recovery Readiness Investigation (read-only planning and evidence design)**. First resolve the diagnostic registry conflict for `password_reset_tokens.PRIMARY`, then design the missing orphan/duplicate evidence, sanitized queue/alert identity mapping verification, full schema/ledger comparison and heartbeat impact measurement. Keep all work non-mutating. Backup/restore readiness must be a separate explicitly scoped workstream. Do not dispatch production workflows or execute migrations as part of the initial Phase 1D.2 investigation.

## Appendix — source files reviewed

- `scripts/diagnostics/phase1c-test1b-consolidated-all18-preflight.php`
- `scripts/phase1c/readonly-schema-diagnostic.php`
- `scripts/phase1c/readonly-diagnostic.schema.json`
- `tests/Support/verify-phase1c-testb-migration-objects.php`
- `app/Support/Database/MigrationSchemaGuard.php`
- `docs/audits/PHASE_1C_SESSIONS_1_TO_4_SCHEMA_RECONCILIATION_2026-11-06.md`
- `docs/audits/PHASE_1C_TEST_1B_ALL_18_MIGRATION_SOURCE_REVIEW_2026-11-06.md`
- `database/migrations/2026_10_17_000001_create_password_reset_tokens_table.php`
- `database/migrations/2026_10_17_000002_add_must_change_password_to_users_table.php`
- `database/migrations/2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php`
- `database/migrations/2026_10_25_000001_create_structured_enrichment_promotion_events_table.php`
- `database/migrations/2026_11_03_000001_create_governed_execution_leases_table.php`
- `database/migrations/2026_11_03_000002_create_governed_execution_runs_table.php`
- `database/migrations/2026_11_03_000003_add_source_retrieval_idempotency_key.php`
- `database/migrations/2026_11_03_000004_add_snapshot_and_observation_idempotency.php`
- `database/migrations/2026_11_03_000005_add_discovery_outcome_idempotency_key.php`
- `database/migrations/2026_11_03_000006_add_change_event_source_link_identity.php`
- `database/migrations/2026_11_03_000007_create_data_reverification_queue_identities_table.php`
- `database/migrations/2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency.php`
- `database/migrations/2026_11_04_000010_create_monitoring_policy_run_checkpoints.php`
- `database/migrations/2026_11_04_000011_add_discovery_candidate_persistence_identity_keys.php`
- `database/migrations/2026_11_04_000012_add_identity_conflict_persistence_keys_and_events.php`
- `database/migrations/2026_11_04_000013_create_source_snapshot_reconciliation_requests_table.php`
- `database/migrations/2026_11_04_000014_enforce_append_only_audit_retention.php`
- `database/migrations/2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat.php`
