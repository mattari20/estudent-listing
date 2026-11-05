# Phase 1C Test 1B — Production Schema Reconciliation

## Purpose and safety boundary

This report reconciles the latest read-only production preflight with the migration source files on `main`. It records observed differences and the next evidence required before any remediation is proposed.

**This report does not authorize production changes.** No migration, DDL, DML, trigger creation, application deployment, dummy data, or production file write was performed. The diagnostic ran in a read-only transaction and rolled it back.

## Evidence reviewed

- Repository: `mattari20/estudent-listing`
- Main revision inspected: `0038d0324b6cdcf60eda387095ced5ff9b86ce2a`
- Read-only preflight: [GitHub Actions run #9](https://github.com/mattari20/estudent-listing/actions/runs/42004271127)
- Workflow conclusion: `success` (the diagnostic executed successfully; this is not a schema-acceptance result)
- Diagnostic classification: `BLOCKED_MISSING_DEPENDENCIES_OR_ORPHANS`
- `partial_application_detected=false`
- Database fingerprint was emitted by the workflow to establish the effective connection target. The fingerprint is intentionally not copied into this report.

## Production evidence from run #9

### Required table inventory

The diagnostic checked 30 required tables. Seven were absent:

| Missing table | Source migration / dependency |
|---|---|
| `monitoring_policy_runs` | `2026_11_04_000010_create_monitoring_policy_run_checkpoints` |
| `monitoring_policy_run_sources` | `2026_11_04_000010_create_monitoring_policy_run_checkpoints` |
| `governed_execution_runs` | `2026_11_03_000002_create_governed_execution_runs_table` |
| `monitoring_alert_identities` | `2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency` |
| `structured_enrichment_promotion_events` | `2026_10_25_000001_create_structured_enrichment_promotion_events_table` |
| `import_conflict_events` | `2026_11_04_000012_add_identity_conflict_persistence_keys_and_events` |
| `governed_execution_run_events` | `2026_11_03_000002_create_governed_execution_runs_table` |

The remaining 23 of the 30 checked tables were present. This does not establish that their columns, indexes, key types, or constraints match the source migrations.

### Migration ledger

All seven migration ledger lookups returned zero rows:

| Migration | Ledger rows |
|---|---:|
| `2026_10_25_000001_create_structured_enrichment_promotion_events_table` | 0 |
| `2026_11_03_000002_create_governed_execution_runs_table` | 0 |
| `2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency` | 0 |
| `2026_11_04_000010_create_monitoring_policy_run_checkpoints` | 0 |
| `2026_11_04_000012_add_identity_conflict_persistence_keys_and_events` | 0 |
| `2026_11_04_000014_enforce_append_only_audit_retention` | 0 |
| `2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat` | 0 |

A missing ledger row is not proof that the migration is safe to rerun. Some migrations may have partially applied or their objects may have been created independently. Schema-object state must be reconciled before any execution decision.

### Existing aggregate data and migration preconditions

The database is not completely devoid of operational records:

- `monitoring_alerts` rows: 2
- Open monitoring alerts eligible for the 000009 identity-table copy: 2
- Duplicate open-alert `dedupe_key` groups: 0
- `security_audit_events` rows: 6
- `identity_resolution_decisions` rows: 0

The zero duplicate-key result satisfies only that one 000009 precondition at the time of the run. Migration 000009 would still copy two open alerts into the new identity table and add a unique nullable idempotency-key column to `security_audit_events`. That is a data/schema change and requires explicit approval.

### Orphan checks

Seven orphan checks were evaluated and returned zero:

- Identity decisions without import rows
- Verification decisions without assertions
- Reconciliation events without batches
- Promotion decisions without rows
- Promotion events without rows
- Promotion events without batches
- Reverification events without queue rows

The remaining four 000014 checks and all seven 000015 checks were reported as `NOT_CHECKED_DEPENDENCY_OR_COLUMN_MISSING`. They are not zero-orphan results and must not be represented as passing.

### Constraints, triggers, and heartbeat

| Object group | Expected | Exact expected objects found |
|---|---:|---:|
| MySQL append-only audit triggers | 24 | 0 |
| 000014 named audit RESTRICT foreign keys | 11 | 0 |
| 000015 monitoring foreign keys | 7 | 0 |
| `monitoring_policy_runs.heartbeat_at` | 1 column | Missing |

The zero foreign-key counts refer to the expected constraint names checked by the diagnostic. They do **not** mean that no foreign keys exist at all. The workflow separately returned existing foreign-key metadata for six audit/history tables; five additional tables were not inspected because they were missing. The existing definitions still need to be compared against the exact legacy constraints that migration 000014 intends to replace.

All 24 expected trigger names were absent. Trigger bodies therefore could not be compared in this run.

### Checks intentionally not completed

Because prerequisite schema objects were missing, the diagnostic did not evaluate:

- Duplicate identity-key values and unique-index shape for the three 000012 target tables.
- The 000015 null-heartbeat backfill count and `started_at` compatibility.
- Orphan relationships requiring missing 000009, 000010, 000012, or governed-run tables.
- Complete FK definition equivalence for all 11 000014 relationships.

These are **unknown / not checked**, not passes.

## Source-to-production reconciliation

The source migrations establish this dependency sequence:

1. **000001 — structured enrichment events:** requires `enrichment_import_rows` and `import_batches`; creates a history table with two foreign keys.
2. **000002 — governed execution runs:** creates the UUID-keyed run table and its event table, with the event table referencing the run UUID.
3. **000009 — monitoring alert identities:** requires `monitoring_alerts` and `security_audit_events`; rejects duplicate open alert keys; copies open alerts and adds a unique nullable audit idempotency key.
4. **000010 — monitoring checkpoints:** creates `monitoring_policy_runs` and `monitoring_policy_run_sources`; 000015 adds their foreign keys later.
5. **000012 — identity/conflict persistence:** adds three nullable unique identity-key columns and creates `import_conflict_events`.
6. **000014 — append-only audit retention:** checks 11 orphan relationships, replaces 11 existing FK definitions with RESTRICT relationships, and installs 24 MySQL triggers.
7. **000015 — monitoring checkpoint integrity:** adds seven foreign keys and nullable `heartbeat_at`, then backfills existing null values from `started_at`.

The source order is a dependency model only. It is not an execution instruction. The production observations currently show missing ledger entries for all seven, seven missing target tables, and missing expected trigger/FK objects. This is consistent with substantial unapplied schema work, but the report does not establish whether the cause is an incomplete deployment, a database reset, independent partial DDL, or another operational history.

## Required next audit before a remediation proposal

The next work item is a **schema-definition and migration-history reconciliation**, not a migration run.

1. Inventory every relevant source migration and record its exact table, column, type, nullability, primary key, unique index, ordinary index, and FK definitions.
2. Compare the live metadata for every existing table against those definitions. Keep absent tables/columns as explicit missing states.
3. For each of the 11 000014 relationships, record the current FK name, local and referenced columns, referenced table, and delete/update rules. Identify exactly which legacy FK each migration `dropForeign` call expects.
4. Record the exact 24 trigger definitions expected from the migration source, including event, timing, target table, and body fingerprint. Current run confirms the expected names are absent.
5. Re-run the gated aggregate preconditions only after the relevant prerequisite tables and columns can be inspected: duplicate open alert keys, open-alert copy count, three identity-key duplicate counts, all 18 orphan checks, and null heartbeat count.
6. Reconcile the migration ledger against actual schema objects and the deployed application revision. Do not infer rerunnability from ledger absence.
7. Produce a separately reviewed, ordered remediation proposal with backup/restore evidence, expected DDL and DML, row-impact estimates, rollback limitations, and explicit operator approval points.

## Decision

- Read-only diagnostic execution: **PASS**.
- Production schema acceptance: **BLOCKED**.
- Source migration dependency review: **DOCUMENTED**.
- Full production schema-definition reconciliation: **PENDING**.
- Production remediation: **NOT AUTHORIZED / NOT PERFORMED**.
- Dummy production data: **NOT CREATED**.

The next acceptance gate is completion and review of the schema-definition reconciliation above. No production migration should be run until that gate is satisfied and a separate explicit authorization is given.
