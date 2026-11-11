# Phase 1D.1 — Production Migration Reconciliation Inventory

**Snapshot date:** 2026-11-11  
**Repository:** `mattari20/estudent-listing`  
**Verified documentation baseline:** `main` at `1b75c875a49db2ebf931c8de6bc135043e6bfe24`  
**Production evidence run:** [GitHub Actions #42965183303](https://github.com/mattari20/estudent-listing/actions/runs/42965183303)  
**Evidence source revision:** `0a5e680db4f0ef9a49dd55fd146f68c00c3a0661`  
**Classification:** Documentation-only reconciliation; no production connection or SQL execution was performed for Phase 1D.1.

## 1. Executive summary

Phase 1C engineering implementation is closed. Phase 1D.1 records the production recovery baseline and maps the 18 pending migration definitions to their source files, expected objects, dependency gates and known compatibility risks. This is not migration authorization and is not a production acceptance declaration.

The latest `main` is `1b75c875a49db2ebf931c8de6bc135043e6bfe24`. PR [#1065](https://github.com/mattari20/estudent-listing/pull/1065) is merged at `0a5e680db4f0ef9a49dd55fd146f68c00c3a0661`; PR [#1066](https://github.com/mattari20/estudent-listing/pull/1066) is merged at the current `main` SHA. PR #1066 adds the final Phase 1C closure and latest production evidence record.

### Verified production snapshot

The protected workflow run completed with conclusion `success`, was manually dispatched from `main`, and targeted the exact source revision `0a5e680db4f0ef9a49dd55fd146f68c00c3a0661`. The artifact reports MariaDB `11.8.9-MariaDB-log`, complete metadata visibility and complete trigger visibility. Its transaction mode is `READ ONLY` and its outcome is `ROLLED_BACK`.

| Inventory | Expected | PASS / PRESENT | MISSING | NOT_CHECKED |
|---|---:|---:|---:|---:|
| Tables | 39 | 28 | 11 | 0 |
| Expected columns | 50 | 2 | 48 | 0 |
| Expected named indexes | 23 | 0 | 23 | 0 |
| Expected foreign keys | 23 | 0 | 23 | 0 |
| Expected triggers | 24 | 0 | 24 | 0 |
| Migration-ledger entries | 18 | 0 | 18 | 0 |
| Orphan checks | 18 | 7 | 0 | 11 |
| Duplicate checks | 14 | 3 | 0 | 11 |

The artifact separately reports **five open reverification queue records** and **two open monitoring alerts**. All seven per-record identity mappings are `NOT_CHECKED`; their existence is not permission to consolidate, rewrite, delete, or backfill them. Preserve all seven records and their history.

**Production disposition: NOT READY / NOT ACCEPTED.** A successful read-only collector run confirms safe evidence collection, not schema acceptance. No production migration, DDL, DML, repair, backfill, deployment, backup or restore was performed or authorized by this inventory.

## 2. Baseline, source hierarchy and evidence integrity

Sources were read from the verified `main` tree. The source hierarchy used is:

1. The protected evidence artifact for observed production state.
2. `scripts/phase1c/readonly-schema-diagnostic.php` and `scripts/phase1c/readonly-diagnostic.schema.json` for expected inventory definitions and evidence semantics.
3. `tests/Support/verify-phase1c-testb-migration-objects.php` for the acceptance registry of named foreign keys and trigger contracts.
4. `scripts/diagnostics/phase1c-test1b-consolidated-all18-preflight.php` for the exact 18 migration-ledger identifiers and aggregate inventory scope.
5. Each actual migration file under `database/migrations/` for the implemented `up()` / `down()` operations and definition details.
6. `app/Support/Database/MigrationSchemaGuard.php` and the Phase 1C acceptance tests for guard and compatibility behavior.
7. The authoritative Phase 1C closure report and the all-18 migration source review for historical context. Historical values are not substituted for the newer artifact.

### Run and artifact verification

| Item | Verified value |
|---|---|
| Run ID | `42965183303` |
| Run result | `completed / success` |
| Event / ref | `workflow_dispatch / main` |
| Run target SHA | `0a5e680db4f0ef9a49dd55fd146f68c00c3a0661` |
| Artifact | `phase1c-readonly-diagnostic-42965183303` |
| Artifact ID | `13831501107` |
| Artifact size | 16,777 bytes |
| GitHub archive SHA-256 | `a7fc5ab49900148ec925307a765d8427a8215731ac2e92296b4e3249f09d79ab` |
| Evidence JSON SHA-256 | `c4cfd921a0112bbc6948116c7009fd35f3a7c33a9fc12eb950abd96c5a95dbf4` |
| Manifest target SHA / run ID | Matches run target / `42965183303` |

The ZIP was downloaded and inspected. Its archive SHA-256 independently matched GitHub's artifact digest. The JSON's SHA-256 independently matched both `evidence-manifest.json` fields `evidence_sha256` and `materialized_evidence_sha256`; `evidence.sha256` also contains that same digest. The manifest's `compressed_sha256` (`c35bcb6ec386c468dcee3c8d00243368dd23aa41e5bab08adcb6e58dbda7b901`) refers to a compressed transfer representation not separately included in the ZIP, so that particular compressed-payload digest could not be independently recomputed from the archive. Artifact retention expiry reported by GitHub: 2026-11-18 05:25:49 UTC; retain the downloaded archive with the audit record if longer retention is required.

The evidence JSON has schema version `phase1c-readonly-evidence-v2`, registry counts of 39 / 50 / 23 / 23 / 24 / 18, and reports complete metadata visibility. The run source SHA predates the documentation-only PR #1066 merge; this is expected because #1066 updated the closure report after the collector run. The run evidence is not represented as having executed against the documentation commit.

### State vocabulary

- `PRESENT`: an object was observed in production. This does not alone prove definition equivalence.
- `MISSING`: complete relevant metadata visibility was established and the expected object was absent.
- `NOT_CHECKED`: the check was skipped, its prerequisite was absent, or evidence did not establish the property.
- `UNKNOWN`: evidence or metadata visibility is insufficient to distinguish present from absent or to establish equivalence.

The evidence schema's `PASS` state for object reconciliation is rendered here as **PRESENT**; it means the collector's expected definition check passed where such a definition check exists. For the table inventory it establishes table presence, not full table-definition equivalence. No `UNKNOWN` state was emitted for the listed object inventories because metadata visibility was complete. The five queue and two alert mapping records remain `NOT_CHECKED`.

## 3. Complete 18-migration matrix

All 18 identifiers in the preflight registry were matched to an existing PHP migration source file on the verified `main` revision. Each file exposes both `up()` and `down()`. Ledger record count is zero for every identifier in the production artifact; batches are empty. This is a production ledger observation, not a statement that the source file is absent.

| # | Migration identifier | Source file | Source blob SHA | Purpose / key operations | Dependencies and gates | Production ledger |
|---:|---|---|---|---|---|---|
| 1 | `2026_10_17_000001_create_password_reset_tokens_table` | [migration](../../database/migrations/2026_10_17_000001_create_password_reset_tokens_table.php) | `3da9e7b36565d3dc64962e88ab1683be54ceafcd` | Create `password_reset_tokens`; primary email, token, nullable created_at. | Laravel users/password-reset feature compatibility; validate existing table definition before treating as safely applied. | MISSING; 0 rows |
| 2 | `2026_10_17_000002_add_must_change_password_to_users_table` | [migration](../../database/migrations/2026_10_17_000002_add_must_change_password_to_users_table.php) | `2fc797413ffb241829bcbc9aa9d50dcbc194653f` | Add `users.must_change_password`, boolean / TINYINT(1), NOT NULL DEFAULT 0. | Requires `users`; existing column must exactly match type, nullability and default. | MISSING; 0 rows |
| 3 | `2026_10_23_000001_add_duplicate_rows_to_import_batches_table` | [migration](../../database/migrations/2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php) | `a0bd89f56c4bc4f0e592bba6ed7846b7cb083c3f` | Add `import_batches.duplicate_rows`, INT UNSIGNED NOT NULL DEFAULT 0. | Requires `import_batches`; existing column definition is guarded. Production column is PRESENT and this is the only fully reconciled expected column besides #4. | MISSING; 0 rows |
| 4 | `2026_10_25_000001_create_structured_enrichment_promotion_events_table` | [migration](../../database/migrations/2026_10_25_000001_create_structured_enrichment_promotion_events_table.php) | `bfef89d68947a79cd0044d440801eaf277d591f8` | Create append-oriented enrichment promotion event table, with row/batch foreign keys, snapshots, metadata and three indexes. | Requires `enrichment_import_rows` and `import_batches`; production table is MISSING. | MISSING; 0 rows |
| 5 | `2026_11_03_000001_create_governed_execution_leases_table` | [migration](../../database/migrations/2026_11_03_000001_create_governed_execution_leases_table.php) | `927a2837bcbbe645b8359859d41afe8c8dd7ff33` | Create lease table; unique task key, owner/run UUIDs and expiry index. | Foundation for governed execution runs/events and monitoring run references. | MISSING; 0 rows |
| 6 | `2026_11_03_000002_create_governed_execution_runs_table` | [migration](../../database/migrations/2026_11_03_000002_create_governed_execution_runs_table.php) | `ea8d5aafb5863a3b5ec254fe1494ec1be19ae00b` | Create governed execution runs and run-event tables; run UUID, status, runner, revision and timestamps. | Required by #14 retention FK for run events and #15 monitoring execution FK. | MISSING; 0 rows |
| 7 | `2026_11_03_000003_add_source_retrieval_idempotency_key` | [migration](../../database/migrations/2026_11_03_000003_add_source_retrieval_idempotency_key.php) | `6eb3dbfde2a0db7909d9192882b0d31d62e4c3af` | Ensure nullable VARCHAR(64) `source_retrieval_runs.idempotency_key` and unique index. | Requires `source_retrieval_runs`; nullable unique key is compatible with multiple legacy NULLs. #15 later references retrieval runs. | MISSING; 0 rows |
| 8 | `2026_11_03_000004_add_snapshot_and_observation_idempotency` | [migration](../../database/migrations/2026_11_03_000004_add_snapshot_and_observation_idempotency.php) | `f11de606de1fef11012a5314c4fe98069ee5c093` | Add nullable unique snapshot capture key and unique observation current-snapshot identity. | Requires `source_snapshots` and `source_change_observations`; explicitly rejects duplicate non-NULL current_snapshot_id values and validates existing column metadata. | MISSING; 0 rows |
| 9 | `2026_11_03_000005_add_discovery_outcome_idempotency_key` | [migration](../../database/migrations/2026_11_03_000005_add_discovery_outcome_idempotency_key.php) | `ada1a93014061499a3adf9fcf2fa013d80d0d57c` | Add nullable unique idempotency key to `institute_discovery_run_outcomes`. | Requires the existing outcomes table; validate legacy non-NULL key compatibility. | MISSING; 0 rows |
| 10 | `2026_11_03_000006_add_change_event_source_link_identity` | [migration](../../database/migrations/2026_11_03_000006_add_change_event_source_link_identity.php) | `1f9fd0492450ef2088a87a1b26e347f5ca49b750` | Add nullable unsigned BIGINT source-link identity and unique index to `change_events`. | Requires `change_events`; review existing non-NULL duplicates and type compatibility. | MISSING; 0 rows |
| 11 | `2026_11_03_000007_create_data_reverification_queue_identities_table` | [migration](../../database/migrations/2026_11_03_000007_create_data_reverification_queue_identities_table.php) | `36b528652da2441320fbb66669b3589f771a993b` | Create active queue identity table and backfill one mapping for each open queue row in 500-row chunks; identity_key is SHA-256 of subject_type, subject_id and attribute. Existing mapping conflicts fail closed. | Requires `data_reverification_queue`; aborts on duplicate open subject/attribute rows. Five open records exist; mapping is NOT_CHECKED. #11 will insert derived identity rows for open records in 500-row chunks; preserve the source rows and establish exact one-to-one mapping before any execution. | MISSING; 0 rows |
| 12 | `2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency` | [migration](../../database/migrations/2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency.php) | `14f22fc27014d05ebe8f73982820f6f49c15e407` | Create active alert identity table, backfill open-alert mappings in 500-row chunks (dedupe_key to active_alert_id), and add nullable monitoring idempotency key to security audit events. Existing mapping conflicts fail closed. | Requires `monitoring_alerts` and `security_audit_events`; aborts on duplicate open dedupe keys. Two open alerts exist; mapping is NOT_CHECKED. #12 will insert dedupe-key-to-alert identity rows for open alerts in 500-row chunks; preserve the source alerts and establish exact mapping before any execution. | MISSING; 0 rows |
| 13 | `2026_11_04_000010_create_monitoring_policy_run_checkpoints` | [migration](../../database/migrations/2026_11_04_000010_create_monitoring_policy_run_checkpoints.php) | `4cae07cf6f837e590c222d425e37cc2fa9106dd2` | Create monitoring policy run and per-source checkpoint tables. | Requires source monitoring policies, source/retrieval/observation entities and governed execution run references; #15 adds/validates FK relationships. | MISSING; 0 rows |
| 14 | `2026_11_04_000011_add_discovery_candidate_persistence_identity_keys` | [migration](../../database/migrations/2026_11_04_000011_add_discovery_candidate_persistence_identity_keys.php) | `030939e952e112ae5c453d0537569288ef484401` | Add nullable unique 64-character discovery identity keys to import_batches, evidence, import_rows and assertions. | Requires all four existing tables; duplicate/non-NULL and exact schema checks are prerequisites. | MISSING; 0 rows |
| 15 | `2026_11_04_000012_add_identity_conflict_persistence_keys_and_events` | [migration](../../database/migrations/2026_11_04_000012_add_identity_conflict_persistence_keys_and_events.php) | `3e37261d337dd51e9b0b5f304aad66ef45ac5374` | Add nullable unique identity keys to candidates, decisions and conflicts; create conflict event history and idempotency/index fields. | Requires identity candidate/decision/conflict and import row/batch entities; #14 later adds conflict-event retention FK and triggers. | MISSING; 0 rows |
| 16 | `2026_11_04_000013_create_source_snapshot_reconciliation_requests_table` | [migration](../../database/migrations/2026_11_04_000013_create_source_snapshot_reconciliation_requests_table.php) | `687dd588cb743f2c8d687ff3d699b182688f62c5` | Create source snapshot reconciliation request table, unique identity, status/details, five named FKs and supporting indexes. | Requires sources and source_snapshots. Three FKs use CASCADE; two previous-snapshot references use SET NULL. | MISSING; 0 rows |
| 17 | `2026_11_04_000014_enforce_append_only_audit_retention` | [migration](../../database/migrations/2026_11_04_000014_enforce_append_only_audit_retention.php) | `dcbdba2cecff8dbeee00ef6cbf65ee236897e1de` | Validate orphan relationships, add eleven named retention FKs, install 22 BEFORE UPDATE/DELETE audit triggers plus identity decision guards. | Depends on all referenced parent/child tables from #4, #6, #11, #12 and existing import/reverification/audit tables. Orphan checks are hard stop gates; one reconciliation-detach update trigger has a distinct contract. | MISSING; 0 rows |
| 18 | `2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat` | [migration](../../database/migrations/2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat.php) | `83577ba3cbcd5abe481fd50b4737da70499cbccd` | Add/validate heartbeat and monitoring policy/source checkpoint FKs; connect active alert identity to monitoring alerts; update NULL heartbeat_at values to started_at. | Depends on #5–#7, #9, #12–#13 and existing policy/source/retrieval/observation tables. Orphan and heartbeat preconditions must be evaluated before any change. | MISSING; 0 rows |

**Ledger interpretation:** all 18 migration source files exist in the repository, but the production `migrations` ledger contains zero matching rows for each. No batch number is recorded. `batch_values_valid` is true because the observed batch list is empty; it does not mean the migrations were applied.

## 4. Six object reconciliation inventories

The six inventories below are the exact object lists emitted by the run's v2 evidence. The registry source is [`readonly-schema-diagnostic.php`](../../scripts/phase1c/readonly-schema-diagnostic.php); acceptance definitions are cross-checked against [`verify-phase1c-testb-migration-objects.php`](../../tests/Support/verify-phase1c-testb-migration-objects.php). Migration-specific source links are provided in Section 3 and referenced by migration identifier below.

### 4.1 Tables — 39

| Table | Production state | Migration/source association |
|---|---|---|
| assertions | PRESENT | Existing table; #14 adds retention guard |
| change_events | PRESENT | #10 adds source-link identity |
| data_reverification_queue | PRESENT | Parent for #11; #14 retention relationship |
| data_reverification_queue_identities | MISSING | #11 |
| enrichment_import_rows | PRESENT | Parent for #4 |
| evidence | PRESENT | #14 retention; #14 adds discovery key |
| governed_execution_leases | MISSING | #5 |
| governed_execution_run_events | MISSING | #6; #17 retention trigger/FK |
| governed_execution_runs | MISSING | #6; #18 monitoring FK |
| identity_match_candidates | PRESENT | #15 |
| identity_resolution_decisions | PRESENT | #15; #17 FK and identity guards |
| import_batches | PRESENT | #3, #4, #14, #15 |
| import_conflict_events | MISSING | #15; #17 retention FK/triggers |
| import_conflicts | PRESENT | #15; #17 conflict-event relationship |
| import_promotion_decisions | PRESENT | #17 FK/triggers |
| import_promotion_events | PRESENT | #17 FK/triggers |
| import_reconciliation_events | PRESENT | #17 FK/triggers |
| import_rows | PRESENT | #14, #15, #17 |
| institute_discovery_run_outcomes | PRESENT | #9 |
| institutions | PRESENT | Existing referenced entity |
| migrations | PRESENT | Laravel migration ledger |
| monitoring_alert_identities | MISSING | #12; #18 FK |
| monitoring_alerts | PRESENT | Parent for #12 and #18 |
| monitoring_policy_run_sources | MISSING | #13; #18 |
| monitoring_policy_runs | MISSING | #13; #18 |
| official_source_refresh_events | PRESENT | #17 audit triggers |
| password_reset_tokens | MISSING | #1 |
| provenance_review_events | PRESENT | #17 audit triggers |
| reverification_events | PRESENT | #11; #17 |
| security_audit_events | PRESENT | #12 idempotency key; #17 audit triggers |
| source_change_observations | PRESENT | #8; #18 |
| source_monitoring_policies | PRESENT | #13; #18 |
| source_retrieval_runs | PRESENT | #7; #18 |
| source_snapshot_reconciliation_requests | MISSING | #16 |
| source_snapshots | PRESENT | #8; #16; #18 |
| sources | PRESENT | #16; #18 |
| structured_enrichment_promotion_events | MISSING | #4; #17 audit triggers/FKs |
| users | PRESENT | #2 |
| verification_decisions | PRESENT | #17 FK and audit triggers |

Table PRESENT is not a substitute for exact table-definition equivalence. The evidence artifact provides normalized CREATE TABLE metadata for visible tables, but this inventory does not claim a complete expected-vs-actual comparison for all 39 table definitions. Missing tables are MISSING because metadata visibility was complete.

### 4.2 Columns — 50

| # | Expected column | State | Source migration |
|---:|---|---|---|
| 1 | password_reset_tokens.email | MISSING | #1 |
| 2 | password_reset_tokens.token | MISSING | #1 |
| 3 | password_reset_tokens.created_at | MISSING | #1 |
| 4 | users.must_change_password | MISSING | #2 |
| 5 | import_batches.duplicate_rows | PRESENT | #3 |
| 6 | structured_enrichment_promotion_events.enrichment_import_row_id | MISSING | #4 |
| 7 | structured_enrichment_promotion_events.import_batch_id | MISSING | #4 |
| 8 | structured_enrichment_promotion_events.occurred_at | MISSING | #4 |
| 9 | governed_execution_leases.task_key | MISSING | #5 |
| 10 | governed_execution_leases.owner_token | MISSING | #5 |
| 11 | governed_execution_leases.run_id | MISSING | #5 |
| 12 | governed_execution_leases.expires_at | MISSING | #5 |
| 13 | governed_execution_runs.id | MISSING | #6 |
| 14 | governed_execution_run_events.run_id | MISSING | #6 |
| 15 | governed_execution_run_events.event_type | MISSING | #6 |
| 16 | governed_execution_run_events.occurred_at | MISSING | #6 |
| 17 | source_retrieval_runs.idempotency_key | MISSING | #7 |
| 18 | source_snapshots.capture_key | MISSING | #8 |
| 19 | source_change_observations.current_snapshot_id | PRESENT | #8 |
| 20 | institute_discovery_run_outcomes.idempotency_key | MISSING | #9 |
| 21 | change_events.source_change_assertion_link_id | MISSING | #10 |
| 22 | data_reverification_queue_identities.identity_key | MISSING | #11 |
| 23 | data_reverification_queue_identities.subject_type | MISSING | #11 |
| 24 | data_reverification_queue_identities.subject_id | MISSING | #11 |
| 25 | data_reverification_queue_identities.active_queue_id | MISSING | #11 |
| 26 | monitoring_alert_identities.dedupe_key | MISSING | #12 |
| 27 | monitoring_alert_identities.active_alert_id | MISSING | #12 |
| 28 | security_audit_events.monitoring_idempotency_key | MISSING | #12 |
| 29 | monitoring_policy_runs.heartbeat_at | MISSING | #18 |
| 30 | monitoring_policy_runs.source_monitoring_policy_id | MISSING | #13 |
| 31 | monitoring_policy_runs.execution_run_id | MISSING | #13 |
| 32 | monitoring_policy_runs.started_at | MISSING | #13 |
| 33 | monitoring_policy_run_sources.monitoring_policy_run_id | MISSING | #13 |
| 34 | monitoring_policy_run_sources.source_id | MISSING | #13 |
| 35 | monitoring_policy_run_sources.retrieval_run_id | MISSING | #13 |
| 36 | monitoring_policy_run_sources.observation_id | MISSING | #13 |
| 37 | import_batches.discovery_identity_key | MISSING | #14 |
| 38 | evidence.discovery_identity_key | MISSING | #14 |
| 39 | import_rows.discovery_identity_key | MISSING | #14 |
| 40 | assertions.discovery_identity_key | MISSING | #14 |
| 41 | identity_match_candidates.identity_key | MISSING | #15 |
| 42 | identity_resolution_decisions.decision_identity_key | MISSING | #15 |
| 43 | import_conflicts.identity_key | MISSING | #15 |
| 44 | import_conflict_events.idempotency_key | MISSING | #15 |
| 45 | source_snapshot_reconciliation_requests.identity_key | MISSING | #16 |
| 46 | source_snapshot_reconciliation_requests.source_id | MISSING | #16 |
| 47 | source_snapshot_reconciliation_requests.triggering_snapshot_id | MISSING | #16 |
| 48 | source_snapshot_reconciliation_requests.current_snapshot_id | MISSING | #16 |
| 49 | source_snapshot_reconciliation_requests.expected_previous_snapshot_id | MISSING | #16 |
| 50 | source_snapshot_reconciliation_requests.recorded_previous_snapshot_id | MISSING | #16 |

The two PRESENT columns are `import_batches.duplicate_rows` and `source_change_observations.current_snapshot_id`. **Independent review correction:** `monitoring_policy_runs.heartbeat_at` is introduced only by migration #18 (`2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat.php`); migration #13 creates the monitoring policy/checkpoint tables but does not define this heartbeat column. The artifact's per-column state is based on the definition-level comparator; do not infer that the remaining 48 are all missing from present parent tables—many are reported as MISSING because their parent table is absent.

### 4.3 Named indexes — 23

| # | Table | Expected index | Columns | Unique | State | Source |
|---:|---|---|---|---|---|---|
| 1 | password_reset_tokens | PRIMARY | email | yes | MISSING | #1 |
| 2 | governed_execution_leases | governed_execution_leases_task_key_unique | task_key | yes | MISSING | #5 |
| 3 | governed_execution_leases | governed_execution_leases_expires_at_index | expires_at | no | MISSING | #5 |
| 4 | source_retrieval_runs | source_retrieval_runs_idempotency_key_unique | idempotency_key | yes | MISSING | #7 |
| 5 | source_snapshots | source_snapshots_capture_key_unique | capture_key | yes | MISSING | #8 |
| 6 | source_change_observations | source_observations_current_snapshot_unique | current_snapshot_id | yes | MISSING | #8 |
| 7 | institute_discovery_run_outcomes | institute_discovery_run_outcomes_idempotency_key_unique | idempotency_key | yes | MISSING | #9 |
| 8 | change_events | change_events_source_link_unique | source_change_assertion_link_id | yes | MISSING | #10 |
| 9 | security_audit_events | security_audit_events_monitoring_idempotency_key_unique | monitoring_idempotency_key | yes | MISSING | #12 |
| 10 | monitoring_policy_run_sources | monitoring_policy_run_source_unique | monitoring_policy_run_id, source_id | yes | MISSING | #13 |
| 11 | import_batches | import_batches_discovery_identity_unique | discovery_identity_key | yes | MISSING | #14 |
| 12 | evidence | evidence_discovery_identity_unique | discovery_identity_key | yes | MISSING | #14 |
| 13 | import_rows | import_rows_discovery_identity_unique | discovery_identity_key | yes | MISSING | #14 |
| 14 | assertions | assertions_discovery_identity_unique | discovery_identity_key | yes | MISSING | #14 |
| 15 | identity_match_candidates | identity_match_candidates_identity_key_unique | identity_key | yes | MISSING | #15 |
| 16 | identity_resolution_decisions | identity_resolution_decisions_identity_key_unique | decision_identity_key | yes | MISSING | #15 |
| 17 | import_conflicts | import_conflicts_identity_key_unique | identity_key | yes | MISSING | #15 |
| 18 | import_conflict_events | import_conflict_events_idempotency_key_unique | idempotency_key | yes | MISSING | #15 |
| 19 | source_snapshot_reconciliation_requests | source_snapshot_reconciliation_requests_identity_key_unique | identity_key | yes | MISSING | #16 |
| 20 | source_snapshot_reconciliation_requests | snapshot_reconciliation_source_status_idx | source_id, status | no | MISSING | #16 |
| 21 | source_snapshot_reconciliation_requests | snapshot_reconciliation_current_status_idx | current_snapshot_id, status | no | MISSING | #16 |
| 22 | import_conflict_events | import_conflict_events_import_conflict_id_occurred_at_index | import_conflict_id, occurred_at | no | MISSING | #15 |
| 23 | monitoring_policy_run_sources | monitoring_policy_source_status_idx | source_id, status | no | MISSING | #13 |

Index MISSING means the named expected index was absent in complete metadata. The 23 entries are the diagnostic's explicit named-index reconciliation registry, not an exhaustive count of every primary/secondary index created inside all 18 migration table definitions (for example, #4–#6 and #13 also define indexes as part of table creation). It does not authorize blindly creating an index: source definitions, uniqueness preconditions and current data must be revalidated immediately before a separately approved migration. **Independent review correction:** the diagnostic's expected index registry lists `password_reset_tokens.PRIMARY(id)`, but migration #1 defines `email` as the primary key and defines no `id` column. The registry and production evidence were not changed; this inventory row now follows the authoritative migration source (`PRIMARY(email)`). This discrepancy must be resolved in a separately reviewed registry/diagnostic change, not by changing the migration.

### 4.4 Foreign keys — 23

All 23 expected constraints are MISSING in the production evidence. Update rules are RESTRICT throughout the expected registry; delete rules are shown per the source definitions.

| # | Constraint | Source column → referenced column | ON DELETE | ON UPDATE | State | Source |
|---:|---|---|---|---|---|---|
| 1 | identity_decision_row_restrict_fk | identity_resolution_decisions.import_row_id → import_rows.id | RESTRICT | RESTRICT | MISSING | #17 |
| 2 | verification_decision_assertion_restrict_fk | verification_decisions.assertion_id → assertions.id | RESTRICT | RESTRICT | MISSING | #17 |
| 3 | import_reconciliation_batch_restrict_fk | import_reconciliation_events.import_batch_id → import_batches.id | RESTRICT | RESTRICT | MISSING | #17 |
| 4 | import_promotion_decision_row_restrict_fk | import_promotion_decisions.import_row_id → import_rows.id | RESTRICT | RESTRICT | MISSING | #17 |
| 5 | import_promotion_event_row_restrict_fk | import_promotion_events.import_row_id → import_rows.id | RESTRICT | RESTRICT | MISSING | #17 |
| 6 | import_promotion_event_batch_restrict_fk | import_promotion_events.import_batch_id → import_batches.id | RESTRICT | RESTRICT | MISSING | #17 |
| 7 | reverification_event_queue_restrict_fk | reverification_events.queue_id → data_reverification_queue.id | RESTRICT | RESTRICT | MISSING | #17 |
| 8 | structured_enrichment_row_restrict_fk | structured_enrichment_promotion_events.enrichment_import_row_id → enrichment_import_rows.id | RESTRICT | RESTRICT | MISSING | #17 |
| 9 | structured_enrichment_batch_restrict_fk | structured_enrichment_promotion_events.import_batch_id → import_batches.id | RESTRICT | RESTRICT | MISSING | #17 |
| 10 | import_conflict_event_restrict_fk | import_conflict_events.import_conflict_id → import_conflicts.id | RESTRICT | RESTRICT | MISSING | #17 |
| 11 | governed_run_event_restrict_fk | governed_execution_run_events.run_id → governed_execution_runs.id | RESTRICT | RESTRICT | MISSING | #17 |
| 12 | ssr_source_fk | source_snapshot_reconciliation_requests.source_id → sources.id | CASCADE | RESTRICT | MISSING | #16 |
| 13 | ssr_triggering_snapshot_fk | source_snapshot_reconciliation_requests.triggering_snapshot_id → source_snapshots.id | CASCADE | RESTRICT | MISSING | #16 |
| 14 | ssr_current_snapshot_fk | source_snapshot_reconciliation_requests.current_snapshot_id → source_snapshots.id | CASCADE | RESTRICT | MISSING | #16 |
| 15 | ssr_expected_previous_fk | source_snapshot_reconciliation_requests.expected_previous_snapshot_id → source_snapshots.id | SET NULL | RESTRICT | MISSING | #16 |
| 16 | ssr_recorded_previous_fk | source_snapshot_reconciliation_requests.recorded_previous_snapshot_id → source_snapshots.id | SET NULL | RESTRICT | MISSING | #16 |
| 17 | mp_run_policy_fk | monitoring_policy_runs.source_monitoring_policy_id → source_monitoring_policies.id | RESTRICT | RESTRICT | MISSING | #18 |
| 18 | mp_run_exec_fk | monitoring_policy_runs.execution_run_id → governed_execution_runs.id | SET NULL | RESTRICT | MISSING | #18 |
| 19 | mp_source_run_fk | monitoring_policy_run_sources.monitoring_policy_run_id → monitoring_policy_runs.id | RESTRICT | RESTRICT | MISSING | #18 |
| 20 | mp_source_source_fk | monitoring_policy_run_sources.source_id → sources.id | RESTRICT | RESTRICT | MISSING | #18 |
| 21 | mp_source_retrieval_fk | monitoring_policy_run_sources.retrieval_run_id → source_retrieval_runs.id | SET NULL | RESTRICT | MISSING | #18 |
| 22 | mp_source_observation_fk | monitoring_policy_run_sources.observation_id → source_change_observations.id | SET NULL | RESTRICT | MISSING | #18 |
| 23 | mai_active_alert_fk | monitoring_alert_identities.active_alert_id → monitoring_alerts.id | SET NULL | RESTRICT | MISSING | #18 |

The authoritative acceptance registry also checks exact source schema/table/column, referenced schema/table/column, ordinal position and actions. A future migration preflight must test source-column compatibility, referenced key type/unsignedness, orphan rows and exact action semantics; do not weaken RESTRICT/CASCADE/SET NULL behavior to make a migration pass.

### 4.5 Triggers — 24

All 24 expected triggers are MISSING. Twenty-two are the audit UPDATE/DELETE trigger pair for eleven tables; two are identity-decision guards. The source migration is #17. Definitions must be checked by normalized contract/fingerprint and never copied into production evidence as raw trigger bodies.

| # | Trigger | Table | Timing / event / orientation | Contract | State |
|---:|---|---|---|---|---|
| 1 | audit_immut_5e1df25372fff553_u | security_audit_events | BEFORE UPDATE ROW | append_only | MISSING |
| 2 | audit_immut_5e1df25372fff553_d | security_audit_events | BEFORE DELETE ROW | append_only | MISSING |
| 3 | audit_immut_493307493f91dfc1_u | verification_decisions | BEFORE UPDATE ROW | append_only | MISSING |
| 4 | audit_immut_493307493f91dfc1_d | verification_decisions | BEFORE DELETE ROW | append_only | MISSING |
| 5 | audit_immut_b04f820f0d4b68b3_u | provenance_review_events | BEFORE UPDATE ROW | append_only | MISSING |
| 6 | audit_immut_b04f820f0d4b68b3_d | provenance_review_events | BEFORE DELETE ROW | append_only | MISSING |
| 7 | audit_immut_f9de606bfd4c935d_u | import_promotion_decisions | BEFORE UPDATE ROW | append_only | MISSING |
| 8 | audit_immut_f9de606bfd4c935d_d | import_promotion_decisions | BEFORE DELETE ROW | append_only | MISSING |
| 9 | audit_immut_f8fe18d3e78fdef2_u | import_promotion_events | BEFORE UPDATE ROW | append_only | MISSING |
| 10 | audit_immut_f8fe18d3e78fdef2_d | import_promotion_events | BEFORE DELETE ROW | append_only | MISSING |
| 11 | audit_immut_72992154e9e26525_u | reverification_events | BEFORE UPDATE ROW | append_only | MISSING |
| 12 | audit_immut_72992154e9e26525_d | reverification_events | BEFORE DELETE ROW | append_only | MISSING |
| 13 | audit_immut_3ba3ddd28550296d_u | structured_enrichment_promotion_events | BEFORE UPDATE ROW | append_only | MISSING |
| 14 | audit_immut_3ba3ddd28550296d_d | structured_enrichment_promotion_events | BEFORE DELETE ROW | append_only | MISSING |
| 15 | audit_immut_67d791570898e61c_u | official_source_refresh_events | BEFORE UPDATE ROW | append_only | MISSING |
| 16 | audit_immut_67d791570898e61c_d | official_source_refresh_events | BEFORE DELETE ROW | append_only | MISSING |
| 17 | audit_immut_e6d938d7ffee7729_u | import_conflict_events | BEFORE UPDATE ROW | append_only | MISSING |
| 18 | audit_immut_e6d938d7ffee7729_d | import_conflict_events | BEFORE DELETE ROW | append_only | MISSING |
| 19 | audit_immut_17f41e0249a768b1_u | import_reconciliation_events | BEFORE UPDATE ROW | reconciliation_detach | MISSING |
| 20 | audit_immut_17f41e0249a768b1_d | import_reconciliation_events | BEFORE DELETE ROW | append_only | MISSING |
| 21 | audit_immut_0c11b0a7b877443d_u | governed_execution_run_events | BEFORE UPDATE ROW | append_only | MISSING |
| 22 | audit_immut_0c11b0a7b877443d_d | governed_execution_run_events | BEFORE DELETE ROW | append_only | MISSING |
| 23 | identity_decision_update_guard | identity_resolution_decisions | BEFORE UPDATE ROW | identity_key_enrichment | MISSING |
| 24 | identity_decision_delete_guard | identity_resolution_decisions | BEFORE DELETE ROW | identity_decision_delete | MISSING |

The 24-trigger count includes 22 audit-table guards and the two identity decision guards; it must not be restated as 24 audit pairs. The trigger contract is defined by the migration and the acceptance registry. Trigger body text is deliberately excluded from this report.

### 4.6 Migration ledger — 18

The exact ordered identifiers and source file mapping are in Section 3. The production evidence reports `record_count=0`, `duplicate_count=0`, empty `batches`, and `batch_values_valid=true` for every one. All 18 source files exist on main. No source file is classified missing. The ledger inventory did not report any unexpected identifiers in this target set; a full comparison of every unrelated production ledger row is outside the evidence fields preserved in this artifact and remains NOT_CHECKED.

## 5. Dependency graph and proposed execution order

This is a source-derived dependency ordering for future planning, not permission to execute. The timestamp sequence is not sufficient by itself: the following prerequisites and data gates are material.

```text
#1 password_reset_tokens ──────────────────────────────────────────────┐
#2 users.must_change_password ─────────────────────────────────────────┤
#3 import_batches.duplicate_rows ──────────────────────────────────────┤
#4 structured_enrichment_promotion_events ─┐                           │
#5 governed_execution_leases ──────────────┐│                           │
#6 governed_execution_runs + run_events ───┼┼──> #17 retention FKs/triggers
#7 retrieval idempotency ──────────────────┐│                           │
#8 snapshot/observation idempotency ───────┼┼──> #18 monitoring FKs
#9 discovery outcome idempotency ─────────┤│
#10 change-event identity ────────────────┤│
#11 reverification identities ────────────┼┼──> #17 retention FK
#12 alert identities + audit idempotency ─┼┼──> #18 active-alert FK
#13 monitoring policy runs/checkpoints ───┴┴──> #18 monitoring FKs
#14 discovery candidate identity keys ───────── (independent after parents)
#15 conflict identity keys + event table ───────> #17 retention FK/triggers
#16 snapshot reconciliation requests ─────────── (requires sources/snapshots)
#17 append-only retention guards <────────────── #4, #6, #11, #12, #15
#18 monitoring FKs/heartbeat <─────────────────── #5–#7, #9, #12–#13
```

Recommended planning waves:

1. **Baseline and preconditions (no DDL):** re-confirm application revision, migration file hashes, all parent table/column definitions, ledger semantics, open queue/alert records, orphan/duplicate checks, and data volume/locking implications.
2. **Independent low-coupling additions:** #1–#3, subject to exact existing-definition guards; #7, #9 and #10 after uniqueness preflights; #8 only after duplicate and nullability checks.
3. **New identity/event structures:** #4, #5–#6, #11–#13, #15–#16, in dependency order and with their own postconditions. Do not merge this into one opaque transaction: MySQL/MariaDB DDL may implicitly commit.
4. **Retention enforcement:** #17 only after every referenced table exists and all eleven orphan checks pass. Validate each FK and each trigger contract.
5. **Monitoring relationships:** #18 only after governed runs, retrieval/observation, policy/checkpoint and alert identity prerequisites exist; validate all six FK relationships and heartbeat behavior.
6. **Post-change evidence:** re-run the protected read-only inventory and reconcile every expected object, migration ledger row, orphan/duplicate check and protected record mapping.

The order above is provisional. Actual migration timestamps, exact source guards, FK dependencies and engine DDL behavior govern the final plan. Re-evaluate the graph after the full data-compatibility investigation and isolated replay; do not reorder solely to make a batch convenient.

## 6. Existing-data compatibility risks and stop conditions

| Risk | Source-backed condition | Required evidence before any separately authorized execution |
|---|---|---|
| Existing schema drift | #1–#3 and the other guarded migrations reject incompatible pre-existing column/table definitions. | Exact type, unsignedness, length, nullability, default, index and table option comparison. |
| Duplicate unique-key values | #8 checks duplicate non-NULL `current_snapshot_id`; #7 and #12 check duplicate open queue/alert identities; nullable unique keys in #7, #9, #10, #14 and #15 depend on their exact NULL semantics. | Full aggregate duplicate checks for every target unique key, with deterministic remediation design; never delete or merge rows by guess. |
| Five open reverification records | Production aggregate count is five; per-record identity mapping is NOT_CHECKED. #11 aborts if duplicate open subject/attribute rows exist. | Preserve all five records; establish sanitized one-to-one identity mapping and prove no duplicate-open conflict before #11. |
| Two open monitoring alerts | Production aggregate count is two; per-record mapping is NOT_CHECKED. #12 aborts on duplicate open dedupe keys. | Preserve both alerts; establish exact dedupe identity mapping and prove no duplicate-open conflict before #12/#18. |
| Orphan relationships | Seven of 18 orphan checks passed; eleven were NOT_CHECKED because prerequisites were missing. #17 and #18 have explicit orphan preconditions. | Evaluate every orphan check after prerequisite tables/columns are available; zero-orphan results must be evidenced per relationship. |
| Audit immutability | #17 installs BEFORE UPDATE/DELETE guards, including a special reconciliation-detach contract. | Confirm application workflows do not rely on prohibited updates/deletes; verify all 24 trigger definitions and expected behavior in isolated engine tests. |
| FK delete behavior | #16 includes CASCADE and SET NULL; #17 includes RESTRICT; #18 includes RESTRICT and SET NULL. | Confirm source/target types and data relationships; review cascade blast radius and nullable columns before adding constraints. |
| Heartbeat compatibility | #13/#18 introduce monitoring run/checkpoint fields and heartbeat semantics. | Inspect existing run history and NULL heartbeat population; #18 performs a data update setting heartbeat_at = started_at where heartbeat_at IS NULL. Quantify affected rows and document exact backfill/default semantics and idempotent rerun behavior. |
| DDL atomicity / locks | Several migrations create tables, indexes, constraints and triggers. MySQL-family DDL may implicitly commit. | Engine-specific rehearsal, expected lock/metadata-lock duration, online-DDL support, statement-by-statement stop points and restore readiness. |
| Historical migration ledger | All 18 ledger entries are absent although all source files exist. | Confirm the exact deployed code revision and ledger table semantics; do not manually insert ledger rows to disguise missing schema. |

No row payloads or sensitive application values were retrieved into this report. The evidence preserves only aggregate counts and ordinal-only queue/alert mapping states.

## 7. Protected records and outstanding checks

| Protected item | Observed count | Mapping / check state | Required handling |
|---|---:|---|---|
| Open reverification queue records | 5 | `NOT_CHECKED` | Preserve all records and event history. No merge, deletion, reassignment or identity backfill based on aggregate counts. |
| Open monitoring alerts | 2 | `NOT_CHECKED` | Preserve both alerts and history. No dedupe, deletion, closure or identity backfill based on aggregate counts. |
| Orphan checks | 18 total | 7 PASS; 11 NOT_CHECKED | Complete all skipped checks once prerequisites are available. |
| Duplicate checks | 14 total | 3 PASS; 11 NOT_CHECKED | Complete all skipped checks and validate every unique-key precondition. |
| Table definition equivalence | 39 expected | Presence known; full definition comparison not asserted for every present table | Perform source-to-production definition comparison before any DDL. |
| Full unrelated migration-ledger inventory | Not established by target rows | NOT_CHECKED | Review complete ledger for unexpected rows, duplicate identifiers and batch anomalies using approved evidence. |
| Backup and restore readiness | Deferred by task instruction | NOT_CHECKED | Separate future workstream; no backup or restore in Phase 1D.1. |

## 8. Unknowns and investigation requirements

1. Exact data-level duplicate/orphan status for the eleven skipped orphan checks and eleven skipped duplicate checks.
2. The per-record mapping for five open queue rows and two open monitoring alerts.
3. Complete definition equivalence for every present production table, including columns not in the 50-item target list, indexes, constraints, triggers and table options.
4. Exact deployed application revision and source-file hash agreement at the point of any future change window.
5. Full migration-ledger contents outside the 18 target identifiers, including any unexpected or duplicate rows and batch ordering.
6. Actual production row counts, key distributions, NULL distributions, index selectivity, table sizes and lock/DDL duration; these were not part of this sanitized artifact.
7. Exact heartbeat population and backfill effect for #18, plus rollback/retry behavior after partial DDL.
8. Current backup validity and restore rehearsal; explicitly deferred from this task.
9. Whether any manual schema changes or partial migration remnants exist beyond the expected-object evidence. The current report must not infer their absence.

## 9. Phase 1D.1 acceptance checklist

- [x] Verify current `main` SHA and merged PR #1065 / #1066.
- [x] Read the authoritative Phase 1C closure report and source registries.
- [x] Match all 18 expected migration identifiers to actual existing source files.
- [x] Inspect the protected production run and download its artifact.
- [x] Independently verify GitHub archive SHA-256 and evidence JSON SHA-256 against the artifact digest/manifest/checksum.
- [x] Reconcile the six inventory counts: 39 tables, 50 columns, 23 indexes, 23 FKs, 24 triggers, 18 ledger entries.
- [x] Preserve five open reverification records and two open monitoring alerts as NOT_CHECKED mappings.
- [x] Record migration dependencies, execution-order proposal, compatibility risks and unresolved checks.
- [x] Keep this task documentation-only: no production SQL, database connection, migration, DDL/DML, repair, backfill, workflow dispatch, backup or restore.
- [ ] Obtain review of this report and independently verify every source-to-object mapping.
- [ ] Complete the investigation items in Section 8 before a Phase 1D.2 production recovery plan can be considered.

**Phase 1D.1 status: PARTIALLY COMPLETE pending independent review of this inventory.** The evidence-backed inventory and PR deliverable are prepared; this document does not close production readiness or authorize Phase 1D.2 execution.

## 10. Recommended Phase 1D.2 work

Prepare a separate, reviewed **production recovery readiness and migration execution design**—not an execution request. It should include:

1. A source-hash-pinned migration manifest and dependency DAG.
2. Per-migration preconditions, exact DDL/data effects, expected duration/locks, postconditions, idempotency and stop/rollback criteria for MariaDB 11.8.9.
3. A sanitized record-preservation plan for all five queue records and two alerts, with explicit identity mapping and audit history retention.
4. Completion of all 18 orphan and 14 duplicate checks, including checks previously NOT_CHECKED.
5. A full existing-schema and full migration-ledger reconciliation, including unexpected objects/rows.
6. Disposable MariaDB 11.8.9 rehearsal and retained MySQL 8.4 regression acceptance; include partial-failure and rerun tests without restarting completed Test 1A or Test 1B.
7. A separate backup and successful restore rehearsal workstream, intentionally excluded from Phase 1D.1.
8. A distinct production change authorization and protected-environment approval, followed by preflight identity verification and a post-change protected read-only evidence run.

No step in this recommendation grants permission to execute production changes. Production migration authorization must be explicit, separate and limited to an approved change plan.

## Appendix A — authoritative repository references

- [Phase 1C Sessions 1–4 schema reconciliation and final closure](PHASE_1C_SESSIONS_1_TO_4_SCHEMA_RECONCILIATION_2026-11-06.md)
- [Phase 1C all-18 migration source review](PHASE_1C_TEST_1B_ALL_18_MIGRATION_SOURCE_REVIEW_2026-11-06.md)
- [Phase 1C migration ledger reconciliation](../PHASE_1C_MIGRATION_LEDGER_RECONCILIATION.md)
- [All-18 read-only preflight registry](../../scripts/diagnostics/phase1c-test1b-consolidated-all18-preflight.php)
- [Read-only diagnostic implementation and object registry](../../scripts/phase1c/readonly-schema-diagnostic.php)
- [Read-only evidence schema](../../scripts/phase1c/readonly-diagnostic.schema.json)
- [Foreign-key and trigger acceptance registry](../../tests/Support/verify-phase1c-testb-migration-objects.php)
- [Migration schema guard](../../app/Support/Database/MigrationSchemaGuard.php)
- [Diagnostic acceptance tests](../../tests/Support/phase1c-readonly-diagnostic-acceptance.php)
- [Phase 1C read-only evidence artifact run #42965183303](https://github.com/mattari20/estudent-listing/actions/runs/42965183303)

