# Phase 1D.2 — Production Recovery Readiness Investigation

**Repository:** `mattari20/estudent-listing`  
**Investigation date:** 2026-11-11  
**Verified base:** `main` at `d7b39232f251b80a10d74c0749dec79536da64c2`  
**Phase 1D.1 inventory:** [PR #1067](https://github.com/mattari20/estudent-listing/pull/1067), merged  
**Phase 1D.1 closeout:** [PR #1068](https://github.com/mattari20/estudent-listing/pull/1068), merged at the verified base SHA  
**Production evidence:** [workflow run #42965183303](https://github.com/mattari20/estudent-listing/actions/runs/42965183303)  
**Disposition:** READ-ONLY INVESTIGATION; PRODUCTION NOT READY; NO EXECUTION AUTHORIZED

## 1. Executive summary

This report advances Phase 1D.1's source inventory into a production recovery readiness investigation. It reuses the existing protected production evidence and prior isolated-engine acceptance records. It does not repeat Test 1A or Test 1B, rerun production diagnostics, or execute any production SQL, migration, DDL, DML, repair, or backfill.

The main findings are:

1. **Password reset registry decision:** migration #1 and the configured Laravel password broker use an email-keyed `password_reset_tokens` table. The intended primary key is `PRIMARY(email)`; the read-only diagnostic's `PRIMARY(id)` expectation is inconsistent with the migration and should be corrected in a separate source-code PR after review. No migration change is justified by the evidence.
2. **Orphan evidence:** the existing artifact reports seven PASS and eleven NOT_CHECKED. The seven PASS checks are the first seven #17 retention relationships. Four remaining #17 relationships and all seven #18 monitoring relationships were skipped because required tables were absent. Skipped checks remain NOT_CHECKED.
3. **Duplicate evidence:** three checks passed (open reverification identity, open monitoring dedupe key, and non-NULL observation current-snapshot identity); eleven checks were NOT_CHECKED because the target tables/columns were absent. Nullable unique keys require non-NULL duplicate grouping; multiple NULLs are valid under MySQL/MariaDB unique-index semantics.
4. **Protected records:** five open reverification records and two open monitoring alerts remain intact. The evidence does not establish individual identity mappings. A future authorized read-only protocol must reconcile each record one-to-one using sanitized ordinal references.
5. **Schema/ledger scope:** the existing collector records broad metadata for its 39-table scope, but its expected-object registry is not a complete equivalence contract for every table. The existing migration-ledger check is limited to the 18 target identifiers and cannot establish global ledger health.
6. **Heartbeat and DDL:** migration #18 performs a bulk update of all NULL `heartbeat_at` values from `started_at`. Affected-row count, NULL-started-at count, lock duration and live-run contention remain unknown. MySQL/MariaDB DDL may commit statement-by-statement; multi-step migrations cannot be treated as atomic.
7. **Recovery order:** the proposed waves are dependency-driven and end with migrations #17 and #18 only after all prerequisite tables, columns, data checks and definition comparisons are established. This is planning, not authorization.

**Overall result: NOT PRODUCTION RECOVERY READY.** Backup/restore readiness remains NOT_CHECKED by design and is a mandatory future gate. No production change may occur until a separate reviewed change plan, verified backup, successful restore rehearsal and explicit production authorization exist.

## 2. Verified repository and evidence baseline

### 2.1 Repository baseline

| Item | Verified result |
|---|---|
| Default branch | `main` |
| Base commit | `d7b39232f251b80a10d74c0749dec79536da64c2` |
| PR #1067 | Closed and merged |
| PR #1068 | Closed and merged |
| Phase 1D.1 inventory | `docs/audits/PHASE_1D_1_PRODUCTION_MIGRATION_RECONCILIATION_INVENTORY.md` |
| Phase 1D.1 closeout | `docs/audits/PHASE_1D_1_FINAL_CLOSEOUT_2026-11-11.md` |
| Branch created for this report | `phase1d2/production-recovery-readiness-investigation-2026-11-11` |

The branch was created directly from the verified latest `main` SHA. This report is documentation-only. No migration, application, diagnostic, workflow, or acceptance-test source is changed.

### 2.2 Reused production artifact

The existing artifact was re-identified through GitHub Actions metadata; it was not regenerated or rerun.

| Evidence field | Recorded value |
|---|---|
| Run | `42965183303` |
| Run result | `completed / success` |
| Event / ref | `workflow_dispatch / main` |
| Source SHA | `0a5e680db4f0ef9a49dd55fd146f68c00c3a0661` |
| Artifact | `phase1c-readonly-diagnostic-42965183303` |
| Artifact ID | `13831501107` |
| Artifact size | 16,777 bytes |
| GitHub archive digest | `sha256:a7fc5ab49900148ec925307a765d8427a8215731ac2e92296b4e3249f09d79ab` |
| Evidence JSON SHA-256 | `c4cfd921a0112bbc6948116c7009fd35f3a7c33a9fc12eb950abd96c5a95dbf4` |
| Artifact expiry shown by GitHub | 2026-11-18 05:25:49 UTC |

The merged Phase 1D.1 inventory records the archive and JSON checksum verification, and notes that the manifest's compressed-transfer digest could not be independently recomputed from the ZIP because that compressed payload was not separately included. Preserve the original artifact and its checksums as historical evidence; do not rewrite its results to reflect later source corrections.

The artifact reports MariaDB `11.8.9-MariaDB-log`, complete schema metadata visibility, complete trigger metadata visibility, a `READ ONLY` transaction and `ROLLED_BACK` outcome.

### 2.3 Production inventory observed

| Category | Expected | Present / PASS | Missing | NOT_CHECKED |
|---|---:|---:|---:|---:|
| Tables | 39 | 28 | 11 | 0 |
| Expected columns | 50 | 2 | 48 | 0 |
| Named expected indexes | 23 | 0 | 23 | 0 |
| Expected foreign keys | 23 | 0 | 23 | 0 |
| Expected triggers | 24 | 0 | 24 | 0 |
| Target migration-ledger entries | 18 | 0 | 18 | 0 |
| Orphan checks | 18 | 7 | 0 | 11 |
| Duplicate checks | 14 | 3 | 0 | 11 |

The two expected columns reported present were `import_batches.duplicate_rows` and `source_change_observations.current_snapshot_id`. The 23 named indexes are a selected registry, not an exhaustive count of every index created by the 18 migration files.

The artifact also reports five open `data_reverification_queue` records and two open `monitoring_alerts`. All seven per-record identity mappings were NOT_CHECKED. No row values or mappings are inferred here.

### 2.4 Existing isolated-engine evidence

The repository's existing Phase 1C acceptance workflow defines MySQL 8.4 and MariaDB 11.8.9 disposable-database jobs. The Phase 1C closure records prior acceptance for those engines and explicitly says that acceptance applies to the previously tested code. This Phase 1D.2 investigation does not rerun those tests or claim that they validate production data, the new readiness design, or future production DDL timings.

Relevant sources:
- [Phase 1C MySQL migration recovery workflow](../../.github/workflows/phase-1c-testb-migration-recovery-mysql.yml)
- [Phase 1C read-only diagnostic CI workflow](../../.github/workflows/phase1c-readonly-diagnostic-ci.yml)
- [Phase 1C sessions 1–4 schema reconciliation](PHASE_1C_SESSIONS_1_TO_4_SCHEMA_RECONCILIATION_2026-11-06.md)
- [Phase 1C all-18 source review](PHASE_1C_TEST_1B_ALL_18_MIGRATION_SOURCE_REVIEW_2026-11-06.md)

No Test 1A or Test 1B execution was restarted.

## 3. Workstream A — Password-reset primary-key registry decision

### 3.1 Source findings

- [Migration #1](../../database/migrations/2026_10_17_000001_create_password_reset_tokens_table.php) creates `password_reset_tokens` with `email` as its primary key, followed by `token` and nullable `created_at`. It defines no `id` column.
- [Laravel authentication configuration](../../config/auth.php) configures the `users` password broker to use the `password_reset_tokens` table.
- The repository search found no application-level custom password-reset implementation or explicit `password_reset_tokens.id` consumer. The repository's declared dependency is Laravel Framework `^13.0` in [composer.json](../../composer.json).
- [Read-only diagnostic registry](../../scripts/phase1c/readonly-schema-diagnostic.php) currently declares the named index as `PRIMARY(id)`.
- [Evidence JSON schema](../../scripts/phase1c/readonly-diagnostic.schema.json) constrains the count and shape of index reconciliation entries but does not itself define the index's expected columns.
- [Test B object acceptance helper](../../tests/Support/verify-phase1c-testb-migration-objects.php) checks required tables, columns, named indexes, FKs and triggers; its required-index list does not independently assert the password reset table's primary-key column.

### 3.2 Decision

**The source-defined intended primary key is `password_reset_tokens.PRIMARY(email)`.** This matches the conventional Laravel database password-token table shape used by the configured password broker. There is no source evidence supporting a new `id` column or a migration rewrite to PRIMARY(id).

The diagnostic registry should be corrected from `['password_reset_tokens','PRIMARY',['id'],true]` to the source-defined email primary-key contract in a separate, narrowly scoped code PR. That PR should also add an explicit acceptance assertion for the primary-key column and verify behavior on both supported engines. Do not combine that code correction into this investigation report.

### 3.3 Historical evidence interpretation

The old production artifact observed the `password_reset_tokens` table as MISSING. Therefore, it did not observe an actual primary-key definition that can be reinterpreted. Its registry entry records the historical expected-index contract as `PRIMARY(id)`; preserve that raw artifact unchanged. A corrected diagnostic must have a new source revision and a new evidence run if a future authorized diagnostic is performed. The old artifact remains valid as a record that the table was absent, but its expected-index definition was source-inconsistent.

**Gate:** no recovery design should treat the index mismatch as a production data defect. Resolve the registry/test contract first; do not alter the migration or create an `id` column to satisfy the stale registry.

## 4. Workstream B — Full 18-check orphan matrix

### 4.1 Evaluation rules

The diagnostic emits:
- `PASS` only when its query executes and finds no orphan;
- `FAIL` when an orphan exists;
- `NOT_CHECKED` when a prerequisite table or column is absent;
- `UNKNOWN` when metadata or query execution is insufficient.

The existing artifact's seven PASS results are the first seven #17 retention relationships. The remaining four #17 relationships and all seven #18 relationships were NOT_CHECKED. These are historical states from the existing artifact, not fresh results.

For every future evaluation, inspect child and parent existence, both key definitions, column nullability, type/signedness and the complete FK definition first. A non-NULL child reference is orphaned only when no parent row matches. Nullable optional relationships must exclude NULL child keys. If the source column is non-nullable, verify that the production definition matches before relying on that assumption.

### 4.2 Orphan relationship matrix

| # | Stable check identifier | Child → parent | Nullable child reference? | Migration | Prior evidence | Evidence still required / stop condition |
|---:|---|---|---|---|---|---|
| 1 | `orphan_identity_decisions_without_import_rows` | `identity_resolution_decisions.import_row_id → import_rows.id` | Source schema defines NOT NULL | #17 | PASS | Reconfirm exact column type and zero unmatched references. Stop if any unmatched row or schema drift. |
| 2 | `orphan_verification_decisions_without_assertions` | `verification_decisions.assertion_id → assertions.id` | Expected non-null FK; verify deployed definition | #17 | PASS | Aggregate orphan count and exact FK source/target/action. Stop on any orphan. |
| 3 | `orphan_reconciliation_events_without_batches` | `import_reconciliation_events.import_batch_id → import_batches.id` | Expected non-null FK; verify deployed definition | #17 | PASS | Check batch ownership separately from nullable row detachment. Stop on any unmatched batch. |
| 4 | `orphan_promotion_decisions_without_rows` | `import_promotion_decisions.import_row_id → import_rows.id` | Expected non-null FK; verify deployed definition | #17 | PASS | Reconfirm exact key types and zero unmatched references. Stop on any orphan. |
| 5 | `orphan_promotion_events_without_rows` | `import_promotion_events.import_row_id → import_rows.id` | Expected non-null FK; verify deployed definition | #17 | PASS | Reconfirm exact key types and zero unmatched references. Stop on any orphan. |
| 6 | `orphan_promotion_events_without_batches` | `import_promotion_events.import_batch_id → import_batches.id` | Expected non-null FK; verify deployed definition | #17 | PASS | Reconfirm exact key types and zero unmatched references. Stop on any orphan. |
| 7 | `orphan_reverification_events_without_queue_rows` | `reverification_events.queue_id → data_reverification_queue.id` | Expected non-null FK; verify deployed definition | #17 | PASS | Reconfirm queue/event relationship without exposing row values. Stop on any orphan. |
| 8 | `orphan_structured_enrichment_events_without_rows` | `structured_enrichment_promotion_events.enrichment_import_row_id → enrichment_import_rows.id` | Expected non-null FK; verify deployed definition | #17 | NOT_CHECKED | Requires event table and parent table/columns. Run aggregate join only after schema preconditions; stop on any orphan. |
| 9 | `orphan_structured_enrichment_events_without_batches` | `structured_enrichment_promotion_events.import_batch_id → import_batches.id` | Expected non-null FK; verify deployed definition | #17 | NOT_CHECKED | Requires event table and batch key. Stop on any orphan. |
| 10 | `orphan_conflict_events_without_conflicts` | `import_conflict_events.import_conflict_id → import_conflicts.id` | Expected non-null FK; verify deployed definition | #17 | NOT_CHECKED | Requires event table, conflicts table and compatible key. Stop on any orphan. |
| 11 | `orphan_governed_run_events_without_runs` | `governed_execution_run_events.run_id → governed_execution_runs.id` | Expected non-null FK; verify deployed definition | #17 | NOT_CHECKED | Requires both governed execution tables and matching run-key definitions. Stop on any orphan. |
| 12 | `orphan_monitoring_runs_without_policy` | `monitoring_policy_runs.source_monitoring_policy_id → source_monitoring_policies.id` | Expected non-null FK; verify deployed definition | #18 | NOT_CHECKED | Requires policy/run tables and compatible key types. Stop on any orphan. |
| 13 | `orphan_monitoring_runs_without_governed_run` | `monitoring_policy_runs.execution_run_id → governed_execution_runs.id` | Yes; diagnostic explicitly excludes NULL | #18 | NOT_CHECKED | Evaluate only non-NULL execution_run_id values. Stop on any non-NULL unmatched reference. |
| 14 | `orphan_monitoring_sources_without_policy_run` | `monitoring_policy_run_sources.monitoring_policy_run_id → monitoring_policy_runs.id` | Expected non-null FK; verify deployed definition | #18 | NOT_CHECKED | Requires run/source tables. Stop on any orphan. |
| 15 | `orphan_monitoring_sources_without_source` | `monitoring_policy_run_sources.source_id → sources.id` | Expected non-null FK; verify deployed definition | #18 | NOT_CHECKED | Requires source/run-source tables. Stop on any orphan. |
| 16 | `orphan_monitoring_sources_without_retrieval_run` | `monitoring_policy_run_sources.retrieval_run_id → source_retrieval_runs.id` | Yes; diagnostic explicitly excludes NULL | #18 | NOT_CHECKED | Evaluate only non-NULL retrieval_run_id values. Stop on any unmatched reference. |
| 17 | `orphan_monitoring_sources_without_observation` | `monitoring_policy_run_sources.observation_id → source_change_observations.id` | Yes; diagnostic explicitly excludes NULL | #18 | NOT_CHECKED | Evaluate only non-NULL observation_id values. Stop on any unmatched reference. |
| 18 | `orphan_monitoring_alert_identities_without_active_alert` | `monitoring_alert_identities.active_alert_id → monitoring_alerts.id` | Yes; diagnostic explicitly excludes NULL | #18 | NOT_CHECKED | Evaluate only non-NULL active_alert_id values. Stop on any unmatched reference. |

**Count check:** 18 total = 7 historical PASS + 11 historical NOT_CHECKED. No historical FAIL is recorded.

### 4.3 NULL semantics and special reconciliation detachment

The four nullable #18 relationships (execution run, retrieval run, observation, active alert) have explicit `IS NOT NULL` conditions in the diagnostic's orphan SQL before treating a missing parent as an orphan. The other #17 relationships are expected non-null FK columns; their exact deployed nullability must still be checked as part of full schema equivalence.

Do not confuse `import_reconciliation_events.import_row_id` with check #3. That field is intentionally nullable and may be detached from a deleted import row while preserving the reconciliation event. The #17 FK/orphan check is for the separate `import_batch_id` relationship. The migration's append-only trigger permits only the defined one-time non-NULL-to-NULL `import_row_id` detach while keeping all other protected fields unchanged. This exception must remain intact.

### 4.4 Required future evidence

For each check, retain only the stable check ID, state, aggregate count, prerequisite status and query/schema revision. Do not export child IDs, parent IDs, row payloads or sensitive values. Any nonzero orphan count is a hard stop; no automatic deletion, reassignment, nulling or synthetic parent creation is permitted.

## 5. Workstream C — Full 14-check duplicate matrix

### 5.1 Evaluation rules

The historical artifact reports three PASS and eleven NOT_CHECKED. The three passed checks are the open reverification identity group, open monitoring-alert dedupe-key group and non-NULL observation current-snapshot ID group.

For nullable unique keys, the correct duplicate predicate groups only non-NULL values. MySQL and MariaDB permit multiple NULL values in a unique index; those NULLs are not duplicate identities. For open-record checks, only rows with `status='open'` participate because that is the source migration's identity scope. Do not infer duplicate absence from a missing column or index.

### 5.2 Duplicate precondition matrix

| # | Stable check identifier | Table / grouping key | Predicate / NULL rule | Migration relevance | Prior evidence | Required evidence / stop condition |
|---:|---|---|---|---|---|---|
| 1 | `duplicate_open_reverification_identity` | `data_reverification_queue(subject_type, subject_id, attribute)` | `status='open'`; group all three fields | #11 | PASS | Recompute open groups and reconcile exactly with the five open source records. Stop on any duplicate group. |
| 2 | `duplicate_open_monitoring_alert_dedupe_key` | `monitoring_alerts(dedupe_key)` | `status='open'`; group dedupe_key | #12 | PASS | Recompute open dedupe groups and reconcile exactly with two open alerts. Stop on any duplicate key. |
| 3 | `duplicate_observation_current_snapshot_id` | `source_change_observations(current_snapshot_id)` | Non-NULL values only | #8 | PASS | Verify column type/nullability and zero duplicate non-NULL snapshot references. Stop on any duplicate. |
| 4 | `duplicate_source_retrieval_idempotency_key` | `source_retrieval_runs(idempotency_key)` | Non-NULL values only | #7 | NOT_CHECKED | Requires key column; aggregate duplicate groups. Stop on duplicates or incompatible existing metadata. |
| 5 | `duplicate_source_snapshot_capture_key` | `source_snapshots(capture_key)` | Non-NULL values only | #8 | NOT_CHECKED | Requires capture key; aggregate duplicate groups. Stop on duplicates. |
| 6 | `duplicate_discovery_outcome_idempotency_key` | `institute_discovery_run_outcomes(idempotency_key)` | Non-NULL values only | #9 | NOT_CHECKED | Requires key column; aggregate duplicate groups. Stop on duplicates. |
| 7 | `duplicate_change_event_source_link` | `change_events(source_change_assertion_link_id)` | Non-NULL values only | #10 | NOT_CHECKED | Requires unsigned BIGINT key; aggregate duplicate groups. Stop on duplicates. |
| 8 | `duplicate_import_batch_discovery_identity` | `import_batches(discovery_identity_key)` | Non-NULL values only | #14 | NOT_CHECKED | Requires key column; aggregate duplicate groups. Stop on duplicates. |
| 9 | `duplicate_evidence_discovery_identity` | `evidence(discovery_identity_key)` | Non-NULL values only | #14 | NOT_CHECKED | Requires key column; aggregate duplicate groups. Stop on duplicates. |
| 10 | `duplicate_import_row_discovery_identity` | `import_rows(discovery_identity_key)` | Non-NULL values only | #14 | NOT_CHECKED | Requires key column; aggregate duplicate groups. Stop on duplicates. |
| 11 | `duplicate_assertion_discovery_identity` | `assertions(discovery_identity_key)` | Non-NULL values only | #14 | NOT_CHECKED | Requires key column; aggregate duplicate groups. Stop on duplicates. |
| 12 | `duplicate_identity_candidate_key` | `identity_match_candidates(identity_key)` | Non-NULL values only | #15 | NOT_CHECKED | Requires key column; aggregate duplicate groups. Stop on duplicates. |
| 13 | `duplicate_identity_decision_key` | `identity_resolution_decisions(decision_identity_key)` | Non-NULL values only | #15 | NOT_CHECKED | Requires key column; aggregate duplicate groups. Stop on duplicates. |
| 14 | `duplicate_import_conflict_identity_key` | `import_conflicts(identity_key)` | Non-NULL values only | #15 | NOT_CHECKED | Requires key column; aggregate duplicate groups. Stop on duplicates. |

**Count check:** 14 total = 3 historical PASS + 11 historical NOT_CHECKED. No historical FAIL is recorded.

### 5.3 Unique constraints not represented by the 14 legacy duplicate checks

The 14 checks are the diagnostic's explicit legacy-data duplicate registry. They are not every unique constraint introduced by all 18 migrations. New-table or partial-table unique contracts also require full structural verification before acceptance, including:
- #5 lease `task_key`;
- #6 governed run UUID and run-event identity/indexes;
- #11 identity table's `identity_key` and active queue mapping uniqueness;
- #12 alert identity `dedupe_key` and `active_alert_id` mapping uniqueness, plus the nullable audit idempotency key;
- #13 policy/run-source unique and supporting indexes;
- #15 conflict-event `idempotency_key` and conflict identity indexes;
- #16 reconciliation `identity_key` and supporting composite indexes.

For a genuinely absent new table, there are no legacy rows to duplicate; the create-table definition and its unique indexes still must be verified exactly. For a pre-existing or partially created table, do not assume it is empty: inventory its rows, inspect duplicate keys using the exact source grouping, and fail closed on any mismatch. The #15 `import_conflict_events.idempotency_key` is NOT NULL and differs from the nullable legacy-key checks.

### 5.4 Migration checks versus diagnostic checks

Migrations #8, #11 and #12 contain explicit duplicate guards for their relevant source identities. The diagnostic independently evaluates the 14 listed groups when prerequisite columns exist. For the remaining nullable unique-key migrations, uniqueness is enforced by the unique index but the existing diagnostic's duplicate registry supplies the preflight evidence. New-table unique indexes rely on exact create-table/partial-table reconciliation, not the 14-row legacy matrix.

Required behavior: any duplicate group, schema mismatch, or incomplete prerequisite must stop the affected migration wave. No record may be deleted, merged, rewritten or backfilled as an automatic response.

## 6. Workstream D — Protected identity mapping evidence protocol

### 6.1 Five open reverification queue records

Migration #11:
- checks duplicate open queue records grouped by `subject_type`, `subject_id`, and `attribute`;
- derives the identity key as SHA-256 of the JSON tuple `[subject_type, (int) subject_id, attribute]`;
- processes open rows in chunks of 500;
- verifies that existing identity-key mappings agree with their source tuple and that an existing queue-ID mapping does not point to a different key;
- inserts missing identity rows without rewriting the source queue records.

**Future read-only protocol:**
1. Confirm the current open-row aggregate count. The inherited observation is five; if a fresh separately authorized observation differs, report the new count and preserve the difference rather than forcing it to five.
2. Assign sanitized ordinals `Q-01` through `Q-05` to a stable snapshot of the five open source rows. Do not include database IDs or source values in the report.
3. Compute the same deterministic key in-memory or in a controlled isolated verifier from the exact migration tuple and PHP integer/string semantics. Do not print the raw tuple or hash.
4. For each ordinal, establish exactly one identity row with the expected key and `active_queue_id` pointing to that same source record.
5. Check reverse uniqueness: no two open source records share an identity key; no active queue ID maps to multiple identities; no identity key maps to another source tuple.
6. Verify the source row and its historical event rows remain present and unchanged across the read-only observation. Only aggregate counts and ordinal states are reportable.
7. Report each ordinal as `PASS`, `FAIL`, `NOT_CHECKED`, or `UNKNOWN`, plus aggregate counts.

A mapping is PASS only when all key, source, cardinality and reverse-uniqueness checks agree. Missing, multiple or conflicting mappings are a hard stop. Do not generate or insert mappings during investigation.

### 6.2 Two open monitoring alerts

Migration #12:
- checks duplicate open alerts grouped by `dedupe_key`;
- maps each open alert's `dedupe_key` to its `active_alert_id` in chunks of 500;
- rejects an existing dedupe key mapped to another alert and an alert ID already associated with a different key;
- adds a separate nullable unique `security_audit_events.monitoring_idempotency_key`.

**Future read-only protocol:**
1. Confirm the current open-alert aggregate count. The inherited observation is two; record a fresh count if authorized and do not force the historical count.
2. Assign sanitized ordinals `A-01` and `A-02`.
3. Establish exactly one identity row per open alert, with the exact dedupe key mapped to that alert's active ID.
4. Verify both directions of uniqueness and ensure no conflicting existing key/alert mapping exists.
5. Confirm alert source rows and historical/audit events remain present and unchanged.
6. Report only ordinal states and aggregate counts; never disclose alert IDs, dedupe keys, row payloads or sensitive values.

A missing or conflicting mapping is a hard stop. No alert may be closed, deleted, consolidated, reassigned or rewritten to make the identity check pass.

### 6.3 Preservation invariant

The five queue records and two alerts are protected business records. Their source rows, events and history remain intact. The identity tables are additional mappings, not replacements for the source records. A future migration may create mapping rows only after a separate approved execution plan and complete preflight; this Phase 1D.2 report does not authorize that work.

## 7. Workstream E — Full schema-equivalence specification

### 7.1 Scope

The Phase 1D.1 collector's 39-table scope and 50/23/23/24 expected-object registries are a reconciliation baseline, not a complete definition-equivalence contract for every production table. It captures `SHOW CREATE TABLE`, table options, column metadata and index metadata for scoped tables, plus FK and trigger metadata. A future comparison must normalize and compare every relevant definition field, not only names or presence.

### 7.2 Required comparison fields

| Object | Required metadata and equivalence checks |
|---|---|
| Table | Exact table name; base-table versus view; engine; row format; create options; table collation/charset; partitioning where applicable; table comment after safe normalization; full normalized `SHOW CREATE TABLE`. |
| Column | Name and ordinal order; data type and full column type; signedness; length; numeric precision/scale; datetime precision; character length/octet length; nullable; default value and whether SQL NULL; default expression; auto-increment; generated expression and stored/virtual mode; character set/collation; column key and extra attributes. |
| Primary / unique index | Name; uniqueness; ordered columns; sequence/ordinal; prefix length; index type; collation/order; visibility where supported; expression indexes where supported. |
| Secondary/composite index | Name; uniqueness; complete ordered key columns; prefix lengths; index type; included/expression parts if supported; sort direction and visibility where available. |
| Foreign key | Constraint name; child schema/table/column(s); column ordinal and composite mapping; parent schema/table/column(s); match option; update/delete actions; referenced key compatibility; enforcement state where engine exposes it. |
| Trigger | Name; schema/table; timing; event; row orientation; definer/security context where relevant; action statement/body; normalized body hash and semantic contract; ordering relative to other triggers where supported. |
| Character semantics | Database/table/column character set and collation; case/accent behavior relevant to unique keys; index prefix and maximum key-length compatibility. |
| Engine/options | InnoDB engine, row format, compression/encryption/partition options where exposed, and options that affect index/FK behavior. |

The comparator must distinguish SQL NULL defaults from literal string `'NULL'`; the existing `MigrationSchemaGuard` contains MariaDB 11.8 handling for this metadata ambiguity. It also normalizes integer display widths where MySQL 8 omits widths and MariaDB retains them, and treats InnoDB `NO ACTION` as equivalent to `RESTRICT` where appropriate.

### 7.3 Full-object result model

Each object should report `PASS`, `FAIL`, `MISSING`, `NOT_CHECKED`, or `UNKNOWN` with:
- stable object identifier;
- expected source revision and expected definition;
- observed normalized definition or safe hash;
- exact mismatch fields;
- metadata-visibility state;
- prerequisite objects;
- evidence timestamp and collector version.

Never infer full table equivalence from table presence. Never infer index equivalence from name alone. Never infer FK equivalence from a source column and target table without target column, ordinal, composite mapping and referential actions. Never infer trigger equivalence from name alone.

## 8. Workstream E2 — Full migration-ledger reconciliation specification

### 8.1 Existing limitation

The existing [migration-ledger reconciliation script](../../scripts/diagnostics/phase1c-migration-ledger-reconciliation.php) selects only the 18 target migration names from the `migrations` table. It records matching rows, duplicate count, batch values and selected required-object presence. It does not enumerate every ledger row or all deployed migration source files. Therefore it cannot establish that the remainder of the ledger is healthy.

The 18 target identifiers are listed in the [all-18 preflight registry](../../scripts/diagnostics/phase1c-test1b-consolidated-all18-preflight.php). The current production observation is zero matching rows for those 18 identifiers; it is not proof of global ledger integrity.

### 8.2 Required full comparison

A future authorized read-only ledger inspection must compare:

1. Every deployed migration source filename/identifier in the exact deployed revision.
2. Every row in the full Laravel `migrations` ledger, including migration name and batch.
3. The expected set of all deployed migration identifiers against all ledger identifiers.
4. Duplicate ledger rows per migration name.
5. Invalid batch values (non-numeric, zero/negative where prohibited, overflow or malformed representation).
6. Batch ordering and grouping anomalies, including migration ordering within a batch and gaps that require interpretation rather than automatic repair.
7. Ledger entries with no corresponding migration source file in the deployed revision.
8. Source files with no corresponding ledger row.
9. Ledger rows whose migration's expected objects are absent or definition-incompatible.
10. Objects introduced by a migration but present without a corresponding ledger row.
11. The 18 target migrations individually, preserving the existing `MISSING` observations until a new authorized observation changes them.

The report must distinguish expected historical migrations from genuinely unexpected entries. A source file absent from the current deployment is not automatically safe to delete from the ledger. A ledger row without an available source file is a hard review item, not an invitation to manually insert or delete ledger rows.

### 8.3 Safe output

Emit counts, sanitized migration identifiers, batch values and state categories only. Do not expose credentials, application row data or unrelated ledger payloads. No manual ledger insert/update/delete is permitted. No migration is marked applied based solely on object presence.

## 9. Workstream F — Migration #18 heartbeat backfill and DDL impact

### 9.1 Source behavior

Migration #18 adds `monitoring_policy_runs.heartbeat_at` if absent, validates an existing MySQL column as nullable TIMESTAMP when present, installs or verifies seven monitoring foreign keys, then executes:

```sql
UPDATE monitoring_policy_runs
SET heartbeat_at = started_at
WHERE heartbeat_at IS NULL
```

This is DML as part of the migration. It is not a metadata-only change. The inherited production artifact did not establish the row count, NULL distribution or actual update cost.

### 9.2 Required impact profile before any separate execution approval

| Measure | Required read-only evidence | Why it matters |
|---|---|---|
| Total rows | Aggregate count of `monitoring_policy_runs` | Establish scale of the target table. |
| NULL heartbeat rows | Count where `heartbeat_at IS NULL` | Upper bound on candidate updates. |
| NULL heartbeat and NULL started_at | Count where both are NULL | Such rows remain NULL after `SET heartbeat_at = started_at`; investigate before execution. |
| Non-NULL heartbeat rows | Aggregate count | Establish preserved values and expected unchanged population. |
| Timestamp ranges | Min/max of non-sensitive timestamps and precision metadata | Detect out-of-range or precision incompatibility without exporting rows. |
| Active run profile | Counts by safe run status and heartbeat-null state | Identify concurrent/active runs that may be touched. |
| Indexes | Existing indexes involving heartbeat/start time and their full definitions | Determine whether the update or later monitoring queries have index implications. |
| Table size | Approximate rows, data/index bytes and engine | Inform lock and runtime estimate. |

### 9.3 Behavior, retry and stop conditions

- Rows with non-NULL `heartbeat_at` are not changed by the statement.
- Rows with NULL `heartbeat_at` are assigned their `started_at` value.
- If `started_at` is NULL, the resulting heartbeat remains NULL; the source migration does not assert that this condition is impossible at runtime.
- Reapplying after successful completion is logically idempotent for rows whose heartbeat is populated, but concurrent writers and partial DDL must be considered.
- The single bulk UPDATE can acquire row locks and generate undo/redo and replication work proportional to affected rows. It can contend with monitoring workers that update heartbeat state.
- Stop if a row count cannot be established, if NULL started_at rows exist without an approved disposition, if a run is actively mutating in a way that makes the snapshot unstable, or if the table's actual definition differs from source.
- Do not batch or rewrite the migration during this investigation. Any execution redesign requires a separate code review and acceptance test.

## 10. Workstream F2 — Engine-specific DDL and locking assessment

### 10.1 Evidence boundary

The repository has prior disposable MySQL 8.4 and MariaDB 11.8.9 acceptance coverage. No production row counts, table sizes, replication topology, storage latency, lock timing or exact online-DDL behavior for the live Hostinger environment are established here. The following are risk assessments, not measured duration estimates.

### 10.2 Operation classes

| Operation class | Migrations | MariaDB 11.8.9 / MySQL 8.4 assessment | Stop and recovery considerations |
|---|---|---|---|
| Create table with PK/indexes | #1, #4, #5, #6, #11, #12, #13, #15, #16 | InnoDB creates table/index metadata; FK creation validates referenced definitions and may inspect existing parent metadata. New tables should be absent or fully equivalent. | Multi-table migrations may partially create one table before failure. Inspect every table/index/FK before retry. |
| Add column with default | #2, #3 | May be metadata-only or require row/table work depending on engine version, column form, table shape and algorithm. Do not assume instant behavior. | Validate existing column definition and values. Capture metadata lock and statement-level result. |
| Add nullable unique column/index | #7, #8, #9, #10, #14, #15 | Adding nullable columns is often less disruptive than populated non-NULL values, but unique index creation scans/indexes existing rows and can require sort/temp space. Multiple NULLs are allowed; duplicate non-NULL values are not. | Preflight duplicates and index definition. Each table alteration is a separate stop point; inspect partial application before retry. |
| Add FK / replace FK | #4, #6, #12, #16, #17, #18 | FK creation validates child references and parent key compatibility; metadata locks and scans can block writes. Integer width/signedness and index support must match. | Verify exact child/parent columns, all orphan preconditions, existing FK names/actions and each DDL statement outcome. |
| Create triggers | #17 | Requires trigger privilege and metadata access; trigger creation takes metadata locks. Trigger body and event/timing/table contracts must match exactly. | Verify each trigger body, not only its name. A partially installed trigger set is a hard stop until reconciled. |
| Identity mapping inserts | #11, #12 | Chunked inserts of up to 500 rows reduce per-statement size but still write production rows, take locks and generate redo/replication. | Not authorized here. Before any later execution, reconcile exact source/mapping cardinality and conflicts. |
| Heartbeat UPDATE | #18 | One bulk update of all NULL heartbeat values can lock many rows and create significant redo/undo/replication load. | Require affected-row profile and active-run coordination. No execution estimate is possible without data and engine telemetry. |
| FK action changes | #17 | Replacing CASCADE with RESTRICT changes delete behavior and may require drop/add constraint DDL. | Preserve audit history; do not use down migration as rollback because it restores CASCADE and removes triggers. |

### 10.3 Implicit commits and partial failure

MySQL-family DDL commonly performs implicit commits. A Laravel migration transaction does not make a sequence of CREATE/ALTER/trigger statements atomically reversible. The migration ledger row is normally written after the migration's `up()` succeeds; therefore a migration can leave some DDL objects present while its ledger entry remains absent.

Required future execution discipline:
1. Record a pre-migration full schema/ledger snapshot.
2. Treat every DDL statement as an independent stop point.
3. After any failure, stop the sequence and collect a new read-only schema/ledger snapshot.
4. Compare every expected and observed object against the migration source.
5. Do not blindly rerun, use `down()`, drop partial objects or insert ledger rows.
6. Resume only with a separately reviewed partial-state reconciliation decision.

### 10.4 Per-migration risk summary

No reliable per-migration duration or lock estimate can be derived without live table sizes, data distributions, engine settings and workload telemetry. Treat all such figures as UNKNOWN until measured in a representative isolated rehearsal and compared with production metadata. The #17 trigger set and #18 FK/backfill are critical-impact operations; #11/#12 are data-writing identity migrations.

## 11. Workstream G — Dependency graph and proposed execution waves

### 11.1 Dependency graph

```text
Existing application tables / exact column definitions
  ├─ #1 password_reset_tokens
  ├─ #2 users.must_change_password
  ├─ #3 import_batches.duplicate_rows
  ├─ #4 enrichment promotion events
  │    └─ #17 retention FK + append-only protection
  ├─ #5 governed execution leases
  ├─ #6 governed execution runs + run events
  │    ├─ #17 run-event retention FK + append-only protection
  │    └─ #18 monitoring execution_run FK
  ├─ #7 retrieval idempotency key
  │    └─ #18 monitoring retrieval_run FK
  ├─ #8 snapshot / observation idempotency
  │    └─ #18 monitoring observation FK
  ├─ #9 discovery outcome idempotency
  ├─ #10 change-event source-link identity
  ├─ #11 queue identity mappings
  │    └─ #17 reverification-event retention relationship depends on queue source table
  ├─ #12 alert identity mappings + audit idempotency
  │    ├─ #17 conflict-event retention protection depends on related history objects
  │    └─ #18 active-alert identity FK
  ├─ #13 monitoring policy runs + source checkpoints
  │    └─ #18 seven monitoring FK/heartbeat operations
  ├─ #14 discovery persistence identity keys
  ├─ #15 candidate/decision/conflict keys + conflict events
  │    └─ #17 conflict-event retention FK + trigger protection
  └─ #16 source snapshot reconciliation requests

#17 requires all 11 retention child/parent relationships and clean orphan gates.
#18 requires monitoring policies, governed runs, sources, retrieval runs,
    observations, monitoring alerts/identity table and #13 checkpoint tables.
```

This is a dependency view, not a claim that timestamp order is sufficient or that every listed migration must be executed in one deployment.

### 11.2 Proposed execution waves (planning only)

| Wave | Migrations | Preconditions and reason for grouping | Wave exit evidence |
|---|---|---|---|
| 0 — Source and environment gates | No migration | Resolve password reset registry mismatch; confirm deployed source revision; complete full schema/ledger baseline; verify supported engine and privileges; obtain data-profile evidence. | Reviewed source/registry correction; complete baseline; no unresolved schema ambiguity. |
| 1 — Independent additive foundations | #1, #2, #3, #5, #7, #8, #9, #10 | Each targets a separate table/column contract. #1 additionally requires password broker compatibility; #2/#3 require exact existing-column checks; #7–#10 require duplicate and partial-index checks. | Exact columns/tables/indexes verified; no duplicate preconditions; per-statement results recorded. |
| 2 — Dependent event and execution structures | #4, #6, #13, #16 | Create event/run/checkpoint/request structures only after parent tables and referenced key definitions are verified. #6 creates its run and event tables in sequence; #16 requires source/snapshot parents. | Full CREATE TABLE definitions, indexes and all FKs compared exactly; no partial tables. |
| 3 — Identity and persistence structures | #11, #12, #14, #15 | #11/#12 are data-writing backfills and require the protected record mapping protocol; #14/#15 add identity keys and event structures and require duplicate/partial-schema checks. Keep each migration separately stoppable. | One-to-one queue/alert mappings proven; all identity uniqueness preconditions PASS; source records/history preserved. |
| 4 — Retention enforcement | #17 | Only after all 11 child/parent tables/columns exist, all orphan checks PASS, FK definitions/types are compatible, and all 24 trigger contracts are verified against the source. | Eleven RESTRICT/RESTRICT FKs and 24 exact trigger bodies verified; append-only and reconciliation-detach tests pass. |
| 5 — Monitoring links and heartbeat | #18 | Only after #6, #7, #8, #12 and #13 objects plus existing policies/sources/alerts are structurally compatible; all seven monitoring orphan checks PASS; heartbeat impact and live-run coordination approved. | Seven FKs (4 RESTRICT delete, 3 SET NULL delete; all RESTRICT update) exact; heartbeat update impact and postcondition verified. |

### 11.3 Foreign-key and trigger totals to preserve

The 23 expected foreign keys comprise:
- #17: 11 RESTRICT on DELETE and RESTRICT on UPDATE;
- #16: 3 CASCADE on DELETE and 2 SET NULL on DELETE, all RESTRICT on UPDATE;
- #18: 4 RESTRICT on DELETE and 3 SET NULL on DELETE, all RESTRICT on UPDATE.

Totals: **15 RESTRICT, 3 CASCADE, 5 SET NULL on DELETE; all 23 RESTRICT on UPDATE.**

Migration #17 installs 22 audit-table triggers (11 tables × BEFORE UPDATE/DELETE) plus two identity-decision guards, for **24 total**. The one allowed reconciliation-event update is the constrained `import_row_id` detach; identity-decision update permits only one-time NULL-to-key enrichment. Preserve these contracts exactly.

### 11.4 Per-migration prerequisites and stop conditions

| # | Migration | Direct prerequisites / source effects | Data preconditions and hard stop | Retry / down caution |
|---:|---|---|---|---|
| 1 | `2026_10_17_000001_create_password_reset_tokens_table` | Configured password broker table; email/token/created_at; PRIMARY(email). | Confirm absent or exact full table definition; resolve registry mismatch first. Stop on any unexpected column/key or broker incompatibility. | Guard checks required columns/definitions; down drops the table and tokens. |
| 2 | `2026_10_17_000002_add_must_change_password_to_users_table` | Existing `users` table; boolean/TINYINT(1) NOT NULL DEFAULT 0. | Verify exact type, nullability, default and existing values. Stop on incompatible partial column. | Guarded add/verify; down drops column and values. |
| 3 | `2026_10_23_000001_add_duplicate_rows_to_import_batches_table` | Existing `import_batches`; unsigned INT NOT NULL DEFAULT 0. | Verify exact definition and existing values; stop on type/default drift. | Guarded column; down drops values. |
| 4 | `2026_10_25_000001_create_structured_enrichment_promotion_events_table` | Existing `enrichment_import_rows` and `import_batches`; creates event table, FKs and indexes. | Verify parent key types, table absence/partial state and zero orphan possibility. | Create-table guard; down drops event history; #17 later changes retention FKs. |
| 5 | `2026_11_03_000001_create_governed_execution_leases_table` | Creates leases with unique task key and expiry index. | Verify table absence or exact structure; task-key uniqueness and UUID field definitions. | Down drops active leases. |
| 6 | `2026_11_03_000002_create_governed_execution_runs_table` | Creates run and run-event tables with run-event FK. | Verify both tables absent or exact; run UUID type/length and event FK compatibility. | Multi-table partial DDL possible; down drops event and run records. |
| 7 | `2026_11_03_000003_add_source_retrieval_idempotency_key` | Existing `source_retrieval_runs`; nullable VARCHAR(64) + unique index. | Check non-NULL duplicate keys and exact existing column/index. Multiple NULLs are permitted. | Schema guard; down drops key/index. |
| 8 | `2026_11_03_000004_add_snapshot_and_observation_idempotency` | Existing `source_snapshots` and `source_change_observations`; capture key and unique current snapshot identity. | Explicit duplicate non-NULL current_snapshot_id check; verify both partial alterations and indexes. | Two table alterations can partially apply; down drops index/key values. |
| 9 | `2026_11_03_000005_add_discovery_outcome_idempotency_key` | Existing outcomes table; nullable unique idempotency key. | Check non-NULL duplicates and exact existing type/nullability/index. | Guarded add; down drops key/index. |
| 10 | `2026_11_03_000006_add_change_event_source_link_identity` | Existing `change_events`; nullable unsigned BIGINT unique source link. | Check non-NULL duplicates, signedness, nullability and index. | Guarded add; down drops key/index. |
| 11 | `2026_11_03_000007_create_data_reverification_queue_identities_table` | Existing queue; creates identity table and copies open-row mappings in chunks of 500. | Duplicate open tuple check; five ordinal records require exact one-to-one mapping and no conflicts. | Inserts are data writes; down drops identity table/mappings, not source rows. |
| 12 | `2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency` | Existing alerts and security audit events; identity table/backfill plus nullable audit key. | Duplicate open dedupe check; two ordinal alerts require exact one-to-one mapping and reverse uniqueness. | Inserts are data writes; down removes mapping table and audit idempotency column/index. |
| 13 | `2026_11_04_000010_create_monitoring_policy_run_checkpoints` | Creates monitoring run and source checkpoint tables with unique/supporting indexes. | Verify source policies, sources, retrieval runs, observations and key compatibility for later #18. | Multi-table partial DDL possible; down drops both tables and their records. |
| 14 | `2026_11_04_000011_add_discovery_candidate_persistence_identity_keys` | Existing import_batches, evidence, import_rows and assertions; adds nullable unique identity columns. | Check non-NULL duplicates and exact definition/index for all four columns before each alteration. | Four sequential alters can partially apply; down removes identity values. |
| 15 | `2026_11_04_000012_add_identity_conflict_persistence_keys_and_events` | Existing candidates, decisions, conflicts; adds nullable unique keys and creates conflict event table. | Check duplicate non-NULL keys and exact conflict-event idempotency/table/FK/index state. | Partial multi-table DDL possible; down drops conflict event history and key values. |
| 16 | `2026_11_04_000013_create_source_snapshot_reconciliation_requests_table` | Existing sources and snapshots; creates request table with five FKs and indexes. | Verify source/snapshot key types, all five mappings, unique identity and partial-table state. | Down drops all request rows. |
| 17 | `2026_11_04_000014_enforce_append_only_audit_retention` | Eleven existing history-child/parent relationships; replaces/adds 11 RESTRICT FKs and 24 triggers. | All 11 orphan preconditions zero; exact child/parent columns and existing FK contracts; all trigger names/bodies reconciled. | Critical. Down removes trigger guards and restores CASCADE semantics; never treat as routine rollback. |
| 18 | `2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat` | Existing policies, governed runs, monitoring run/checkpoint tables, sources, retrieval/observation records, alerts and alert identities; seven FKs plus heartbeat update. | All seven orphan checks zero; exact nullable FK semantics; heartbeat NULL/started_at and active-run profile reviewed. | Critical. Down drops seven FKs and heartbeat column/values. |

## 12. Workstream H — Backup and restore boundary

Backup/restore work is intentionally deferred and was not performed, requested or used as a blocker for this documentation investigation.

| Gate | Status |
|---|---|
| Backup existence and retention inventory | NOT_CHECKED |
| Backup integrity verification | NOT_CHECKED |
| Successful restore rehearsal | NOT_CHECKED |
| Restore duration / recovery-point validation | NOT_CHECKED |
| Production migration authorization | NOT AUTHORIZED |

Before any future production migration execution, a separate workstream must verify a suitable backup, test a successful restore in a controlled environment, record recovery-point/recovery-time evidence, and obtain explicit approval. A backup file's existence alone is not sufficient.

## 13. Outstanding unknowns and evidence gaps

1. Eleven orphan checks remain NOT_CHECKED in the historical production artifact.
2. Eleven duplicate checks remain NOT_CHECKED in the historical production artifact.
3. Five queue identity mappings and two alert mappings remain individually NOT_CHECKED.
4. The production schema has not been compared field-by-field against every source-defined table and object.
5. The full migration ledger has not been enumerated; only the 18 target identifiers were checked.
6. The password-reset diagnostic index registry still conflicts with migration source until a separate code correction is reviewed and merged.
7. Production row counts, NULL distributions, index selectivity, table sizes and replication/locking telemetry are not established.
8. Heartbeat affected-row and NULL-started_at counts are unknown.
9. Per-statement DDL algorithm, lock duration and online behavior on the production host are unknown.
10. The artifact's historical result cannot establish the state of production after its collection time.
11. Backup/restore readiness is NOT_CHECKED and deferred.
12. Existing MySQL 8.4/MariaDB 11.8.9 acceptance does not replace production schema/data evidence or a representative rehearsal of future changes.

No unknown is converted to PASS by this report.

## 14. Phase 1D.2 completion checklist

- [x] Verify latest `main` SHA and merged PRs #1067/#1068.
- [x] Reuse existing production evidence run/artifact metadata and checksum record.
- [x] Reuse existing Phase 1C isolated-engine acceptance records without rerunning Test 1A/1B.
- [x] Decide source-defined password reset primary-key contract and isolate required code follow-up.
- [x] Document all 18 orphan checks and preserve historical states.
- [x] Document all 14 duplicate checks and NULL/grouping semantics.
- [x] Define protected five-record queue and two-alert mapping protocols.
- [x] Define full schema-equivalence and full-ledger comparison specifications.
- [x] Define heartbeat impact evidence and DDL/locking risk requirements.
- [x] Define dependency graph, proposed waves, per-migration preconditions and stop conditions.
- [x] Keep backup/restore explicitly NOT_CHECKED and deferred.
- [x] Keep production untouched; no migrations, SQL, DDL, DML, backfills or workflow dispatch.
- [ ] Validate all report links and matrix counts on the branch.
- [ ] Create the Phase 1D.2 PR and inspect actual CI checks.
- [ ] Obtain review and merge approval separately; do not auto-merge.

## 15. Exact next phase recommendation

**Next: Phase 1D.2A — Diagnostic source-contract correction and isolated regression plan.**

Scope:
1. Prepare a separate code PR correcting `password_reset_tokens.PRIMARY(id)` to `PRIMARY(email)` in the diagnostic registry.
2. Add explicit primary-key definition assertions to diagnostic acceptance tests on MySQL 8.4 and MariaDB 11.8.9.
3. Add/confirm fixtures for nullable orphan relationships and NULL-safe duplicate grouping.
4. Extend the ledger design to full-ledger enumeration and full schema-equivalence output, but keep the implementation separately reviewed.
5. Do not run production SQL, dispatch production workflows, backfill identities, or execute migrations.

Only after source-contract correction and isolated regression acceptance should a separate Phase 1D.2B evidence plan be considered. Backup/restore readiness remains its own future gate.

---

## Appendix A — authoritative source files reviewed

### Phase 1D.1 reports and diagnostic/evidence contracts
- [Phase 1D.1 inventory](PHASE_1D_1_PRODUCTION_MIGRATION_RECONCILIATION_INVENTORY.md)
- [Phase 1D.1 final closeout](PHASE_1D_1_FINAL_CLOSEOUT_2026-11-11.md)
- [Read-only schema diagnostic](../../scripts/phase1c/readonly-schema-diagnostic.php)
- [Read-only evidence JSON schema](../../scripts/phase1c/readonly-diagnostic.schema.json)
- [All-18 migration preflight registry](../../scripts/diagnostics/phase1c-test1b-consolidated-all18-preflight.php)
- [Migration ledger reconciliation script](../../scripts/diagnostics/phase1c-migration-ledger-reconciliation.php)
- [Migration schema guard](../../app/Support/Database/MigrationSchemaGuard.php)
- [Test B object acceptance helper](../../tests/Support/verify-phase1c-testb-migration-objects.php)
- [Phase 1C MySQL recovery workflow](../../.github/workflows/phase-1c-testb-migration-recovery-mysql.yml)
- [Phase 1C diagnostic CI workflow](../../.github/workflows/phase1c-readonly-diagnostic-ci.yml)

### Migration sources
- [#1 password reset tokens](../../database/migrations/2026_10_17_000001_create_password_reset_tokens_table.php)
- [#2 must-change-password flag](../../database/migrations/2026_10_17_000002_add_must_change_password_to_users_table.php)
- [#3 duplicate row count](../../database/migrations/2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php)
- [#4 structured enrichment promotion events](../../database/migrations/2026_10_25_000001_create_structured_enrichment_promotion_events_table.php)
- [#5 governed execution leases](../../database/migrations/2026_11_03_000001_create_governed_execution_leases_table.php)
- [#6 governed execution runs](../../database/migrations/2026_11_03_000002_create_governed_execution_runs_table.php)
- [#7 source retrieval idempotency](../../database/migrations/2026_11_03_000003_add_source_retrieval_idempotency_key.php)
- [#8 snapshot/observation idempotency](../../database/migrations/2026_11_03_000004_add_snapshot_and_observation_idempotency.php)
- [#9 discovery outcome idempotency](../../database/migrations/2026_11_03_000005_add_discovery_outcome_idempotency_key.php)
- [#10 change-event source link identity](../../database/migrations/2026_11_03_000006_add_change_event_source_link_identity.php)
- [#11 reverification queue identities](../../database/migrations/2026_11_03_000007_create_data_reverification_queue_identities_table.php)
- [#12 monitoring alert identities](../../database/migrations/2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency.php)
- [#13 monitoring policy run checkpoints](../../database/migrations/2026_11_04_000010_create_monitoring_policy_run_checkpoints.php)
- [#14 discovery persistence identity keys](../../database/migrations/2026_11_04_000011_add_discovery_candidate_persistence_identity_keys.php)
- [#15 identity/conflict persistence keys and events](../../database/migrations/2026_11_04_000012_add_identity_conflict_persistence_keys_and_events.php)
- [#16 source snapshot reconciliation requests](../../database/migrations/2026_11_04_000013_create_source_snapshot_reconciliation_requests_table.php)
- [#17 append-only audit retention](../../database/migrations/2026_11_04_000014_enforce_append_only_audit_retention.php)
- [#18 monitoring FKs and heartbeat](../../database/migrations/2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat.php)

### Application configuration and historical evidence
- [Laravel authentication configuration](../../config/auth.php)
- [Composer dependency declaration](../../composer.json)
- [Phase 1C source review](PHASE_1C_TEST_1B_ALL_18_MIGRATION_SOURCE_REVIEW_2026-11-06.md)
- [Phase 1C sessions 1–4 reconciliation](PHASE_1C_SESSIONS_1_TO_4_SCHEMA_RECONCILIATION_2026-11-06.md)
