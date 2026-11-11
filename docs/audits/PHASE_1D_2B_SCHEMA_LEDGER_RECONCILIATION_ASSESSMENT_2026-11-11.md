# Phase 1D.2B — Full Schema and Migration-Ledger Reconciliation

**Repository:** `mattari20/estudent-listing`  
**Verified base:** `main` at `00cfce7d73ed8647b07d3f185aa16be081913d08`  
**Branch:** `phase1d2b/full-schema-ledger-reconciliation`  
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

The evidence schema is versioned as `phase1c-readonly-diagnostic/v2`. No production database was queried by this implementation; all database acceptance remains in disposable CI services.

## 6. Remaining blockers before Phase 1D.2B acceptance

The source-backed full schema definition manifest and comparison engine are not yet complete. The existing table metadata PASS remains a successful metadata-read state, not proof of source equivalence. A complete 39-table manifest must be derived from the authoritative migration source history, and the observed definitions must be compared across all columns, table options, indexes, foreign keys and trigger contracts.

The 18 target migrations also still need an explicit migration-to-object state matrix so the collector can distinguish:
- Ledger entry plus all expected objects present.
- Ledger entry with one or more expected objects missing.
- Objects present while the ledger entry is absent.
- Partial object application.
- UNKNOWN / NOT_CHECKED when visibility or prerequisites are insufficient.

Phase 1D.2B is therefore still IN PROGRESS. Do not begin Phase 1D.3.

## 7. Required implementation and test plan

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

## 8. Production and protected-record boundary

No production connection, query, SQL, DDL, DML, migration, repair, identity backfill, deployment or workflow dispatch was performed in this stage. The five open reverification records and two open monitoring alerts remain protected. Production row-level compatibility is not inferred from this source review.

Phase 1D.7 backup/restore is excluded from the supplied execution scope and is not a prerequisite for this engineering stage.

## 9. Current disposition

**Phase 1D.2B is IN PROGRESS and NOT ACCEPTED.** The current collector provides substantial observed metadata, but it does not yet prove complete source-to-live schema equivalence, and the JSON evidence does not yet expose full-ledger reconciliation and object/ledger partial-application states.

Do not start Phase 1D.3 until the missing source contract, implementation, isolated regression, required review and merge are complete.
