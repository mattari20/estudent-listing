# Phase 1C — Legacy NULL idempotency-key inventory

Date: 2026-11-04  
Repository: mattari20/estudent-listing  
Base: e9feccedbb20078c3af24656e6399d55383b6722

## Inventory scope

The repository now has seven nullable persistence-identity columns relevant to discovery and identity replay:

- identity_match_candidates.identity_key
- identity_resolution_decisions.decision_identity_key
- import_conflicts.identity_key
- import_batches.discovery_identity_key
- evidence.discovery_identity_key
- import_rows.discovery_identity_key
- assertions.discovery_identity_key

NULL values are classified as legacy/unkeyed. This is a report category only; a NULL value does not prove that a historical row is duplicate, safe to merge, or safe to assign a deterministic key.

## Reproducible read-only implementation

- Added LegacyIdentityKeyInventoryService, which checks schema availability, reports total/keyed/NULL counts and returns a bounded ordered sample of NULL-key row IDs per table.
- Added institutes:audit-legacy-identity-keys. It requires APP_ENV=testing and independently inspects the resolved Laravel database connection before calling the inventory service. It accepts only SQLite :memory: or the repository's database/testing.sqlite file, or the exact named local GitHub Actions MySQL service tuple (connection mysql, host localhost/127.0.0.1/::1, port 3306, database approval_migration_test, username approval_ci, GITHUB_ACTIONS=true). It rejects remote hosts, non-test database names, arbitrary MySQL connections, database URL overrides, read/write split configurations and unknown drivers/connections. It also bounds the sample limit to 1–1000. No PDO connection or query is opened during the guard.
- Added LegacyIdentityKeyInventoryTest with isolated test-database fixtures across all seven tables. It verifies one NULL-key row per table, seven total NULL-key rows and unchanged NULL values after inventory. Command-level cases prove that remote production MySQL, a non-test database on localhost, database URL overrides, and unknown connection names are refused before the inventory service is called; a positive command test uses the explicitly configured isolated test database.
- Added the focused inventory test to the isolated MySQL workflow.

## Findings and disposition

The repository fixture inventory is now reproducible, and the implementation deliberately does not classify any production historical rows. No production inventory was run because production database access is prohibited by this mission. The actual count and classification of legacy production NULL-key records therefore remains an external acceptance blocker requiring separate read-only production authorization and an approved private report destination.

No automatic backfill, merge, key assignment, historical decision rewrite or candidate-state change is authorized by this package.

## Status and safety

## Phase 1C-28 exact-head validation

Current `main` at verification: `92e748f7aeedb25d098775be6ff16abbbda23749` (the PR head is not behind current main).

Final PR head: `f99eb4a350f984c7293e9fee729043c55adb6bde`.

- Test run [41833654494](https://github.com/mattari20/estudent-listing/actions/runs/41833654494): passed — 893 tests, 3,949 assertions.
- Laravel CI run [41833654453](https://github.com/mattari20/estudent-listing/actions/runs/41833654453): passed, including Pint formatting verification for the command and both inventory test files.
- Project Controller run [41833654439](https://github.com/mattari20/estudent-listing/actions/runs/41833654439): passed.
- Isolated MySQL run [41833654465](https://github.com/mattari20/estudent-listing/actions/runs/41833654465): passed. The existing inventory command succeeded against the isolated `approval_migration_test` database, and command-level refusal fixtures passed against production/ambiguous configurations.

IMPLEMENTED and TESTED on the exact PR head. MERGED and PRODUCTION VERIFIED are not claimed.

No production query or mutation was performed. GCU rows #348–353 and batches #19–23 remain protected. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data.
