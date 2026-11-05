# Phase 1C — Migration Ledger Reconciliation

## Purpose

Reconcile seven selected production migration-ledger entries against the schema objects those migrations are expected to create. This is a diagnostic only; it is not a remediation or migration workflow.

## Scope

The diagnostic reads:
- Only the selected migration names and batch values from Laravel's `migrations` table.
- MySQL `information_schema` metadata for required tables, columns, foreign keys, and named triggers.

It does not read rows from other application tables. It does not run Artisan, migrations, DDL, DML, trigger creation, or persistent report writes. The database transaction is explicitly read-only and rolled back.

## Migrations and expected objects

| Migration | Expected schema objects |
|---|---|
| `2026_10_25_000001_create_structured_enrichment_promotion_events_table` | `structured_enrichment_promotion_events` |
| `2026_11_03_000002_create_governed_execution_runs_table` | `governed_execution_runs`, `governed_execution_run_events` |
| `2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency` | `monitoring_alert_identities`, `security_audit_events.monitoring_idempotency_key` |
| `2026_11_04_000010_create_monitoring_policy_run_checkpoints` | `monitoring_policy_runs`, `monitoring_policy_run_sources` |
| `2026_11_04_000012_add_identity_conflict_persistence_keys_and_events` | `import_conflict_events` and three identity-key columns |
| `2026_11_04_000014_enforce_append_only_audit_retention` | 22 append-only triggers and two identity-decision guards |
| `2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat` | heartbeat column and seven named foreign keys |

## Status interpretation

- `CONSISTENT`: ledger entry recorded and all expected schema objects present.
- `RECORDED_BUT_SCHEMA_INCOMPLETE`: ledger entry exists but one or more expected objects are missing.
- `SCHEMA_PRESENT_BUT_NOT_RECORDED`: expected objects exist but migration is not in the ledger.
- `NOT_RECORDED_AND_SCHEMA_INCOMPLETE`: neither the ledger entry nor all expected schema objects are present.

A status is a reconciliation signal, not permission to run or rerun a migration. Any repair must first be reviewed against migration ordering, production backup/restore evidence, data safety, and the approved operational procedure.

## Dispatch

Workflow: `.github/workflows/phase-1c-migration-ledger-reconciliation.yml`

Required confirmation phrase:
`AUTHORIZE-PHASE1C-MIGRATION-LEDGER-RECONCILIATION-READONLY`

Use the current `main` SHA for `target_sha`. Keep the existing production-readonly-diagnostics GitHub Environment protections in place.
