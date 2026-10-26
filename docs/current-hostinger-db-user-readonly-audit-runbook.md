# Current Hostinger Database User: Read-Only Audit Runbook

Date: 2026-10-26

## Purpose

Run the offering-gap preflight and reconciliation against the existing Hostinger database account when the shared-hosting plan does not allow a second MySQL user. This procedure is for audit/reporting only; it does not authorize production data repair.

## Existing connection

- Database: `u537436044_Institutes`
- Username: `u537436044_institutes_app`
- Host: retrieve the exact MySQL hostname from Hostinger hPanel.
- Port: verify in hPanel; commonly `3306`.
- Password: use the existing database user's password; never commit or paste it into chat, issues, logs, or repository files.

## GitHub Environment and secrets

Open **Settings → Environments → `offering-gap-readonly-audit`** in the repository. Add these as *environment secrets*, not plaintext workflow variables:

- `OFFERING_GAP_DB_HOST`
- `OFFERING_GAP_DB_PORT`
- `OFFERING_GAP_DB_DATABASE`
- `OFFERING_GAP_DB_USERNAME`
- `OFFERING_GAP_DB_PASSWORD`

These values authenticate the workflow to MySQL. They do not grant or reduce MySQL permissions by themselves. Do not echo values to logs.

## Mandatory safety gates before the first live run

1. Verify the exact host, port, database, and username in hPanel.
2. Determine the existing user's grants using Hostinger's supported database-management interface or support. Do not assume the account is read-only merely because the workflow only intends to read.
3. If Hostinger cannot provide read-only grants for the existing user, classify this as **application-enforced read-only with elevated credential risk**. Continue only with explicit owner acceptance of that residual risk; do not describe it as database-enforced read-only.
4. Review the exact approved commit SHA. The workflow requires the full 40-character SHA and checks out that revision.
5. Confirm the commands `institutes:offering-gap-preflight` and `institutes:offering-gap-reconcile` contain only read operations and report `database_mutations=0`, `mutation_performed=false`, and `production_write_authorization=false`.
6. Do not run migrations, imports, approvals, cleanup, or remediation writes from this workflow.

## Run and verify

1. Open **Actions → Read-only offering-gap preflight → Run workflow**.
2. Supply the exact reviewed 40-character commit SHA.
3. Inspect the run logs without exposing secrets.
4. Download the short-retention report artifact and verify both command exit codes, report row counts, and the safety fields.
5. Treat any missing secret, failed query, schema mismatch, nonzero mutation counter, or unexpected report shape as a failed/blocked audit. Do not bypass the failing check.
6. Save the run URL and report summary in the remediation record. A successful audit report is not permission to write to production and does not alone close Phase 2.

## Security limitation

The workflow's application checks, a Laravel read-only transaction, and zero-mutation counters are defense-in-depth; none of these changes the MySQL account's actual grants. Database-level least privilege is the preferred control. Where the hosting plan prevents a dedicated account, document that limitation explicitly and keep all write/remediation operations outside this workflow.

## Completion checklist

- [ ] Environment exists and is restricted to authorized maintainers.
- [ ] Five environment secrets are present (values never printed).
- [ ] Connection details were verified in hPanel.
- [ ] Existing user's grants are verified, or residual elevated-credential risk is explicitly recorded.
- [ ] Exact reviewed SHA was used.
- [ ] Both read-only commands completed successfully.
- [ ] Fresh artifact shows expected rows and zero mutations.
- [ ] No production data was changed.
