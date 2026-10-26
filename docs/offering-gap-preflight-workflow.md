# Guarded live offering-gap preflight workflow

The manual workflow is `.github/workflows/offering-gap-preflight.yml`. It is deliberately read-only and is not a data-repair or import workflow. The Hostinger SSH audit workflow is `.github/workflows/hostinger-readonly-offering-gap-audit.yml`; it can retrieve the private Hostinger reports as a short-retention GitHub artifact after SSH host-key verification.

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
6. For the Hostinger SSH audit workflow, add `HOSTINGER_SSH_FINGERPRINT` as a repository or environment secret containing the independently verified `SHA256:...` fingerprint for the Hostinger SSH host key. Do not derive trust solely from an unverified `ssh-keyscan` result. The workflow compares the scanned key against this pinned fingerprint before downloading reports.

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

## Hostinger private report retrieval

- The Hostinger workflow runs both audit commands on the host and keeps the JSON files under `storage/app/private/phase2-offering-gap-audits` with restrictive permissions.
- The workflow returns only the two report paths in action output; it does not stream row-level JSON into public logs.
- A separate runner step verifies the Hostinger SSH host key against `HOSTINGER_SSH_FINGERPRINT`, downloads the two files over SSH, validates the 36-row and zero-mutation contracts, and uploads them as a three-day GitHub Actions artifact.
- Artifact access is controlled by repository permissions. Keep repository membership restricted because row-level records can contain operationally sensitive information.
- If the fingerprint secret is absent or does not match, retrieval fails closed. Do not disable host-key verification to make the workflow pass.
- This retrieval feature does not resolve the shared-hosting database-user/grants limitation. The Hostinger-side application commands remain read-only by contract, but database-account grants still have not been independently confirmed as SELECT-only.

## Report interpretation

The report includes the exact commit SHA, timestamp, workflow run ID, command exit code, blockers, and zero-mutation declaration. A failed command still attempts to upload the report artifact. If the command fails before JSON output is produced, the job reports failure and no successful preflight should be inferred.


## Step 3 closure checklist (2026-10-26)

Before declaring the offering-gap remediation complete, record a disposition for every row in the 36-row live report:

- [ ] Confirm the report is from the intended Hostinger database and exact application commit.
- [ ] Confirm the report has 36 rows and explicitly reports zero mutations.
- [ ] For each row, record the official programme source, exact award-title match, campus source, canonical campus mapping, and any blockers.
- [ ] Keep ambiguous institution identities, generic award labels, online/virtual campus assumptions, and conflicting campus evidence in review or blocked status.
- [ ] Reconcile report row IDs against canonical programme and offering IDs; do not treat historic failure-event counts as unique unresolved records.
- [ ] Document a separate record-level proposed repair plan and duplicate checks before any future production write.
- [ ] Verify database grants independently; a successful audit command is not proof that the database account is SELECT-only.
- [ ] Record the workflow run URL, artifact/report checksum or identifier, inspected commit SHA, and reviewer disposition in the Step 3 tracking issue.

A green workflow is evidence that a process ran, not proof that all rows are correct or ready for promotion. No closure decision should be based only on aggregate counts.
