# Phase 1D.2B — Full Schema and Migration-Ledger Reconciliation

**Repository:** `mattari20/estudent-listing`  
**Verified base:** `main` at `00cfce7d73ed8647b07d3f185aa16be081913d08`  
**Branch:** `phase1d2b/full-schema-ledger-reconciliation`  
**Draft PR:** [#1071 — full migration-ledger reconciliation foundation](https://github.com/mattari20/estudent-listing/pull/1071)  
**Disposition:** FULL-LEDGER IMPLEMENTATION FOUNDATION / FULL-SCHEMA CONTRACT STILL OPEN  
**Production:** READ-ONLY; NO PRODUCTION EXECUTION

## 1. Baseline verification

The following PRs are merged: #1067, #1068, #1069 and #1070. PR #1070 merged as commit `00cfce7d73ed8647b07d3f185aa16be081913d08`.

Final Phase 1D.2A validation reused:
- Focused diagnostic matrix #42977214545 — MySQL 8.4 and MariaDB 11.8.9 PASS.
- Laravel CI #42977413880 — PASS.
- Repository Test #42977413888 — PASS.

The Phase 1D.2A report's earlier pre-merge checklist is historical text and is superseded by the merged PR state and final workflow results. Test 1A / Test 1B were not restarted.

## 2. Existing diagnostic capabilities

The current `scripts/phase1c/readonly-schema-diagnostic.php` already:
- Inventories the 39 registered table names and captures ENGINE, ROW_FORMAT, TABLE_COLLATION and CREATE_OPTIONS.
- Captures sanitized `SHOW CREATE TABLE` output for visible registered tables.
- Captures ordered column metadata including ordinal, type, nullability, defaults, precision, charset/collation, extra attributes and generated expression.
- Captures index metadata including sequence, uniqueness, column, prefix length, collation and index type.
- Reconciles the targeted 50 columns, 23 named indexes, 23 foreign keys and 24 trigger contracts.
- Preserves metadata-visibility semantics and returns UNKNOWN where required metadata cannot be established.
- Reconciles the 18 target migration identifiers, including duplicate rows and batch values for those identifiers.

These are useful existing capabilities and should be extended rather than replaced.

## 3. Confirmed Phase 1D.2B gaps

### A. Full schema definition equivalence

The current collector's `tables` state PASS means that the table's metadata and CREATE TABLE statement were successfully read. It does not mean that the observed table matches the complete source definition.

The targeted 50-column and 23-index registries are not exhaustive table-definition manifests. The 23 indexes are a selected named-index list, not the total set of indexes across the 39 tables or all migration-created tables.

A complete source-backed contract is still required for every in-scope table and must cover:
- Table engine, row format and relevant table options.
- Character set and collation.
- Every column and ordinal position.
- Type, unsignedness, length, precision and scale.
- Nullability, default and default expression.
- Auto-increment and generated-column expression.
- Primary and unique keys, composite order, index type and prefix lengths.
- All foreign-key source/target column mappings and both referential actions.
- Trigger timing, event, row orientation and exact behavior contract.

Do not classify a table definition PASS from a successful metadata read alone. If the expected source definition is unavailable or metadata visibility is insufficient, use NOT_CHECKED / UNKNOWN.

### B. Full migration ledger

The existing `scripts/diagnostics/phase1c-full-migration-ledger-inventory.php` already enumerates all rows in the migrations ledger, counts duplicate migration names, compares ledger entries to the deployed migration source filename set, and reports missing/extra source entries. The current read-only JSON collector, however, only returns the 18 target migration rows.

The full-ledger contract still needs:
- All source migration filenames and all ledger entries.
- Unexpected ledger entries and ledger rows without source files.
- Missing source files for recorded migrations.
- Duplicate migration rows and invalid batch values.
- Batch gaps and ordering anomalies, with ordering classified UNKNOWN if the available ledger schema cannot prove execution order.
- The 18 target migration states linked to their expected object states.
- Partial-application classification: ledger row present while expected objects are missing; objects present while ledger row is absent.
- Explicit separation between migration-ledger evidence and object-presence evidence.

Do not insert or modify ledger rows. Do not treat a missing ledger row as proof that objects are absent.

### C. Source contract provenance

The next implementation must record which migration source(s) define each expected object. Source-level mapping must be verified against actual migration files, including earlier baseline migrations and later alterations. Do not infer full definitions from object names, timestamps or historical production observations.

## 5. Implemented full-ledger inventory foundation

The Phase 1D.2B branch now adds a version-two evidence contract and a full migration-ledger inventory to the existing read-only collector.

Changed implementation paths:
- `scripts/phase1c/readonly-schema-diagnostic.php`
- `scripts/phase1c/readonly-diagnostic.schema.json`
- `scripts/phase1c/validate-readonly-evidence.py`
- `tests/Support/phase1c-readonly-diagnostic-acceptance.php`
- `tests/phase1c/test_readonly_evidence_validator.py`
- `tests/phase1c/test_evidence_transfer.py`

The pure ledger analyzer and collector inventory now report:
- Every deployed migration source filename and every row from the `migrations` ledger.
- Source files not recorded in the ledger.
- Ledger names without a matching source file.
- Duplicate ledger rows.
- Invalid/non-positive batch values.
- Distinct batch numbers and gaps (gaps are informational / NOT_CHECKED, not automatically treated as corruption because rollback history can create gaps).
- Within-batch filename ordering anomalies when a reliable `id` sequence column is available. Ordering is UNKNOWN when the sequence cannot be established.
- A fail-closed UNKNOWN result if the migration source directory, ledger columns or full inventory query is unavailable.

Focused acceptance fixtures now cover complete, missing, unexpected, duplicate, invalid-batch, ordering-anomaly, missing-sequence and batch-gap cases. The existing targeted 18-row reconciliation remains intact and separate from the full-ledger inventory.

The evidence schema is versioned as `phase1c-readonly-diagnostic/v2`. A target migration/object consistency matrix is also included for all 18 target migrations. It distinguishes a valid ledger/object pair, a ledger row with missing target objects, target objects present without a ledger row, partial target-object presence, and UNKNOWN evidence. The Python privacy validator rejects false PASS combinations. This matrix covers the currently registered target objects; it is not a substitute for the full 39-table source manifest. No production database was queried by this implementation; all database acceptance remains in disposable CI services.

## 6.1 Source-provenance inventory foundation

The migration directory on this branch contains **97 PHP migration files**. The diagnostic's `TABLES` allowlist contains **39 entries**, of which 38 are application tables and one is Laravel's `migrations` ledger table. These counts must not be conflated: the number 39 is the current diagnostic allowlist size, not the number of migration files.

A first provenance artifact has been committed:
- `database/schema-contract/phase1d2b-table-source-provenance.json`
- `tests/phase1c/test_phase1d2b_source_provenance.py`
- Contract: `phase1d2b-source-provenance/v1`
- Commit: `227c8c1c686dd992a3326ad67cf40a031025f6c6`

It maps each of the 38 application tables to the reviewed create-table migration(s) and later migration(s) that alter the table, add indexes/constraints/columns, or install append-only guards. The `migrations` ledger is explicitly marked as Laravel-migrator-managed rather than being attributed to an application migration.

A dedicated Python regression test now checks that the provenance table set matches the diagnostic allowlist, all mapped source paths exist, create-source files contain a table-creation operation, and each alteration source references its mapped table.

This artifact is **provenance only**. It deliberately does not claim to be the full schema definition manifest: column definitions, complete index contracts, foreign-key actions, trigger bodies and table options must still be encoded and compared. Source migrations that create tables outside the diagnostic allowlist remain outside this 1D.2B table contract unless the authoritative scope is expanded; they must not be silently omitted from a broader whole-database claim.

## 6.2 Expanded migration-created schema scope inventory

Continuation branch `phase1d2b/complete-source-schema-reconciliation` was created from verified `main` SHA `117d2dd710e7b9545b7a28b6c802415e26c02f1f`.

A source-level inventory of all 97 PHP files in `database/migrations` identified 62 distinct application-owned table creation targets, including multiple tables created by one migration and guarded `MigrationSchemaGuard::createTableIfMissing` operations. The Laravel `migrations` ledger is separately classified as framework-managed, giving 63 current table-scope entries in this inventory.

New artifact:
- `database/schema-contract/phase1d2b-schema-scope-inventory.json`
- Contract: `phase1d2b-schema-scope-inventory/v1`
- Application creation targets: 62
- Framework-managed ledger: 1
- Total entries: 63
- Status: `SOURCE_CREATE_INVENTORY_IDENTIFIED; FINAL_SCHEMA_DEFINITION_NOT_CHECKED`

The current 39-entry diagnostic allowlist is a subset of this expanded source inventory. This establishes that the previous 38-application-table inventory is not the complete migration-created application table scope.

Regression:
- `tests/phase1c/test_phase1d2b_schema_scope.py`
- Checks scope counts, framework-ledger classification, allowlist inclusion, creation-source existence, recognized create operations and explicit NOT_CHECKED definition state.
- Added to `phase1c-readonly-diagnostic-ci.yml`.

Important limitation: this inventory is a creation-target/scope discovery artifact, not the final-state schema manifest. It does not yet encode complete final column definitions, indexes, foreign keys, triggers, table options or every subsequent alteration. Those definitions must be reconstructed from all applicable source migrations before any schema-equivalence PASS can be emitted.

## 6.3 Alteration provenance and source-scan regression

The expanded scope inventory now includes an `alteration_migrations` list for 28 application tables based on inspected `Schema::table`, `MigrationSchemaGuard` and append-only / monitoring checkpoint migration sources. The original 38-table provenance artifact now links to the expanded 63-entry scope artifact and explicitly warns that its legacy `tables` object is not the complete application scope.

The scope regression was strengthened to scan all 97 migration PHP files for literal `Schema::create`, `createTableIfMissing`, raw `CREATE TABLE`, and constant-backed `Schema::create(self::TABLE, ...)` targets, then compare the discovered set to the inventory. It also verifies every mapped alteration source path and its table reference.

The first run of this stronger scan failed because the test recognizer expected the constant-backed `Schema::create(self::TABLE)` call to close immediately; the real migration correctly passes a Blueprint closure after the constant. The failure was in the test recognizer, not the migration. The recognizer was corrected to accept the closure argument. Exact-head rerun is pending; no assertion has been removed or weakened.

## 6.4 Exact-head scope inventory regression result

At continuation commit `9360d713558c3455b19b20c161cd6d0a057c808e`, Phase 1C read-only diagnostic CI run **#42994466030** completed successfully.

Both disposable database jobs passed:
- MySQL 8.4 — PASS.
- MariaDB 11.8.9 — PASS.

Within each engine job:
- Existing source provenance regression — PASS.
- Expanded 63-entry schema scope regression — PASS, including the all-97-migration create-target scan and alteration-source path checks.
- Existing disposable read-only diagnostic acceptance — PASS.
- Sanitized evidence schema validation — PASS.

This validates the new source-scope inventory and its test harness only. It does not validate final-state schema equivalence; the complete source-derived definition manifest and comparison engine remain open. Laravel CI and Repository Test for this exact head were still running at the time of this report update and must be rechecked on the final PR head.

## 6. Remaining blockers before Phase 1D.2B acceptance

The source-backed full schema definition manifest and comparison engine are not yet complete. The existing table metadata PASS remains a successful metadata-read state, not proof of source equivalence. A complete definition manifest must cover the 62 application-owned table creation targets in the expanded scope inventory (plus the Laravel migrations ledger), using the provenance map and authoritative migration history. Observed definitions must be compared across all columns, table options, indexes, foreign keys and trigger contracts.

The current target migration/object matrix is implemented, but its object mapping must be expanded and source-verified as part of the complete schema manifest. It must not be treated as exhaustive for every table/column/index/constraint created by the 18 migrations.

Phase 1D.2B is therefore still IN PROGRESS. Do not begin Phase 1D.3.

## 7. First CI regression and correction

The first PR #1071 focused diagnostic matrix (run #42981392749) failed on both MySQL 8.4 and MariaDB 11.8.9 at the same pure acceptance assertion: the batch-gap fixture expected overall ledger state PASS while supplying only two of the three known source migration filenames. The analyzer correctly classified the absent third ledger record as MISSING; the test fixture was internally inconsistent.

The fixture was corrected in commit `9391d6b60990c85c0b9e4c567b68eb9b83a3ec19` by including the third source migration in batch 3. This retains the intended gap (batch 2) while making source-to-ledger coverage complete. The implementation was not weakened. A new exact-head CI run #42982039978 was triggered for MySQL 8.4 and MariaDB 11.8.9; its final outcome must be verified before acceptance.

## 8. Required implementation and test plan

The Phase 1D.2B implementation PR must:
1. Add a versioned source-derived schema contract for the complete in-scope inventory.
2. Compare observed table/column/index/FK/trigger definitions against that contract.
3. Preserve MySQL 8.4 and MariaDB 11.8.9 metadata differences through explicit normalization only where semantically equivalent.
4. Add full-ledger reconciliation without exposing application row payloads.
5. Add target migration/object consistency and partial-application states.
6. Preserve PASS, FAIL, MISSING, NOT_CHECKED and UNKNOWN semantics.
7. Keep all evidence deterministic and sanitized.
8. Reuse existing disposable engine CI; do not restart Test 1A / Test 1B.

Focused tests must cover correct definitions and deliberately incorrect type, nullability, default, index order/prefix/type, FK mapping/actions, trigger behavior, missing/duplicate/unexpected ledger entries, missing source files, invalid batches, ordering uncertainty, inconsistent ledger/object states, metadata visibility failure and deterministic evidence.

## 9. Production and protected-record boundary

No production connection, query, SQL, DDL, DML, migration, repair, identity backfill, deployment or workflow dispatch was performed in this stage. The five open reverification records and two open monitoring alerts remain protected. Production row-level compatibility is not inferred from this source review.

Phase 1D.7 backup/restore is excluded from the supplied execution scope and is not a prerequisite for this engineering stage.

## 10. Current disposition

**Phase 1D.2B is IN PROGRESS and NOT ACCEPTED.** The current branch provides full-ledger inventory and target-object/ledger consistency evidence, but it does not yet prove complete source-to-live schema equivalence for all 39 tables and every object. Full source-derived schema manifest/comparison and exact-head isolated CI/review remain outstanding.

Do not start Phase 1D.3 until the missing source contract, implementation, isolated regression, required review and merge are complete.
