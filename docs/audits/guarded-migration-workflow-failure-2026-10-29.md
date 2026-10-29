# Guarded duplicate_rows migration workflow incident — 2026-10-29

## Status

**Open; no production schema mutation authorized.**

Repository: `mattari20/estudent-listing`  
Workflow: `.github/workflows/controlled-import-batch-duplicate-rows-migration.yml`  
Latest reviewed run: [Controlled Import Batch Duplicate Rows Migration #5](https://github.com/mattari20/estudent-listing/actions/runs/40818346167)  
Run commit: `195ad692c6f44bc34a3a71b19ae811de8d32fc5b`  
Latest diagnostic merge: [PR #891](https://github.com/mattari20/estudent-listing/pull/891)

## Confirmed evidence

1. The workflow's revision-resolution job passed.
2. Hostinger SSH connected and reported the deployed application revision.
3. `git fetch --no-tags --unshallow origin main` completed successfully.
4. `application_code_parity=VERIFIED` was printed.
5. The SSH action then returned status 255 before the PHP preflight JSON, `php_preflight_stage` output, or schema findings appeared.
6. The independent Hostinger read-only diagnostics workflow passed on the same commit: [run #40818197420](https://github.com/mattari20/estudent-listing/actions/runs/40818197420). This proves the general SSH diagnostics path is working, but does not prove the specific PHP preflight invocation works.
7. The workflow input `confirm_migration` was `false`. No migration was requested by this run, and no production database write is evidenced by the run.

## Interpretation — do not overstate

- The evidence does **not** prove that `import_batches.duplicate_rows` is missing.
- The failure is currently localized to the guarded migration workflow's remote command execution immediately after application-code parity.
- Status 255 alone does not identify whether the failure is caused by PHP invocation, shell/heredoc handling, process termination, or the SSH action/remote execution boundary. The next revision must make the stage and captured PHP exit/output observable.
- Do not manually add a column, edit the migration ledger, or run the migration until a read-only preflight returns the table, column, migration-file and ledger state.

## Remediation and validation gate

A candidate code repair is under review in [PR #892](https://github.com/mattari20/estudent-listing/pull/892). It changes the preflight and post-migration verification to execute explicit temporary PHP script files, prints start/exit markers, captures PHP output, and retains the dry-run default and single-migration restriction.

Required before closure:

- [ ] PR #892 passes Laravel CI, Test, and all required checks.
- [ ] After merge, dispatch the workflow from `main` with `confirm_migration=false`.
- [ ] Confirm the run emits preflight JSON or a specific PHP error and exit code.
- [ ] Verify `import_batches`, `duplicate_rows`, the exact migration filename, and the `migrations` ledger state using the returned evidence.
- [ ] If schema drift is reported, prepare a separate reconciliation plan; do not blindly retry or create a column manually.
- [ ] Only consider the single migration after a successful read-only preflight and separate explicit authorization.

## Project-wide audit implications

This incident is one item in the existing evidence-based audit plan tracked in PR #822. It does not replace the wider audit of deployment parity, CI/workflow triggers, schema/migration reconciliation, import and promotion lifecycle, data provenance, public routes, access control, SEO, and release-readiness gates.

Current main checks observed after PR #891 were successful for Laravel CI, Test, frontend build, public-site monitor, command orchestrator, and Hostinger read-only diagnostics. These are useful controls, but they do not substitute for the failed migration workflow's own live preflight.

**Closure rule:** close this incident only when the guarded workflow produces an interpretable read-only result and the schema/migration state is verified. A merged PR alone is not closure evidence.
