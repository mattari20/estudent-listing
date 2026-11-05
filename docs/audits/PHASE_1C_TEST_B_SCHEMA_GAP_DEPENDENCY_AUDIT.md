# Phase 1C Test B — Production Schema-Gap and Migration Dependency Audit

**Audit date:** 2026-11-05  
**Repository:** `mattari20/estudent-listing`  
**Source revision audited:** `745c2c9982641b626ccf435dd126a197fa337f0f`  
**Production evidence:** Read-only reconciliation workflow run [#42018256678](https://github.com/mattari20/estudent-listing/actions/runs/42018256678)  
**Scope:** Test B / Phase 1C migration-ledger and production schema reconciliation. This is an evidence and source-code audit only.

## 1. Executive decision

**Test B diagnostics: EXECUTION PASS. Production schema acceptance: FAIL / BLOCKED.**

The corrected read-only workflow completed against the configured production connection and checked seven selected migration ledger entries plus their required metadata. It reported zero of the seven migrations recorded and missing required schema objects. It explicitly rolled back its read-only transaction and reported:

- `production_database_changed=false`
- `production_application_files_changed=false`
- `migrations_executed=false`
- `ddl_dml_executed=false`
- `persistent_report_written=false`

The successful workflow conclusion means the diagnostic ran to completion; it does **not** mean the production schema passed.

## 2. Exact observed production evidence

Workflow run [#42018256678](https://github.com/mattari20/estudent-listing/actions/runs/42018256678), run number 2, completed successfully on source revision `745c2c9982641b626ccf435dd126a197fa337f0f`.

Observed summary:

| Measure | Result |
|---|---:|
| Selected migrations checked | 7 |
| Selected migration ledger rows recorded | 0 |
| Migrations with failed reconciliation status | 7 |
| Required append-only audit triggers present | 0 of 24 |
| Monitoring checkpoint foreign keys present | 0 of 7 |
| `monitoring_policy_runs.heartbeat_at` | Missing |

All seven selected migrations were reported `NOT_RECORDED_AND_SCHEMA_INCOMPLETE`:

1. `2026_10_25_000001_create_structured_enrichment_promotion_events_table`
2. `2026_11_03_000002_create_governed_execution_runs_table`
3. `2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency`
4. `2026_11_04_000010_create_monitoring_policy_run_checkpoints`
5. `2026_11_04_000012_add_identity_conflict_persistence_keys_and_events`
6. `2026_11_04_000014_enforce_append_only_audit_retention`
7. `2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat`

Specific missing objects reported include:

- `structured_enrichment_promotion_events`
- `governed_execution_runs` and `governed_execution_run_events`
- `monitoring_alert_identities` and `security_audit_events.monitoring_idempotency_key`
- `monitoring_policy_runs` and `monitoring_policy_run_sources`
- `import_conflict_events`, `identity_match_candidates.identity_key`, `identity_resolution_decisions.decision_identity_key`, and `import_conflicts.identity_key`
- All 24 expected append-only / identity-decision triggers
- `monitoring_policy_runs.heartbeat_at` and all seven named monitoring foreign keys

The workflow read only the seven selected rows from Laravel's `migrations` ledger and metadata from MySQL `information_schema`. It did not read application-table rows. No application data values or counts were inspected by this workflow.

## 3. Migration dependency and data-impact audit

This section is derived from the seven migration source files at the audited revision. It identifies prerequisites and migration behavior; it is not evidence that those prerequisites currently exist in production.

### 3.1 Migration 000001 — structured enrichment promotion events

Creates `structured_enrichment_promotion_events`.

Prerequisites visible in source:
- `enrichment_import_rows`
- `import_batches`

Adds:
- A foreign key to each parent table, initially with cascade-on-delete behavior.
- Indexes for enrichment row/time, canonical identity and batch/event.

Dependency:
- Migration 000014 later changes both foreign keys to restrictive delete behavior and installs append-only triggers for this table.

Preflight:
- Confirm parent tables and referenced key types exist.
- Before 000014, check for orphan event references. Do not delete or rewrite history to make the migration pass.

### 3.2 Migration 000002 — governed execution runs

Creates:
- `governed_execution_runs`
- `governed_execution_run_events`

The events table references the run UUID and initially uses cascade-on-delete behavior.

Dependency:
- Migration 000014 changes the event-to-run foreign key to restrictive behavior and installs append-only update/delete triggers on the events table.
- Migration 000015 references `governed_execution_runs` from monitoring checkpoints.

Preflight:
- Confirm both tables and the UUID key relationship.
- Migration 000014 checks for orphan governed-run events before changing retention behavior.

### 3.3 Migration 000009 — monitoring alert identities and audit idempotency

Creates `monitoring_alert_identities`, copies each currently open alert's `dedupe_key` and alert ID into that identity table in chunks, and adds nullable unique `security_audit_events.monitoring_idempotency_key`.

Important behavior and risks:
- It checks for duplicate `dedupe_key` values among open `monitoring_alerts` and throws if any exist.
- It performs a data backfill from existing open alerts. This is a DML operation.
- The new audit idempotency column is nullable and unique.
- Its `down()` removes the unique index and column and drops `monitoring_alert_identities`, which would discard the identity mapping.

Dependencies:
- `monitoring_alerts`
- `security_audit_events`

Follow-up:
- Migration 000015 adds `mai_active_alert_fk` from `monitoring_alert_identities.active_alert_id` to `monitoring_alerts.id`.

Required preflight before any separately authorized execution:
- Read-only duplicate-open-alert check.
- Read-only inventory of the open alerts that would be copied, including uniqueness of their dedupe keys and whether an identity row already exists.
- Read-only orphan check for any pre-existing identity rows, if the table exists.
- Confirm the audit column is absent or already consistent.
- Preserve all existing alerts and security audit events. No delete, reset, merge, or recreation is permitted as a shortcut.

### 3.4 Migration 000010 — monitoring policy run checkpoints

Creates:
- `monitoring_policy_runs`
- `monitoring_policy_run_sources`

The initial migration creates indexes and a unique key on (monitoring policy run, source), but does not add the foreign keys; those are added by migration 000015.

Dependencies:
- Migration 000015 expects `source_monitoring_policies`, `governed_execution_runs`, `sources`, `source_retrieval_runs`, `source_change_observations`, and `monitoring_alert_identities` / `monitoring_alerts` to be available as applicable.

Preflight:
- Confirm every parent table and referenced column exists and has a compatible type.
- Inspect existing checkpoint rows for orphans before 000015.
- Do not create synthetic monitoring runs or sources in production.

### 3.5 Migration 000012 — identity conflict persistence keys and events

Adds nullable unique identity keys to:
- `identity_match_candidates.identity_key`
- `identity_resolution_decisions.decision_identity_key`
- `import_conflicts.identity_key`

Creates `import_conflict_events`, with a foreign key to `import_conflicts` and a unique event idempotency key.

Important behavior and risks:
- The identity-key columns are nullable and unique; this migration does not backfill them.
- The event table initially cascades on conflict deletion.
- Migration 000014 later makes the event-to-conflict relationship restrictive and adds append-only triggers for conflict events.
- Migration 000014's identity-decision trigger permits only a one-time NULL-to-non-NULL identity-key enrichment while protecting the remaining decision fields.
- Its `down()` drops the event table and removes all three identity-key columns; this can discard persisted conflict history and identity keys.

Preflight:
- Inventory current identity-key NULLs and duplicate non-NULL keys separately for each affected table.
- Confirm referenced conflict rows and key/index definitions.
- Do not backfill, resolve, or alter identity records as part of a schema diagnostic.

### 3.6 Migration 000014 — append-only audit retention

This is a high-impact integrity migration. It does more than install 24 triggers.

MySQL source behavior:
1. Checks for orphan references across 11 audit-history relationships.
2. Replaces existing foreign keys with restrictive delete behavior for retained history.
3. Installs 22 update/delete triggers across 11 append-only event/history tables.
4. Installs two identity-decision guards, bringing the expected trigger total to 24.

The 11 parent relationships checked in the migration source are:
- identity resolution decisions → import rows
- verification decisions → assertions
- import reconciliation events → import batches
- import promotion decisions → import rows
- import promotion events → import rows
- import promotion events → import batches
- reverification events → data reverification queue
- structured enrichment promotion events → enrichment import rows
- structured enrichment promotion events → import batches
- import conflict events → import conflicts
- governed execution run events → governed execution runs

The migration refuses to proceed if it finds orphan history. It does not provide an automatic cleanup path. Its MySQL `down()` removes the guards and reverts restrictive foreign keys to cascade behavior, weakening retention protection.

Preflight required:
- Verify every participating table and current foreign-key definition.
- Run a read-only orphan count / bounded evidence query for each of the 11 relationships.
- Confirm all expected trigger names and definitions, not only their count.
- Review table engines and MySQL permissions/compatibility for trigger and constraint operations.
- Obtain explicit approval for the integrity-policy change and a verified backup/restore plan before execution.

**Do not run this migration until all 11 orphan checks are clean and the dependency migrations are reconciled. Do not use rollback as a routine recovery mechanism.**

### 3.7 Migration 000015 — monitoring checkpoint foreign keys and heartbeat

This migration:
1. Checks seven possible orphan relationships.
2. Adds `monitoring_policy_runs.heartbeat_at`.
3. Adds seven named foreign keys.
4. Backfills existing NULL heartbeat values from `started_at`.

The seven foreign keys are:
- `mp_run_policy_fk`
- `mp_run_exec_fk`
- `mp_source_run_fk`
- `mp_source_source_fk`
- `mp_source_retrieval_fk`
- `mp_source_observation_fk`
- `mai_active_alert_fk`

The seven orphan checks cover:
- monitoring policy runs without a policy
- monitoring policy runs without a governed execution run (when execution_run_id is non-NULL)
- source checkpoints without a policy run
- source checkpoints without a source
- source checkpoints without a retrieval run (when retrieval_run_id is non-NULL)
- source checkpoints without an observation (when observation_id is non-NULL)
- alert identities without an active alert (when active_alert_id is non-NULL)

The heartbeat backfill is an UPDATE and therefore a production data mutation. The migration's `down()` removes the seven FKs and drops `heartbeat_at`.

Preflight:
- Confirm migrations 000009, 000010 and 000002 have been reconciled.
- Run all seven orphan checks read-only.
- Record the number of existing checkpoint rows and NULL heartbeat values using an explicitly approved aggregate-only query.
- Confirm that heartbeat backfill from `started_at` is acceptable for every affected row.
- Do not run until separately authorized.

## 4. Proposed dependency order

The source-derived dependency order for these seven migrations is:

1. `2026_10_25_000001` — structured enrichment promotion events
2. `2026_11_03_000002` — governed execution runs and events
3. `2026_11_04_000009` — alert identity mapping and audit idempotency
4. `2026_11_04_000010` — monitoring policy checkpoint tables
5. `2026_11_04_000012` — identity conflict keys and conflict events
6. `2026_11_04_000014` — append-only audit retention and restrictive FKs
7. `2026_11_04_000015` — monitoring checkpoint heartbeat and FKs

This is the chronological order represented by the migration filenames and the direct dependencies visible in source. It is **not** an instruction to execute the migrations. Earlier prerequisite migrations and pre-existing parent tables must also be verified before any execution.

## 5. Production safety and authorization boundary

The diagnostic run was read-only and did not change production data or files. This audit does not authorize production modifications.

The following operations remain prohibited until separately authorized:
- running any production migration;
- DDL or DML, including the backfills in 000009 and 000015;
- altering, deleting, recreating or deduplicating alerts, audit events, conflicts, identity decisions or import history;
- installing or dropping triggers / foreign keys;
- deployment, source qualification, extraction, staging, approvals, imports, promotion or publication.

The previous Test B evidence also referenced two existing open monitoring alerts and six security audit events from an earlier inspection. This current reconciliation did not reread those records. They must be preserved, and a fresh approved read-only preflight must establish their current state before migration planning is finalized.

## 6. Remaining Test B completion gates

| Gate | Status | Evidence needed |
|---|---|---|
| Corrected read-only diagnostic executes | PASS | Run #42018256678 |
| Seven selected ledger entries recorded | FAIL | Currently 0 of 7 |
| Seven migration schemas reconciled | FAIL | Required objects missing |
| 24 audit triggers | FAIL | 0 of 24 reported |
| 11 audit-retention parent relationships | NOT VERIFIED IN RUN #2 | Dedicated read-only orphan / FK inspection |
| Seven monitoring checkpoint FKs | FAIL | 0 of 7 reported |
| Heartbeat column | FAIL | Missing |
| 000009 duplicate-open-alert and backfill preflight | NOT RUN | Read-only aggregate / duplicate checks |
| 000012 identity-key and conflict preflight | NOT RUN | NULL / duplicate / orphan inventory |
| 000014 eleven orphan checks | NOT RUN | Read-only preflight for every relationship |
| 000015 seven orphan checks and heartbeat impact | NOT RUN | Read-only preflight |
| Backup retention and restore recoverability | NOT VERIFIED | Backup inventory and controlled restore evidence |
| Production remediation authorization | NOT GRANTED BY THIS AUDIT | Separate explicit authorization required |

## 7. Next safe execution plan

1. Add a dedicated, bounded, read-only preflight diagnostic for the 11 audit relationships, seven monitoring relationships, duplicate open alert keys, existing identity-key NULL/duplicate summaries, and the heartbeat backfill impact. It must read metadata and aggregate counts only, must not retrieve sensitive row payloads, and must not write reports to production.
2. Test that diagnostic against an isolated MySQL fixture containing clean and deliberately failing cases, including orphan records and duplicate open alerts.
3. Review the report and reconcile prerequisite migrations / existing schema before drafting any production remediation sequence.
4. Obtain a separate authorization for production DDL/DML and a verified backup/restore plan. The current read-only authorization does not cover those actions.
5. Only after authorization, apply the approved migration sequence in a controlled window, one bounded step at a time, with a stop on any unexpected state.
6. Rerun Test B and verify ledger rows, tables, columns, indexes, all 24 trigger definitions, all 11 audit relationships, all seven monitoring FKs, and data integrity.
7. Keep Phase 1C open until production acceptance evidence is complete. Phase 2 remains on hold.

**Final audit conclusion:** The diagnostic implementation is now functioning and has exposed a substantial production schema gap. The source-code dependency audit is complete at this revision, but Test B is not passed and production remediation has not been performed or authorized.
