# Phase 1C Test 1B — Structural Metadata Audit Run 2 Evidence

**Audit run:** [GitHub Actions run #42059232458](https://github.com/mattari20/estudent-listing/actions/runs/42059232458)  
**Job:** Capture exact production schema metadata  
**Dispatch revision:** `fcc1be443fd46eb036ca2e1da8b0342caa99e0d0`  
**Result:** workflow/job succeeded; metadata capture completed  
**Database fingerprint:** `d06c2a8519877bad90ffeeaa64f7998a6d4bdade1439b1dafd90a46850e6b019`

## 1. What this run proves

The protected SSH diagnostic resolved the effective Laravel database connection using `dotenv_plus_config_database` and connection name `mysql`. It connected successfully and queried `information_schema` within a read-only transaction.

The fingerprint exactly matches the fingerprint from consolidated all-18 preflight run [#42054629047](https://github.com/mattari20/estudent-listing/actions/runs/42054629047). Therefore these two diagnostics inspected the same database identity (host/database/current-user fingerprint).

Captured metadata:

| Metadata | Result |
|---|---:|
| Tables present in the diagnostic's 38-table scope | 27 |
| Tables absent from that scope | 11 |
| Column definitions | 338 |
| Index entries | 169 |
| Foreign-key column mappings | 32 |
| Foreign-key referential-rule entries | 32 |
| Triggers on the 38 scoped tables | 0 |

All observed tables reported InnoDB, `utf8mb4_unicode_ci`, Dynamic row format and BASE TABLE type.

The server does not expose `IS_VISIBLE` or `EXPRESSION` in `information_schema.STATISTICS`; the diagnostic reported both capabilities as unavailable and still captured portable index metadata. This is a server metadata capability limitation, not evidence that indexes are absent.

## 2. Missing tables in the exact 38-table diagnostic scope

The diagnostic's source-defined table list contains 38 names, but the earlier read-only schema inventory defined a 39-table scope that also included `institutions`. The 38-table script accidentally omitted `institutions`. The following 11 names were not returned by `information_schema.TABLES` within the 38-name query:

1. `password_reset_tokens`
2. `structured_enrichment_promotion_events`
3. `governed_execution_leases`
4. `governed_execution_runs`
5. `governed_execution_run_events`
6. `data_reverification_queue_identities`
7. `monitoring_alert_identities`
8. `monitoring_policy_runs`
9. `monitoring_policy_run_sources`
10. `import_conflict_events`
11. `source_snapshot_reconciliation_requests`

The tables `users`, `migrations`, `import_batches`, `sources`, `source_retrieval_runs`, `source_snapshots`, `source_change_observations`, `institute_discovery_run_outcomes`, `change_events`, `data_reverification_queue`, `monitoring_alerts`, `source_monitoring_policies`, `verification_decisions`, `provenance_review_events`, `import_promotion_decisions`, `import_promotion_events`, `reverification_events`, `official_source_refresh_events`, `import_reconciliation_events`, `import_rows`, `assertions`, `evidence`, `enrichment_import_rows`, `import_conflicts`, and `identity_match_candidates` were present in this scope, along with the remaining returned tables listed in the run log.

**Important:** absence from this diagnostic's table list is not, by itself, authorization to create a table. Each missing object must first be tied to its exact migration, dependencies, expected columns/indexes/FKs, migration-ledger entry, and any backfill behavior.

## 3. Trigger finding

The audit returned `trigger_metadata_count=0` for the 38 scoped tables queried in run #42059232458; `institutions` was outside that run's scope. The consolidated all-18 plan identifies 24 expected audit/identity triggers for migration `2026_11_04_000014`.

This is a material schema gap requiring exact source-to-production comparison. The query is scoped to the 38 named tables; do not generalize it to every table in the database.

## 4. Consolidated preflight correlation

The earlier consolidated all-18 preflight [run #42054629047](https://github.com/mattari20/estudent-listing/actions/runs/42054629047) used the same database fingerprint. It reported missing-table prerequisites as `NOT_CHECKED_PREREQUISITE_MISSING`, including expected columns and indexes for the missing tables. It also reported:

- 24 expected audit triggers; 0 present; 24 missing.
- 18 orphan checks total; 7 evaluated; 11 skipped because prerequisite tables/columns were unavailable.
- Duplicate checks and heartbeat backfill exposure were marked `NOT_CHECKED_PREREQUISITE_MISSING` where prerequisites were absent.

Skipped checks are **unknown**, not clean/zero. No row-level payload was emitted.

## 5. Scope-count discrepancy and correction

The earlier read-only schema inventory explicitly included `institutions`, giving a 39-table scope. The structural audit script used for run #42059232458 omitted `institutions` and queried only 38 names (`expected_scope=38`). A separate read-only migration preflight run #42049836371 returned an existing foreign-key definition referencing `institutions.id`, which is evidence that the table existed at that earlier check. The run #42059232458 counts above remain the exact historical 38-table results; they must not be relabelled as 39-table results.

A follow-up code correction adds `institutions` to both the structural metadata audit and consolidated all-18 preflight table scopes. After that correction is merged, rerun both protected read-only workflows against the same current `main` SHA. Only the new run can establish the current 39-table totals and current `institutions` metadata.

## 6. Safety evidence

The workflow log confirms:

- `read_only_transaction=ROLLED_BACK`
- `production_database_changed=false`
- `production_application_files_changed=false`
- `production_migrations=NOT_RUN`
- `ddl_dml_executed=NO`
- `application_row_payloads_emitted=NO`
- `remote_temporary_files_created=NO`
- `dummy_production_data=NOT_CREATED`

## 7. Decision and next work

**Test 1B remains OPEN / BLOCKED.** This successful run is evidence capture, not schema acceptance.

Next grouped work package should reconcile the 18 pending migration source definitions against:
1. the exact migration ledger entries;
2. table and column definitions;
3. ordered index definitions and uniqueness;
4. FK column mappings and update/delete rules;
5. the exact 24 trigger definitions and bodies;
6. the 11 skipped orphan checks, duplicate checks and backfill exposure once prerequisites are available;
7. verified backup and isolated restore evidence.

Then prepare one ordered remediation proposal identifying each missing/partial object, migration order, exact effects, stop conditions and rollback limitations. Production schema changes remain separate and require explicit authorization after review. Do not run migrations, create tables manually, add triggers/FKs, seed dummy data, or modify production as part of this evidence PR.
