# Phase 1C Test 1B — Current Production Read-only Evidence (2026-11-06)

**Repository:** `mattari20/estudent-listing`  
**Inspected revision:** `66c7d0b64cc85b9523b64fe0df18d706cbcaf1e4`  
**Scope:** two protected, manually dispatched, read-only production diagnostics.

## Executive result

| Area | Result |
|---|---|
| Structural metadata diagnostic execution | PASS |
| Consolidated all-18 preflight execution | PASS |
| Production schema acceptance | **BLOCKED / NOT PASSED** |
| Migration ledger reconciliation | **BLOCKED — 18 of 18 selected migrations absent** |
| Production remediation | NOT AUTHORIZED / NOT RUN |
| Test 1B acceptance | **OPEN** |

A green Actions conclusion means the diagnostic completed, not that production satisfies the expected schema.

## Exact workflow evidence

1. [Structural Metadata Audit — run #4](https://github.com/mattari20/estudent-listing/actions/runs/42146775246)  
   - Head SHA: `66c7d0b64cc85b9523b64fe0df18d706cbcaf1e4`
   - Conclusion: success.
   - 39 expected tables; 28 present.
   - 349 column metadata entries; 173 index metadata entries.
   - 32 foreign-key column metadata entries and 32 FK-rule entries.
   - Zero trigger definitions reported.

2. [Consolidated All-18 Migration Preflight — run #3](https://github.com/mattari20/estudent-listing/actions/runs/42146936130)  
   - Head SHA: `66c7d0b64cc85b9523b64fe0df18d706cbcaf1e4`
   - Conclusion: success.
   - 39 tables checked; 28 present; 11 missing.
   - 18/18 selected migration ledger entries returned zero.
   - 50 expected-column checks; 13 missing.
   - 23 expected-index checks; 13 missing.
   - 24 expected audit triggers; 0 present.
   - 18 orphan checks: 7 evaluated, 11 skipped because prerequisite tables/columns were missing; zero nonzero results among evaluated checks.

Both jobs passed the protected dispatch guard, PHP syntax validation, environment-scoped credential validation, and SSH diagnostic execution. The two diagnostics used the same configured database fingerprint.

## Missing tables

The current 39-table inventory reports these 11 missing tables:

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

The preflight reported these 13 missing columns:

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

The 13 missing expected indexes correspond to the uniqueness/idempotency indexes for retrieval runs, snapshots, observations, discovery outcomes, change-event links, monitoring audit idempotency, and discovery/identity keys on import batches, evidence, import rows, assertions, identity match candidates, identity resolution decisions, and import conflicts.

## Data-integrity and backfill evidence

- Duplicate current snapshot ID groups: 0.
- Duplicate open reverification identity groups: 0.
- Duplicate open monitoring-alert dedupe-key groups: 0.
- Existing open reverification queue rows: 5.
- Existing open monitoring alerts: 2.
- Eleven of 18 orphan checks were not evaluated because required schema prerequisites were missing. These are NOT zero-orphan results.
- The heartbeat NULL backfill precondition was not checked because its required schema was missing.
- Several duplicate-key preconditions for migrations 000003, 000005, 000006, 000011 and 000012 were not checked because required columns/tables were absent.

The 24 expected audit triggers were all absent. This is an observed schema gap, not permission to create triggers.

## Migration scope

The 18 pending migration names are the same set recorded in the previously merged full migration inventory report `PHASE_1C_TEST_B_FULL_MIGRATION_RECONCILIATION_2026-11-05.md`. The current run reconfirmed zero ledger entries for each of the 18 names. The earlier full inventory found 97 source migration files and 97 deployed migration filenames, with 79 ledger entries and 18 pending. This current pair did not rerun the full 97-file inventory; its all-18 scope is a targeted recheck.

## Safety evidence

Both runs explicitly report:

- `read_only_transaction=ROLLED_BACK`
- `production_database_changed=false`
- `production_application_files_changed=false`
- `production_migrations=NOT_RUN`
- `ddl_dml_executed=NO`
- no application row payloads emitted
- no persistent reports or remote temporary diagnostic files written
- no dummy production data created

## Required next gates — do not skip

1. **Source/schema reconciliation:** compare all 18 migration `up()` implementations against actual captured columns, indexes, FK definitions, triggers and table presence. Identify already-present/partial objects, migration ordering and every data backfill.
2. **Read-only data preflight:** complete the 11 skipped orphan checks and missing duplicate/backfill checks only after schema prerequisites are available. This needs a separately approved, tightly scoped read-only data-inspection operation. Do not expose row payloads.
3. **Backup/recovery:** obtain dated backup retention evidence and a successful controlled restore/recovery proof.
4. **Deployment and runtime parity:** verify the deployed code revision and runtime before preparing any execution plan.
5. **Remediation proposal:** only after Gates 1–4, prepare a migration-by-migration plan including DDL/DML, backfills, failure/rollback limits and post-checks. Production execution requires a separate explicit authorization.

No production migration, schema repair, DDL, DML, deployment, trigger installation, scheduler change, source retrieval, candidate staging, approval, import, canonical promotion or publication is authorized by this report.

**Disposition: Test 1B remains OPEN / BLOCKED.**
