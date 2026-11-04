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
- Added the focused inventory and command-safety tests to the isolated MySQL workflow. The command-safety suite verifies refusal for a production host, an unknown remote host paired with a test-looking database name, a missing host, a non-test database on localhost, URL override, an unknown connection, an unrecognized connection alias, and read/write split configuration. Each refusal uses a service mock that must not receive `report()`. The positive command execution is exercised on the repository's isolated SQLite test database in the full Test suite and on the isolated `approval_migration_test` MySQL service in the isolated MySQL suite.

## Findings and disposition

The repository fixture inventory is now reproducible, and the implementation deliberately does not classify any production historical rows. No production inventory was run because production database access is prohibited by this mission. The actual count and classification of legacy production NULL-key records therefore remains an external acceptance blocker requiring separate read-only production authorization and an approved private report destination.

No automatic backfill, merge, key assignment, historical decision rewrite or candidate-state change is authorized by this package.

## Phase 1C-28B exact-head validation

Current `main` at verification: `92e748f7aeedb25d098775be6ff16abbbda23749`. Current PR branch contains current main as an ancestor.

Validated implementation head before this audit-document reconciliation: `238a4c8a67f9132ac317f28fd9ca559f8105e61d`.

- Test run [41835701126](https://github.com/mattari20/estudent-listing/actions/runs/41835701126): passed — **897 tests, 3,957 assertions**.
- Laravel CI run [41835700970](https://github.com/mattari20/estudent-listing/actions/runs/41835700970): passed — same 897-test suite and Pint formatting verification for the command and both inventory test files.
- Project Controller run [41835701058](https://github.com/mattari20/estudent-listing/actions/runs/41835701058): passed.
- Isolated MySQL run [41835700998](https://github.com/mattari20/estudent-listing/actions/runs/41835700998): passed. The MySQL job ran the inventory service tests (2 tests / 25 assertions) and the command database-safety suite (8 tests / 16 assertions) against the isolated CI service. The command safety cases refused remote and unknown hosts, missing host, production/non-test database, URL override, unknown connection/alias and read/write split configuration before calling the inventory service. The command also completed successfully on the isolated MySQL test service.

The above run IDs all belong to implementation head `238a4c8a67f9132ac317f28fd9ca559f8105e61d`. The final PR head will additionally contain this documentation reconciliation; its exact-head checks are recorded in the PR description after this documentation commit.

## Status and safety

IMPLEMENTED and TESTED on the implementation head above. MERGED and PRODUCTION VERIFIED are not claimed.

No production query or mutation was performed. GCU rows #348–353 and batches #19–23 remain protected. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data.
