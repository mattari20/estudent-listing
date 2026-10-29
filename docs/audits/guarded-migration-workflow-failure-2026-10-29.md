# Guarded duplicate_rows migration workflow incident — 2026-10-29

## Status

**Open — root cause not yet confirmed; no production schema mutation authorized.**

Repository: `mattari20/estudent-listing`  
Workflow: `.github/workflows/controlled-import-batch-duplicate-rows-migration.yml`  
Latest reviewed run: [Controlled Import Batch Duplicate Rows Migration #6](https://github.com/mattari20/estudent-listing/actions/runs/40819601765)  
Run commit: `c0f7bb9aa15e9b994fa3a4787c29399e570567bf`  
Previous repair: [PR #892](https://github.com/mattari20/estudent-listing/pull/892), merged as `c0f7bb9aa15e9b994fa3a4787c29399e570567bf`.

## Confirmed evidence

1. The revision-resolution job passed and resolved target application revision `6cb68ce1ebc0b92629720221882f5dea229eef4e`.
2. Hostinger SSH connected and reported deployed revision `c0f7bb9aa15e9b994fa3a4787c29399e570567bf`.
3. `git fetch --no-tags --unshallow origin main` completed successfully.
4. `application_code_parity=VERIFIED` was printed.
5. The workflow printed `php_preflight_stage=starting`, then the remote action returned status 255. No PHP output file contents, PHP exit marker, or schema findings were emitted.
6. The run's input was `CONFIRM_MIGRATION=false`; the migration command was not reached. The run provides no evidence of a production schema write.
7. The independent Hostinger read-only diagnostics run [#40819347203](https://github.com/mattari20/estudent-listing/actions/runs/40819347203) passed and executed several `php artisan` commands over SSH. This demonstrates that the general SSH/PHP diagnostics path works, but does not prove this specific script invocation works.
8. The six main checks on the same commit passed, but they do not execute the guarded migration's live preflight and do not override this failure.

## Interpretation — do not overstate

- The evidence does **not** establish whether the `duplicate_rows` column exists or whether the migration is recorded.
- The failure is localized to the guarded workflow's remote execution at or immediately after the PHP preflight start marker.
- The SSH action reports status 255 without the shell's PHP exit marker. This is different from a normal, captured PHP failure and makes the previous diagnostic handling insufficient.
- Upstream `appleboy/ssh-action` documentation recommends shell-level `set -e` instead of the legacy `script_stop` option, and upstream issue history documents compound-command handling problems with `script_stop`. This makes the option a credible hypothesis, **not a confirmed root cause**. See [action documentation](https://github.com/appleboy/ssh-action#%EF%B8%8F-error-handling--troubleshooting) and [issue #335](https://github.com/appleboy/ssh-action/issues/335).
- No manual column addition, migration-ledger edit, or migration retry is authorized.

## Current repair proposal

A focused follow-up branch, `fix/guarded-migration-ssh-script-stop-20261029`, removes `script_stop: true` while retaining explicit shell `set -eu` handling, adds an isolated PHP CLI version/inline-execution probe, reuses the resolved PHP executable for the explicit preflight/postcheck script files, and strengthens the workflow contract test. This is designed to reveal whether the failure occurs in PHP CLI startup, PHP inline execution, or Laravel bootstrap/database inspection.

The live diagnosis is **not complete** until the updated workflow is run with `confirm_migration=false` and returns interpretable stage/exit output.

## Required closure gate

- [ ] Follow-up PR CI passes for Laravel CI and Test, plus all required checks.
- [ ] Review the final diff to confirm dry-run default, one-migration path guard, and no unrelated production writes remain intact.
- [ ] Merge the code fix only after CI and code review are clean.
- [ ] Dispatch the workflow from `main` with `confirm_migration=false`.
- [ ] Confirm PHP CLI probe markers and either successful preflight JSON or a specific captured PHP error/exit code.
- [ ] Verify `import_batches`, `duplicate_rows`, exact migration filename, and migration-ledger state from actual read-only evidence.
- [ ] If schema drift is reported, prepare a separate reconciliation plan. Do not blindly retry or add a column manually.
- [ ] Consider the migration only after a successful read-only preflight and separate explicit authorization.

## Project-wide master audit implications

This incident remains one item within the existing [deep system audit](deep-system-audit-2026-10-26.md) and the audit plan tracked in PR #822. The wider audit must continue across:

1. Repository/architecture inventory and test-to-requirement coverage.
2. CI, workflow triggers, branch rules, secrets/permissions boundaries, and deployment parity.
3. Database schema, migration ordering, foreign keys/indexes, and migration-ledger/schema reconciliation.
4. Import batch/row validation, duplicate resolution, source provenance, identity decisions, promotion/reversal lifecycle, and audit trails.
5. Geography hierarchy, campus/program/offerings relationships, and data-gap reporting accuracy.
6. Public routes, search/filter/pagination, detail pages, SEO/canonical metadata, accessibility, and public smoke tests.
7. Admin/operator authorization, upload validation, CSRF, role boundaries, and guest-denial checks.
8. Scheduled discovery/refresh workflows, idempotency, failure recovery, and review-before-promotion safeguards.
9. Read-only live diagnostics, production data quality, safe remediation manifests, and release readiness.

Track each item as **confirmed defect**, **audit gap**, **verified pass**, or **blocked by external access**. Do not treat a successful CI run, merged PR, or successful deployment as proof that the entire system is healthy.

**Closure rule:** close this incident only after the guarded workflow produces an interpretable read-only result and the relevant live schema/migration state is verified. A merged PR alone is not closure evidence.


## Execution update — 2026-10-29

- [PR #893](https://github.com/mattari20/estudent-listing/pull/893) was reviewed and squash-merged as `a6239509d1183ad54e3f062d572876e377e5743d`. Its Test run [#40819995295](https://github.com/mattari20/estudent-listing/actions/runs/40819995295) and Laravel CI run [#40819995286](https://github.com/mattari20/estudent-listing/actions/runs/40819995286) passed.
- The merged change removes the legacy SSH `script_stop: true` setting from this workflow and adds PHP CLI discovery/version/inline-execution markers. This is a diagnostic improvement, **not proof of a live fix**.
- The only observed production attempt still remains the old main workflow run [#40819601765](https://github.com/mattari20/estudent-listing/actions/runs/40819601765), which stopped with SSH status 255 immediately after `php_preflight_stage=starting`. No schema report was emitted; no migration execution or production schema mutation is evidenced.
- The connected GitHub integration has no workflow-dispatch operation. No fresh run was triggered by this session. Required next operational check: manually dispatch [Controlled Import Batch Duplicate Rows Migration](https://github.com/mattari20/estudent-listing/actions/workflows/controlled-import-batch-duplicate-rows-migration.yml) on `main` with `confirm_migration=false`, then inspect all stage markers and sanitized schema/ledger output.

### Additional confirmed fail-closed gap discovered in code review

- The existing preflight rejected “migration recorded, column absent” and accepted “migration recorded, column present,” but did not reject the opposite partial state: `duplicate_rows` already exists while the migration has no ledger row. In that state it could report `READY_FOR_SINGLE_MIGRATION`, causing an attempted duplicate-column migration if explicitly confirmed.
- A focused fail-closed fix and regression test are in [PR #894](https://github.com/mattari20/estudent-listing/pull/894), currently open and awaiting CI. The fix rejects that partial state before the ready decision. It does not inspect or mutate the production database and does not alter the migration confirmation gate.
- Fresh Hostinger read-only diagnostics [run #40820996249](https://github.com/mattari20/estudent-listing/actions/runs/40820996249), checked at 2026-10-29 10:01:46 +05, now establish `import_batches` exists, `duplicate_rows` exists, the migration file exists, and the migrations table exists, but the target migration row is absent (`COLUMN_PRESENT_MIGRATION_NOT_RECORDED`). The diagnostic does not report the column type, nullability, default, or all relevant schema constraints, so full equivalence remains unproven. Do not dispatch with confirmation enabled.


### Fresh live evidence — 2026-10-29 10:01 +05

- Hostinger read-only diagnostics [#40820996249](https://github.com/mattari20/estudent-listing/actions/runs/40820996249) completed successfully and reported `deployment_revision_status=APPLICATION_CODE_MATCH`; this verifies application-path parity, not the entire Git commit SHA.
- The duplicate_rows check explicitly returned `table_exists=true`, `duplicate_rows_column_exists=true`, `migration_file_exists=true`, `migration_ledger_exists=true`, `migration_applied=false`, `matching_migration_rows=[]`, and `diagnosis=COLUMN_PRESENT_MIGRATION_NOT_RECORDED`. `mutation_performed=false`.
- This is a confirmed partial schema/ledger state, but not yet proof that the existing column definition exactly matches the migration's `unsignedInteger('duplicate_rows')->default(0)->after('candidate_rows')` contract. Do not apply the migration; the new PR #894 guard is designed to fail closed.
- The same diagnostics run reports `official_source_refresh_events` readiness as `schema_ready=true`, required columns/indexes present, and its migration ledger row present. This updates the earlier October 28 finding for that separate migration.
- The public monitor [#40820996120](https://github.com/mattari20/estudent-listing/actions/runs/40820996120) passed its HTTP smoke workflow but classified the public listing as `EMPTY_DATA` (HTTP 200); no published institution detail URL was available to test. Guest requests to operator-only discovery history and review-package download returned 302 to the Institutes login.
