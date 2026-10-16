# Production Read-Only Verification Runbook

| Field | Value |
| --- | --- |
| Document ID | RUN-DB-OPS-001 |
| Status | Proposed; use only after the reviewed release is deployed |
| Scope | Institutes Laravel application only |
| Application path | Hostinger `public_html/institutes` |
| Database writes | Prohibited in this runbook |

## Purpose

Verify the deployed Institutes application can connect to its configured database and preview the effect of the top-level Pakistan geography seed without modifying data. This runbook does not authorize a merge, deployment, migration, seed, or repair.

## Preconditions

- The pull request containing both commands has been independently reviewed and approved.
- The approved release has been deployed through the existing Hostinger Git integration. Do not change or recreate that integration.
- Open the Hostinger terminal at the Institutes Laravel application root (`public_html/institutes`).
- Confirm the current directory is the Institutes application root. Do not run commands from `public_html/cv-builder`.
- Do not paste or display `.env`, database passwords, application keys, or full connection strings.
- Keep Remote MySQL disabled.

If the reviewed release is not deployed, stop. The commands will not exist in the currently deployed version.

## Batch 1 — Confirm application root and deployed command availability

Run:

```sh
pwd
php artisan list --raw | grep -E '^institutes:(database-health|geography-seed-preflight)$'
```

Expected: the path is the Institutes Laravel root and both commands are listed. If either command is missing, stop and verify the deployed commit; do not run migrations or change deployment settings.

## Batch 2 — Read-only database health report

Run:

```sh
php artisan institutes:database-health --json
```

Save the report privately for review. It should include connection status, required-table status, directory readiness, geography integrity, geography population, and `mutation_performed: false`.

Interpretation:
- `healthy`: the configured checks passed.
- `warning`: investigate reported data gaps or empty geography reference data.
- `critical` or a non-zero exit: stop and investigate. Do not attempt a repair from this runbook.

This check is read-only but does query the configured application database. Do not share its output publicly if it contains operationally sensitive record counts.

## Batch 3 — Read-only geography seed impact preview

Only after Batch 2 has completed and its result has been reviewed, run:

```sh
php artisan institutes:geography-seed-preflight --json
```

Verify:
- `mutation_performed` is `false`;
- `seed_command_executed` is `false`;
- every proposed action is understood;
- duplicate identities and any `update_existing` actions are investigated before proceeding;
- the reported current count and proposed changes are recorded for the review.

A warning with create-only actions is not permission to seed. A critical status, conflict, duplicate, or unexpected count means stop.

## Prohibited actions

Do not run any of the following as part of this runbook:
- `php artisan migrate`, `migrate:fresh`, or rollback commands;
- `php artisan institutes:seed-pakistan-geography`;
- SQL `INSERT`, `UPDATE`, `DELETE`, `TRUNCATE`, or schema changes;
- commands targeting the CV Builder application or database;
- changes to Hostinger Git deployment settings, permissions, or remote MySQL access.

## Separate approval required for any write

A one-time seed may be considered only after an independently verified backup is confirmed, the full preflight report is reviewed, expected reference records are approved, and the owner gives explicit approval for the specific production write. If existing records would be updated, resolve the conflict first rather than accepting an overwrite.

## Evidence to record

Record the deployment commit, date/time, command exit codes, overall statuses, current geography count, proposed create/update/no-change counts, and reviewer. Redact credentials and any sensitive details. No scheduled automation is enabled by this runbook.


## Protected diagnostics and record-level report gate

The repository currently defines the protected endpoint at `/institutes/automation/diagnostics` and the private-report workflow expects aggregate, record-level, and geography-preflight sections. After the reviewed commit is confirmed deployed, verify freshness using a successful run of **Retrieve Institutes Production Diagnostics (Private)**. Check only the workflow outcome, timestamp, and private repository's newest report metadata; inspect record-level JSON only within the private repository.

A successful GitHub workflow alone does not prove the intended commit is deployed to Hostinger. Do not claim production record-level verification until deployment identity and the latest private report are confirmed. If either is unknown, stop and record the audit as blocked. Keep all database operations read-only; do not run migrations, seeders, imports, promotions, or SQL writes.