# Phase 1C Test 1B — Migration Dependency Audit

## Purpose and safety boundary

This document records a source-code dependency review for the seven migrations currently included in the Phase 1B read-only preflight. It is a planning artifact, not proof of production schema state and not authorization to change production.

**Production safety rules**
- Do not run `php artisan migrate`, migration rollback, DDL, DML, trigger creation, or schema repair as part of this audit.
- Do not create dummy production data.
- Keep all production inspection read-only and report aggregate counts only.
- A successful GitHub Actions run means the diagnostic completed; it does not mean every migration is applied or that production is ready.
- Production remediation requires a separately reviewed plan and explicit authorization.

## Migration sequence and dependencies

The sequence below follows the migration filenames and the dependencies visible in their `up()` methods. It is not a substitute for confirming the migration ledger on the effective production database.

| Migration | Objects changed | Dependencies / preconditions visible in source | Risk gate |
|---|---|---|---|
| `2026_10_25_000001_create_structured_enrichment_promotion_events_table` | Creates `structured_enrichment_promotion_events` | `enrichment_import_rows`, `import_batches` must exist; its two foreign keys are created immediately. | Confirm both parent tables and compatible key types exist before execution. |
| `2026_11_03_000002_create_governed_execution_runs_table` | Creates `governed_execution_runs`, `governed_execution_run_events` | The event table references the UUID primary key of `governed_execution_runs`. | Confirm neither table is already partially present and the UUID key shape is compatible. |
| `2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency` | Creates `monitoring_alert_identities`; adds unique nullable `security_audit_events.monitoring_idempotency_key`; copies open alerts into the identity table. | Requires `monitoring_alerts` and `security_audit_events`. It explicitly aborts if more than one open alert shares a `dedupe_key`. | Count duplicate open-alert dedupe keys; verify target table/column/index state; preserve and reconcile any existing identity rows rather than overwriting them. |
| `2026_11_04_000010_create_monitoring_policy_run_checkpoints` | Creates `monitoring_policy_runs`, `monitoring_policy_run_sources` | Creates checkpoint tables and indexes; foreign keys are added later by 000015. | Confirm no partial tables exist and referenced parent tables are present before 000015. |
| `2026_11_04_000012_add_identity_conflict_persistence_keys_and_events` | Adds nullable unique identity keys to `identity_match_candidates`, `identity_resolution_decisions`, and `import_conflicts`; creates `import_conflict_events`. | Requires all three existing tables; creates unique indexes and an event foreign key to `import_conflicts`. | Check all three columns/indexes independently, detect partial application, and confirm compatible parent key. Nullable keys do not by themselves prove existing records have been backfilled. |
| `2026_11_04_000014_enforce_append_only_audit_retention` | Replaces 11 existing foreign keys with RESTRICT relationships and installs 24 MySQL triggers (22 table triggers plus 2 identity-decision guards). | Requires the 11 audit/history tables and their referenced parent tables. It performs 11 orphan checks before DDL. The migration has special handling for SQLite and MySQL. | Verify every table, column, existing FK name/definition, orphan count, trigger name and trigger definition. Do not infer safety merely from empty application data. |
| `2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat` | Adds `monitoring_policy_runs.heartbeat_at` and seven foreign keys; backfills null heartbeat values from `started_at`. | Depends on 000002, 000009, and 000010 objects plus `source_monitoring_policies`, `sources`, `source_retrieval_runs`, `source_change_observations`, and `monitoring_alerts`. It performs seven orphan checks before DDL. | Verify all seven relationships and heartbeat column independently; count orphans; preserve any existing heartbeat values. |

## Required dependency inventory

### Migration 000014 — audit retention

The migration references these 11 history tables:

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

Its parent-table checks include `import_rows`, `assertions`, `import_batches`, `data_reverification_queue`, `enrichment_import_rows`, `import_conflicts`, and `governed_execution_runs`.

