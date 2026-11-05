# Phase 1C Test 1B — Consolidated All-18 Migration Reconciliation Plan

**Prepared:** 2026-11-05  
**Baseline main:** `62943bbc04dfe45b6d9e3c6dacfc2b966aa54102`  
**Evidence baseline:** full ledger inventory, 39-table schema metadata inventory, and read-only preflight run [#42049836371](https://github.com/mattari20/estudent-listing/actions/runs/42049836371).  
**Purpose:** consolidate the source-code impact and production verification requirements for every one of the 18 pending migrations into one reviewable plan.

## 1. Decision and safety boundary

The 18 migration files are present in the deployed application directory according to the full source/deployment inventory. The production migration ledger records 79 of 97 expected migration names; 18 remain pending. The earlier metadata inventory defined a 39-table scope including `institutions` and found 11 missing tables. The structural audit run #42059232458 accidentally queried only 38 names because its script omitted `institutions`; its 27-present/11-missing counts are historical 38-scope counts and must not be treated as a complete 39-table result. Run #42049836371 used the effective Laravel MySQL connection, matched the same non-secret database fingerprint as the earlier inventories, and reported missing prerequisites across the selected high-impact migrations.

This document is a source-derived plan, not a production execution instruction. It does not authorize `php artisan migrate`, DDL, DML, trigger/FK changes, application writes, deployment, or data correction. The database's intentional lack of test/canonical data is not permission to seed it.

## 2. Migration-by-migration source reconciliation

| Migration | Source effect | Data / integrity impact | Required production preflight |
|---|---|---|---|
| `2026_10_17_000001` | Create `password_reset_tokens` with email primary key | New auth support table; no backfill in source | Table absent/present consistency; email/token/created_at types; existing auth consumers |
| `2026_10_17_000002` | Add `users.must_change_password`, default false | Existing users receive DB default; no explicit row update in migration source | Users table/column existence, type/default/nullability; application compatibility |
| `2026_10_23_000001` | Add `import_batches.duplicate_rows`, unsigned integer default 0, guarded by hasColumn | Existing rows receive default semantics; additive schema | Existing column/type/default and candidate_rows prerequisite; duplicate_rows partial-state check |
| `2026_10_25_000001` | Create structured enrichment promotion event table, two cascading FKs and three indexes | Creates append-only history table; later 000014 changes retention semantics | Parent table/key compatibility; partial table/index/FK state; 000014 orphan checks |
| `2026_11_03_000001` | Create governed execution leases; unique task_key and expiry index | New lease coordination state; no backfill | Table/column/index partial-state; task_key uniqueness and engine/type compatibility |
| `2026_11_03_000002` | Create governed execution runs and run events; event FK cascades on run deletion | New execution history; later 000014 makes run-event retention restrictive | Both tables, UUID key compatibility, FK/index definitions, 000014 orphan relationship |
| `2026_11_03_000003` | Add nullable unique `source_retrieval_runs.idempotency_key` | No backfill; nullable unique key | Existing column/index partial state; non-NULL duplicate count if column already exists |
| `2026_11_03_000004` | Add nullable unique snapshot capture_key and unique observation current_snapshot_id | Explicitly aborts if duplicate current_snapshot_id exists | Count duplicate current_snapshot_id groups; existing columns/indexes; snapshot and observation schema compatibility |
| `2026_11_03_000005` | Add nullable unique discovery outcome idempotency_key | No backfill | Table/column/index partial state; non-NULL duplicate count if already present |
| `2026_11_03_000006` | Add nullable source assertion link ID to change_events with unique index | No backfill; uniqueness enforced for non-NULL values | Table/column/index partial state; non-NULL duplicate count if column already exists |
| `2026_11_03_000007` | Create reverification identity table and copy every open queue item in chunks | DML backfill; aborts on duplicate open subject_type/subject_id/attribute | Duplicate-open identity groups, open-row count, partial target table, queue schema, exact backfill cardinality |
| `2026_11_04_000009` | Create monitoring alert identity table; copy open alerts; add nullable unique audit idempotency column | DML backfill of open alerts; aborts on duplicate open dedupe_key | Duplicate-open dedupe keys, exact open-alert count, pre-existing identity rows/column/index, preservation of alerts and audit events |
| `2026_11_04_000010` | Create monitoring policy runs and run-source checkpoints, including unique run/source pair | New monitoring checkpoint schema; no source backfill in this migration | Parent table/key types; table/index partial state; FK additions deferred to 000015 |
| `2026_11_04_000011` | Add nullable unique discovery identity keys to import_batches, evidence, import_rows and assertions | No backfill in source; four unique indexes | All four tables/columns/indexes; non-NULL duplicate groups if columns already exist; nullable semantics and existing data |
| `2026_11_04_000012` | Add nullable unique identity keys to candidates, decisions and conflicts; create conflict events with unique idempotency key and cascading FK | No identity-key backfill in source; creates event history capability; later 000014 restricts retention | Three key columns/indexes, conflict table/FK, non-NULL duplicate key groups, event-table partial state and orphan relationship |
| `2026_11_04_000013` | Create snapshot reconciliation requests with five FKs and unique identity_key | New reconciliation request records; no backfill | Source/snapshot parent key compatibility; five FK definitions and delete rules; unique index and partial table state |
| `2026_11_04_000014` | Install 24 append-only/identity guard triggers and replace 11 history relationships with restrictive delete FKs | High-impact retention policy change; no cleanup path; rollback weakens retention protection | All 11 orphan counts, all participating table/column/FK definitions, exact trigger definitions, engines/privileges, verified backup/restore |
| `2026_11_04_000015` | Add heartbeat_at; seven monitoring FKs; backfill NULL heartbeat from started_at | DDL + FK enforcement + DML heartbeat backfill | Seven orphan counts, exact FK definitions, checkpoint counts, NULL heartbeat count, started_at compatibility, prior 000002/000009/000010 readiness |

## 3. Cross-migration dependency groups

The source files imply the following dependency groups. These are planning groups, not authorization to run migrations in batches.

### Group A — additive account and import-batch schema
- 000001 password reset tokens
- 000002 user password-change flag
- 000001 (October 23) import batch duplicate count

Check existing schema first; these are comparatively narrow but still require partial-state and application-compatibility checks.

### Group B — source/discovery idempotency
- 000003 retrieval idempotency
- 000004 snapshot and observation idempotency
- 000005 discovery outcome idempotency
- 000006 change-event source-link identity
- 000011 discovery candidate identity keys

The principal risk is unique-index creation against pre-existing non-NULL values or duplicates. 000004 explicitly checks duplicate current_snapshot_id values. All duplicate checks must distinguish absent prerequisites from zero duplicates.

### Group C — backfilled identity tables
- 000007 reverification queue identities
- 000009 monitoring alert identities

These create new identity tables and copy existing open rows. They must have separately reported source row counts, duplicate groups, pre-existing target state and exact expected inserted-row counts. No production row should be deleted, merged, reset or recreated to make a migration pass.

### Group D — execution, monitoring and reconciliation structures
- 000001 (October 25) structured enrichment promotion events
- 000001 (November 3) governed execution leases
- 000002 governed execution runs/events
- 000010 monitoring policy runs/checkpoints
- 000012 identity conflict keys/events
- 000013 source snapshot reconciliation requests

Verify every table, column, key type, index, FK and delete rule. Later integrity migrations depend on these tables.

### Group E — integrity enforcement
- 000014 append-only audit retention
- 000015 monitoring checkpoint foreign keys and heartbeat

These are final enforcement steps only after all dependency groups and all required data checks pass. 000014 changes deletion behavior for retained history and installs triggers. 000015 adds seven FKs and performs a heartbeat update. Both require backup/restore proof and a separately approved execution window.

## 4. Consolidated read-only diagnostic added in this PR

New script: `scripts/diagnostics/phase1c-test1b-consolidated-all18-preflight.php`  
New workflow: `.github/workflows/phase-1c-test-1b-consolidated-all18-preflight.yml`

The workflow is manually dispatched with the operation-specific confirmation phrase and protected production-readonly GitHub Environment. It uses Laravel's cached config when present, otherwise Laravel's dotenv/database config path and URL parser. It prints only a non-secret database fingerprint.

It checks:
- the migration ledger for all 18 names;
- required table existence;
- expected migration columns and named indexes;
- duplicate groups for migrations 000004, 000007, 000009, 000011 and 000012;
- aggregate row counts that determine the 000007, 000009 and 000015 backfill exposure;
- all 11 000014 orphan relationships and all seven 000015 orphan relationships, each gated by table and column availability;
- the 24 expected audit triggers;
- the 11 retention and seven monitoring named foreign keys, plus the five 000013 snapshot-reconciliation FKs.

A check blocked by a missing table or column is reported as `NOT_CHECKED_PREREQUISITE_MISSING`, not counted as a pass. It reads no application row payloads and creates no persistent report or remote temporary script file.

**Diagnostic limitation:** this first consolidated pass inventories named indexes and FK existence. Final migration review must additionally compare complete column definitions, index column order/uniqueness, FK delete/update rules, trigger bodies, table engines and any manually created partial objects. A name match alone is not structural equivalence.

## 5. Four remaining closure gates

| Gate | Required evidence | Status |
|---|---|---|
| 1. Complete schema delta | All 18 migrations compared to production metadata, including exact types/defaults/indexes/FK rules/triggers | PENDING consolidated run and detailed diff |
| 2. Data safety | All duplicate, orphan and backfill checks evaluated; missing dependencies explicitly resolved | PARTIAL; earlier seven-migration checks contain NOT_CHECKED results |
| 3. Recovery | Dated backup inventory, retention policy and successful controlled restore proof | PENDING |
| 4. Controlled remediation proposal | Ordered migration plan, exact DDL/DML and row effects, stop conditions, rollback limits, maintenance window, post-checks | PENDING; no execution authorization |

## 6. Explicit production guardrails

- Do not run `php artisan migrate` as a shortcut.
- Do not create dummy or synthetic production data.
- Do not manually create tables, columns, indexes, foreign keys or triggers.
- Do not delete, reset, deduplicate, merge or rewrite alerts, audit events, import history, identity decisions or queue records.
- Do not deploy application files or alter production configuration as part of this diagnostic.
- A successful workflow run means the diagnostic executed; production schema acceptance requires every expected object and safety gate to pass.
- Any production DDL/DML/migration execution requires separate explicit authorization after this review package is complete.

## 7. Acceptance status

**Test 1B remains OPEN / BLOCKED.** The goal of this grouped PR is to replace fragmented checks with one comprehensive, read-only inventory across the entire 18-migration scope. It is not the production repair itself.
