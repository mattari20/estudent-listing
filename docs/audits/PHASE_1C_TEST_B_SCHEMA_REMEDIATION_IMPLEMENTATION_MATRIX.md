# Phase 1C Test B — 18-Migration Schema Remediation Implementation Matrix

**Baseline:** `db60b441d08d308430a4c8bc091f63a22784b493`  
**Scope:** Engineering remediation and isolated MySQL validation. Production DDL/DML is not performed by this PR.  
**Plan:** Two major PRs: (1) migration hardening and schema repair implementation; (2) integrated MySQL execution, partial-state recovery and production readiness validation.

## Execution principles

1. Do not create duplicate tables or parallel schema definitions. Correct the existing migration chain.
2. Treat a missing migration-ledger entry and a partially applied schema as separate facts. MySQL DDL may commit before Laravel records a migration.
3. For every expected object, distinguish absent, exact match, compatible partial state, and incompatible drift. Never silently skip a mismatched object.
4. Backfills must be deterministic, bounded, idempotent, and reconcile already-present target rows before writing.
5. Duplicate/orphan checks that cannot run because prerequisites are missing are `NOT_CHECKED`, never passes.
6. No production data, schema, migration ledger, files, triggers, workers, or protected GCU history are changed by engineering PRs.

## Migration inventory and remediation targets

| # | Migration | Objects / behavior | Dependencies and risks | Implementation requirement |
|---:|---|---|---|---|
| 1 | `2026_10_17_000001_create_password_reset_tokens_table` | Creates `password_reset_tokens` | A partially-created table can make retry fail | Exact table/column/index preflight; create only when absent; reject incompatible existing shape |
| 2 | `2026_10_17_000002_add_must_change_password_to_users_table` | Adds `users.must_change_password` | Existing column may have wrong type/default | Check column definition and default; add only if absent; fail on drift |
| 3 | `2026_10_23_000001_add_duplicate_rows_to_import_batches_table` | Adds `import_batches.duplicate_rows` default 0 | Existing guard checks only column presence | Strengthen exact definition check; preserve existing values |
| 4 | `2026_10_25_000001_create_structured_enrichment_promotion_events_table` | Creates event table, two parent FKs, three indexes | Requires enrichment rows and import batches; partial DDL retry | Exact object reconciliation; preserve restrictive/retention behavior expected by later migration 000014 |
| 5 | `2026_11_03_000001_create_governed_execution_leases_table` | Creates lease table, unique task key, expiry index | Unique identity and UUID definitions | Verify full table definition; safe retry after partial create |
| 6 | `2026_11_03_000002_create_governed_execution_runs_table` | Creates run and run-event tables plus FK | Two-table migration can partially apply | Reconcile each table, index and FK independently; dependency order |
| 7 | `2026_11_03_000003_add_source_retrieval_idempotency_key` | Adds nullable unique retrieval key | Existing duplicate non-NULL keys or partial unique index | Preflight duplicate values and exact index; add missing objects only |
| 8 | `2026_11_03_000004_add_snapshot_and_observation_idempotency` | Adds snapshot capture key and unique observation snapshot ID | Duplicate check currently groups NULL values too; two ALTERs can partially apply | Correct NULL semantics; preflight each column/index; preserve rows and stop on conflicting non-NULL identity |
| 9 | `2026_11_03_000005_add_discovery_outcome_idempotency_key` | Adds nullable unique discovery key | Existing column/index drift | Exact column/index check and safe retry |
| 10 | `2026_11_03_000006_add_change_event_source_link_identity` | Adds nullable source assertion link and unique index | Existing duplicate non-NULL links / partial ALTER | Preflight duplicates and independently reconcile column/index |
| 11 | `2026_11_03_000007_create_data_reverification_queue_identities_table` | Creates identity table and backfills open queue rows | Previously observed 5 open rows; retry may collide with partially copied rows | Reconcile expected identity rows by deterministic key; detect conflicting mappings; insert only missing exact mappings |
| 12 | `2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency` | Creates alert identity table, backfills open alerts, adds audit idempotency key | Previously observed 2 open alerts; table/backfill/column can partially apply | Reconcile key-to-alert mappings; add audit key column/index safely; reject mismatched mappings |
| 13 | `2026_11_04_000010_create_monitoring_policy_run_checkpoints` | Creates policy run and per-source checkpoint tables | Parent tables/ID types; two-table partial DDL | Verify each table independently and exact unique/secondary indexes |
| 14 | `2026_11_04_000011_add_discovery_candidate_persistence_identity_keys` | Adds four nullable unique identity columns | Four sequential ALTERs can partially apply | Independent exact column/index reconciliation; do not synthesize historical keys without a reviewed rule |
| 15 | `2026_11_04_000012_add_identity_conflict_persistence_keys_and_events` | Adds three nullable unique keys and conflict event table | Multiple ALTERs plus create/FK; partial DDL | Reconcile every column/index/table/FK; no unapproved backfill |
| 16 | `2026_11_04_000013_create_source_snapshot_reconciliation_requests_table` | Creates request table, five FKs and two indexes | Requires sources and source snapshots with compatible key types | Verify exact FK columns/rules and table/index shape before accepting an existing object |
| 17 | `2026_11_04_000014_enforce_append_only_audit_retention` | Replaces 11 FK delete rules and installs 24 MySQL triggers | Critical retained-history protection; orphan checks; current trigger helper only checks name existence | Preflight all 11 old FK definitions and all 24 trigger definitions; reject same-name wrong-body triggers; perform staged, observable changes; preserve the approved nullable identity-enrichment exception |
| 18 | `2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat` | Adds heartbeat, seven FKs, and fills NULL heartbeat from started_at | Critical; multiple ALTERs and DML backfill; seven orphan checks | Reconcile column/FKs independently; count and update only NULL heartbeat rows; validate no orphan relationships |