The 11 named RESTRICT foreign keys are:
- `identity_decision_row_restrict_fk`
- `verification_decision_assertion_restrict_fk`
- `import_reconciliation_batch_restrict_fk`
- `import_promotion_decision_row_restrict_fk`
- `import_promotion_event_row_restrict_fk`
- `import_promotion_event_batch_restrict_fk`
- `reverification_event_queue_restrict_fk`
- `structured_enrichment_row_restrict_fk`
- `structured_enrichment_batch_restrict_fk`
- `import_conflict_event_restrict_fk`
- `governed_run_event_restrict_fk`

The 24 MySQL trigger names comprise two triggers for each of the 11 history tables, plus `identity_decision_update_guard` and `identity_decision_delete_guard`. The migration computes the table-trigger suffix from the first 16 characters of the SHA-256 hash of each table name; the diagnostic must derive those names using the same algorithm.

### Migration 000015 — monitoring checkpoint integrity

The seven named foreign keys are:
- `mp_run_policy_fk`
- `mp_run_exec_fk`
- `mp_source_run_fk`
- `mp_source_source_fk`
- `mp_source_retrieval_fk`
- `mp_source_observation_fk`
- `mai_active_alert_fk`

It also adds nullable `monitoring_policy_runs.heartbeat_at`, then sets existing null values to `started_at`. That backfill is a production data write and must be included in any future remediation approval.

## What the current Test 1B preflight already inspects

The current `scripts/diagnostics/phase1c-test1b-readonly-migration-preflight.php` checks:
- effective Laravel database configuration and a non-secret database fingerprint;
- presence of the migration ledger and required application tables;
- aggregate row counts for selected monitoring/audit tables;
- the 11 orphan relationships used by migration 000014 and seven used by 000015;
- ledger presence for the seven named migrations;
- expected 24 trigger names, 11 audit foreign-key names, seven monitoring foreign-key names, and the heartbeat column;
- partial-application classification and a read-only transaction rollback.

A green Actions result indicates the diagnostic process completed. Read the emitted `preflight_classification` and each individual check; do not use the workflow conclusion alone as a production readiness decision.

## Additional verification required before a migration plan can be approved

The current preflight should be extended or supplemented to report the following read-only checks:

1. **Column and index shape:** verify all columns, data types, nullability, primary/unique/index definitions, and referenced key types required by each migration. Object-name presence alone is insufficient.
2. **Migration 000009 duplicate precondition:** count duplicate `dedupe_key` groups among open `monitoring_alerts`; also report the number of open alerts that would be copied. Do not emit alert payloads or identifiers.
3. **Migration 000012 uniqueness preconditions:** count duplicate non-null values for each proposed identity key if values already exist, and detect partial column/index states independently.
4. **Migration 000014 trigger definitions:** compare existing trigger event, timing, table, and normalized body/definition against the expected implementation; name-only presence is insufficient.
5. **Migration 000014 existing FK definitions:** verify the current FK definitions that the migration attempts to drop, not just the desired new FK names.
6. **Migration 000015 heartbeat semantics:** count null heartbeat values and verify `started_at` is present and compatible; do not update any value during diagnostics.
7. **Migration-ledger consistency:** report ledger rows, missing rows, duplicate rows, and schema-object mismatches independently. Never infer that a migration is safely rerunnable from a missing ledger row.
8. **Schema completeness:** report missing tables/columns before executing dependent SQL, and classify dependent checks as `NOT_CHECKED` rather than treating a SQL error as zero orphan rows.

## Execution order for any future approved remediation

Subject to the complete schema inventory and review, the source-level dependency order is:

1. 000001 structured enrichment events
2. 000002 governed execution runs/events
3. 000009 monitoring alert identities and audit idempotency
4. 000010 monitoring checkpoint tables
5. 000012 identity/conflict persistence keys/events
6. 000014 append-only audit retention
7. 000015 monitoring checkpoint foreign keys and heartbeat

This is a dependency-based planning order, **not an instruction to execute migrations now**. Before any production run, confirm the deployed application revision, the exact effective database target, backup and restore evidence, migration ledger, existing schema, and all data-integrity gates.

## Current status

- Source-code dependency review: documented in this file.
- Production dependency audit: **not yet run as a distinct completed audit**.
- Production schema remediation: **not authorized and not performed**.
- Test 1B: remains under investigation until the read-only evidence is reviewed and the acceptance criteria are met.
