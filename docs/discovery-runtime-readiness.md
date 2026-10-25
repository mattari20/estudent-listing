# Discovery runtime readiness audit

## Purpose

The manually dispatched workflow `.github/workflows/discovery-runtime-readiness.yml` checks the deployed Institutes Directory discovery runtime on Hostinger without initiating a discovery run, changing canonical records, or applying migrations.

## Run

1. Open **Actions → Read-only Discovery Runtime Readiness**.
2. Select **Run workflow** on `main`.
3. Open the run's **Audit deployed discovery runtime without mutations** job logs.
4. Record the expected/deployed application revision, required table statuses, migration status, outcome totals, active/due monitoring policy counts, protected package route middleware, and canonical counts.

The workflow uses the existing Hostinger SSH secrets. It must fail closed on revision mismatch, missing required runtime tables, or package routes that are not protected by both `auth` and `institute.operator`.

## Safety boundaries

- Read-only database inspection only; `migrate:status` is informational.
- Does not execute `php artisan migrate`, refresh sources, create candidate batches, approve, promote, publish, import, delete, or repair data.
- Does not download private review-package contents. It verifies that the CSV/manifest routes are registered behind operator authentication; authenticated download and checksum behavior still require a separate session-level acceptance check.
- AI extraction is blocked if any configured profile enables `use_ai: true`; this workflow makes no paid API calls.
- A successful workflow execution means the audit ran. It does not mean every readiness condition passed. In particular, `official_source_refresh_events` reports as blocked when its migration/table is absent. Do not apply that migration without the separately approved migration procedure.
