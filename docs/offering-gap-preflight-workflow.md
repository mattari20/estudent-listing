# Guarded live offering-gap preflight workflow

The manual workflow is `.github/workflows/offering-gap-preflight.yml`. It is deliberately read-only and is not a data-repair or import workflow.

## One-time repository configuration

1. In GitHub, open **Settings → Environments** and create `offering-gap-readonly-audit`.
2. Restrict who can run this environment. If available, require an authorized reviewer before the job starts.
3. Add these **environment secrets** to that environment:
   - `OFFERING_GAP_DB_HOST`
   - `OFFERING_GAP_DB_PORT`
   - `OFFERING_GAP_DB_DATABASE`
   - `OFFERING_GAP_DB_USERNAME`
   - `OFFERING_GAP_DB_PASSWORD`
4. The database username must be a dedicated least-privilege account with SELECT-only access to the four tables needed by the command: `programs`, `institutions`, `campuses`, and `program_offerings`. Do not use a site administrator or database owner account. Grant no INSERT, UPDATE, DELETE, DDL, or migration privileges. Restrict its network access to the minimum required.
5. Do not put credentials in workflow inputs, repository files, logs, artifacts, or issue comments. Do not configure these secrets until the database account is confirmed read-only.

The workflow intentionally fails before connecting if any required secret is absent. Secret names are new, dedicated names; this change does not assume existing Hostinger credential names or expose any existing secret.

## Running an audit

1. Deploy the application command and evidence register at the same revision, and identify the exact commit SHA to inspect.
2. Open **Actions → Read-only offering-gap preflight → Run workflow**.
3. Enter the exact 40-character commit SHA. Branch names are rejected.
4. Review the workflow result and download the short-retention report artifact only if authorized. The report includes per-program/campus findings and may contain operationally sensitive data; access should remain limited to trusted repository/environment operators.
5. Inspect both `preflight_status` and each row's `blockers`. A successful workflow means the command executed, not that all 36 rows are ready or that production repair is approved.

## Safety guarantees and limitations

- The workflow checks out the exact requested SHA and fails closed if the Laravel entry point, preflight command, or evidence register is missing.
- It runs the existing `php artisan institutes:offering-gap-preflight --json --no-ansi` command.
- It does **not** run migrations, seeders, imports, promotions, or any write/apply command.
- It verifies the report explicitly declares `database_mutations: 0` and `mutation_performed: false`; otherwise the job fails.
- The artifact is retained for three days. GitHub artifact access is governed by repository permissions; this is not a substitute for restricting repository membership and environment access.
- The database-side account must be genuinely read-only. Workflow code alone cannot turn a privileged database account into a read-only account.
- No database records are created, changed, activated, deactivated, published, or deleted by this workflow.
- `READY_FOR_MANUAL_REVIEW_ONLY` is not permission to write production data. Any future apply operation needs a separate reviewed record-level plan and explicit authorization.
- A run cannot be considered a live database verification until the required environment secrets are configured and a real workflow run completes against the intended database.

## Report interpretation

The report includes the exact commit SHA, timestamp, workflow run ID, command exit code, blockers, and zero-mutation declaration. A failed command still attempts to upload the report artifact. If the command fails before JSON output is produced, the job reports failure and no successful preflight should be inferred.