## Dependency order

1. Existing foundation tables: users, import batches/rows, evidence, assertions, source retrieval/snapshot/observation, discovery outcomes, change events, queue, monitoring alerts and audit tables.
2. Independent new tables: password reset tokens, structured enrichment events, execution leases, execution runs/events.
3. Idempotency columns and unique indexes on existing tables.
4. Queue/alert identity tables and deterministic backfills.
5. Monitoring checkpoint tables.
6. Discovery and conflict identity keys/events.
7. Snapshot reconciliation request table.
8. Audit retention FK replacement and append-only triggers.
9. Monitoring checkpoint FK installation and heartbeat backfill.

The final execution order must be generated from the actual migration timestamps and verified dependencies; do not reorder Laravel migration history casually. If dependency constraints require a code correction, document it and test the complete sequence.

## High-risk defects to address in PR 1

- Most create-table migrations use unconditional `Schema::create`; a prior partial MySQL DDL application can make retry fail.
- Several add-column migrations are unconditional; partial application can make retry fail.
- Migration 000003 has a column-existence guard but does not validate its definition or unique index.
- Migration 000004's duplicate query groups NULL values; NULL handling must match the unique-key semantics and actual column nullability.
- Migrations 000007 and 000009 perform copy backfills that can fail on retry if target rows already exist. A deterministic key alone is insufficient; existing target mappings must be checked for exact agreement.
- Migration 000014's MySQL trigger helper treats a trigger name as sufficient. Same-name triggers with wrong event/table/body must be reported as drift and stop execution.
- Migration 000014 replaces existing FKs using Laravel's inferred names in several places; exact current FK names and definitions must be verified before drop/replace.
- Migration 000015's heartbeat backfill is naturally idempotent only if restricted to NULL values; its column and seven FK operations need partial-state reconciliation.
- MySQL DDL is not generally transactionally reversible. A failed migration must resume from verified object state, not blindly rerun or rollback.

## PR delivery plan

### PR 1 — Migration remediation and partial-state safety

- Implement exact schema-object inspection helpers suitable for migration-time use.
- Harden all 18 pending migrations against absent/exact/partial/incompatible states.
- Make the 000007 and 000009 backfills deterministic and safe to resume.
- Correct NULL duplicate semantics in 000004.
- Verify/replace exact FK and trigger definitions in 000014/000015 with fail-closed drift behavior.
- Add focused migration contract tests for each migration family.

### PR 2 — Integrated isolated MySQL validation and recovery

- Run the complete migration chain on MySQL 8.4 from empty schema.
- Simulate partial application for every multi-object migration family and prove safe continuation.
- Test backfill replay, duplicates, conflicting mappings, orphan rejection, FK rules and trigger behavior.
- Verify exact schema metadata and exactly one migration-ledger entry per migration.
- Add workflow artifact/log summaries without production row payloads or secrets.
- Produce production readiness/backup/preflight gates and a precise operation manifest.

## Acceptance criteria

- All 18 migrations complete on a clean isolated MySQL database.
- Re-running the migration command does not duplicate objects or ledger entries.
- Simulated partial DDL can be safely reconciled or stops with a precise, non-destructive error.
- Existing rows remain unchanged except explicitly specified, counted, idempotent backfills.
- Backfill retries do not duplicate or remap identities.
- Every required column, index, FK and trigger matches its full expected definition.
- Orphan/duplicate checks are either evaluated and pass or explicitly block; none are silently skipped.
- All focused and integrated MySQL tests pass.
- Production backup and restore readiness are evidenced before production execution.
- Production migration is executed only through an approved controlled workflow after preflight; no dummy data and no protected GCU record changes.

## Production authorization boundary

The user has authorized grouped engineering implementation and isolated MySQL testing. Production migration is authorized **only after backup and preflight conditions are met**. Backup preparation is authorized. PR merging is authorized after all required checks pass. Production DDL/DML must remain behind the protected controlled workflow and explicit operation-specific approval gate; this matrix is not a blanket permission to bypass that gate.
