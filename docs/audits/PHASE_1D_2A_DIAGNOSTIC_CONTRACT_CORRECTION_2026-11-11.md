# Phase 1D.2A — Diagnostic Source-Contract Correction and Isolated Regression

**Repository:** `mattari20/estudent-listing`  
**Baseline:** `main` at `4e8da700285ee2c09dddd742415d64dcb51ba3c0`  
**Merged Phase 1D.2 investigation:** [PR #1069](https://github.com/mattari20/estudent-listing/pull/1069)  
**Authoritative investigation:** [Phase 1D.2 report](PHASE_1D_2_PRODUCTION_RECOVERY_READINESS_INVESTIGATION_2026-11-11.md)  
**Branch:** `phase1d2a/diagnostic-contract-correction-2026-11-11`  
**Disposition:** SOURCE CORRECTION + DISPOSABLE DATABASE REGRESSION ONLY

## 1. Scope and safety

This change corrects one read-only diagnostic registry entry and extends the existing disposable-database acceptance script. It does not change migration source, application configuration, production workflows, or production data.

- No production database connection or production workflow dispatch.
- No production SQL, DDL, DML, migrations, repairs, or identity backfills.
- No deployment, backup, or restore.
- Test 1A and Test 1B migration-chain acceptance were not restarted.
- The focused Phase 1C diagnostic acceptance matrix is used because it already runs against disposable MySQL 8.4 and MariaDB 11.8.9 services.

## 2. Source-contract decision

Migration #1 defines:

- `password_reset_tokens.email` as `VARCHAR(255) NOT NULL PRIMARY KEY`;
- `token` as `VARCHAR(255) NOT NULL`;
- nullable `created_at`;
- no `id` column.

The Laravel `users` password broker is configured to use `password_reset_tokens`. Accordingly, the diagnostic registry now expects `PRIMARY(email)`. Migration #1 and `config/auth.php` remain unchanged.

The evidence JSON schema does not hardcode primary-key column names. Its existing index contract represents ordered index columns as an array, so no schema change is needed.

## 3. Exact files changed

| File | Change |
|---|---|
| `scripts/phase1c/readonly-schema-diagnostic.php` | Correct `password_reset_tokens.PRIMARY` expected columns from `['id']` to `['email']`. |
| `tests/Support/phase1c-readonly-diagnostic-acceptance.php` | Build the disposable password-reset fixture with email as primary key; add positive, wrong-key and composite-key checks; add nullable orphan, non-NULL orphan, NULL duplicate, non-NULL duplicate, open-only identity, closed-record exclusion and missing-prerequisite regressions. |
| `docs/audits/PHASE_1D_2A_DIAGNOSTIC_CONTRACT_CORRECTION_2026-11-11.md` | Record the correction, test coverage, engine results and safety boundary. |

Unchanged by design:
- `database/migrations/2026_10_17_000001_create_password_reset_tokens_table.php`
- `config/auth.php`
- `scripts/phase1c/readonly-diagnostic.schema.json`
- `.github/workflows/phase1c-readonly-diagnostic-ci.yml`

The existing diagnostic CI workflow already has a disposable engine matrix for `mysql:8.4` and `mariadb:11.8.9`, and its pull-request path filters include the diagnostic source and acceptance script. No workflow modification was required.

## 4. Regression coverage added

### Password reset primary key
- Confirms expected index columns are exactly `['email']`.
- Confirms the correct email primary key reconciles as PASS.
- Confirms the disposable source-shaped table has no `id` column.
- Changes the fixture to a wrong `PRIMARY(token)` and requires FAIL.
- Changes the fixture to composite `PRIMARY(email, created_at)` and requires FAIL, proving ordered composite columns are compared rather than silently accepted.

The diagnostic comparator already compares the full ordered list of index columns; composite keys are supported as observed metadata and rejected when they do not equal the expected one-column contract.

### NULL-safe orphan checks
For each of the four nullable optional relationships in the #18 matrix:
- `monitoring_policy_runs.execution_run_id`;
- `monitoring_policy_run_sources.retrieval_run_id`;
- `monitoring_policy_run_sources.observation_id`;
- `monitoring_alert_identities.active_alert_id`;

the test inserts a valid NULL reference and expects PASS, then supplies a non-NULL reference with no matching parent and expects FAIL.

A missing `source_retrieval_runs` parent table is also required to produce NOT_CHECKED for its orphan relationship.

### NULL-safe duplicate checks
- Inserts multiple NULLs into nullable unique `source_retrieval_runs.idempotency_key` and `source_snapshots.capture_key` columns and requires PASS.
- Temporarily removes the disposable unique index, inserts duplicate non-NULL idempotency values, and requires FAIL before cleaning up and restoring the index.
- Proves duplicate open reverification identity groups fail, while a closed row with the same tuple is excluded.
- Proves duplicate open alert dedupe keys fail, while duplicate closed alerts are excluded.
- Retains the existing missing-column NOT_CHECKED test.

These are disposable fixture mutations only. They do not change the 18/14 production evidence definitions or alter historical evidence.

## 5. Database engine execution

The existing workflow runs the same focused diagnostic acceptance against two independent disposable database services:

| Engine | Container image | Workflow run | Result |
|---|---|---|---|
| MySQL | `mysql:8.4` | Pending PR CI | NOT YET EXECUTED / PENDING |
| MariaDB | `mariadb:11.8.9` | Pending PR CI | NOT YET EXECUTED / PENDING |

The workflow records `SELECT VERSION()` in the acceptance output. Final engine version strings, run IDs, pass/fail results and any failed or unexecuted jobs will be added after GitHub Actions completes. No result is claimed in advance.

## 6. Validation and completion state

- [x] Latest merged baseline verified as `4e8da700285ee2c09dddd742415d64dcb51ba3c0`.
- [x] PR #1069 verified merged at that exact commit.
- [x] Migration #1 and Laravel password broker configuration inspected.
- [x] Diagnostic registry corrected to `PRIMARY(email)`.
- [x] Primary-key positive, wrong-key and composite-key tests added.
- [x] Four nullable orphan relationships tested for NULL and non-NULL missing-parent cases.
- [x] Nullable unique NULL handling, non-NULL duplicate detection and open-only identity grouping tests added.
- [x] Missing prerequisite NOT_CHECKED behavior tested.
- [x] Existing MySQL 8.4 / MariaDB 11.8.9 disposable CI matrix reused.
- [ ] MySQL 8.4 focused regression passes.
- [ ] MariaDB 11.8.9 focused regression passes.
- [ ] Required PR checks complete and successful.
- [ ] Review and merge approval obtained separately.

## 7. Outstanding issues and next step

Until both engine jobs and all required PR checks finish, Phase 1D.2A is **implemented but not accepted**. Any engine-specific failure must be investigated against the focused test and the diagnostic's established evidence semantics; do not weaken audit, FK, identity, or NOT_CHECKED behavior to make a test pass.

After successful CI and review, the next appropriate work is a separate **Phase 1D.2B planning/review** for full schema and migration-ledger evidence design. It must not start production evidence collection or production migrations without separate authorization and all required gates.

**Final boundary:** this PR is limited to source-contract correction and disposable regression. Production remains untouched.
