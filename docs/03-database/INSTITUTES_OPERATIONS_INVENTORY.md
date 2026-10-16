# Institutes Operations Inventory — Batch 6

| Field | Value |
| --- | --- |
| Project | eStudent.pk — Institutes & Education Directory |
| Status | Repository inventory; hosting-side facts remain unverified |
| Updated | 2026-10-15 |
| Scope | Read-only diagnostics, scheduler, monitoring, backups and recovery |

## Purpose

Inventory existing diagnostic capabilities before adding database-operations automation. Repository presence does not prove that a command has been deployed or run in Hostinger.

## Confirmed in repository

### Scheduler

`bootstrap/app.php` registers these hourly commands with `withoutOverlapping(55)`:

- `institutes:bootstrap-agent`
- `institutes:monitor-changes`

This is application scheduling configuration only. It does not prove Hostinger invokes Laravel's scheduler or that a scheduled task has completed successfully.

### Read-only database and directory diagnostics

- `institutes:database-health --json`: connection, required-table, canonical readiness and geography integrity/population checks.
- `institutes:geography-seed-preflight --json`: previews top-level geography seed effects without running the seed.
- `institutes:data-gap-report --json`: aggregates canonical counts and relationship gaps, geography, potential duplicate institution names, import-pipeline counts, and source/evidence/monitoring counts where the corresponding tables exist.

The data-gap report is intended for diagnosis before correction. It does not modify records, prove national coverage, establish that candidate duplicate names are actual duplicates, or authorize import/promotion.

Use `PRODUCTION_READ_ONLY_VERIFICATION_RUNBOOK.md` for the health check and geography preflight, and `PRODUCTION_DATA_GAP_REPORT_RUNBOOK.md` for the aggregated report. Verify the deployed commit and command availability before running anything in production.

### Monitoring features

The repository also contains:

- `institutes:monitor-health` for source-monitoring health;
- `institutes:monitor-alerts` for open/acknowledged monitoring alerts;
- source monitoring operations, policies and history views.

Source-monitoring alerts are not, by themselves, proof that database-health checks, backups, or restore procedures are monitored.

## Hosting-side items not verified

| Area | Current evidence | Required evidence |
| --- | --- | --- |
| Deployed release | Repository commit can be inspected | Hostinger deployment record or deployed revision confirmation |
| Scheduler execution | Scheduler entries exist in code | Actual Hostinger cron configuration and a successful execution record |
| Backups | No hosting-side verification in this inventory | Backup cadence, retention, last successful backup and restore procedure |
| Restore readiness | No restore-drill evidence recorded | A successful restore drill in a non-production environment |
| Production data-gap results | No direct production database connection available to this audit | Actual command output from the confirmed deployed release, reviewed privately |
| Database-health notifications | No verified database-health notification integration | Separate reviewed design and safe channel verification |

## Safe sequence

1. Review and test the data-gap report change.
2. Merge only after required checks pass and the change is approved.
3. Confirm the existing Hostinger auto-deployment has deployed the intended commit.
4. Run the read-only report following its runbook and securely review the result.
5. Record a sanitized summary and prioritize gaps before proposing any write.
6. Verify scheduler, backup and restore settings independently; do not infer them from source code.

## Safety boundary

No production database changes, migrations, seeds, imports, promotion, publication, hosting changes, or credentials are authorized by this inventory. Do not alter the existing Hostinger Git integration or enable Remote MySQL.


## Diagnostics execution evidence gate — 2026-10-16

The repository includes a protected diagnostics verification workflow and a private-report retrieval workflow. Their presence does not independently confirm production endpoint configuration, valid GitHub secrets, Hostinger deployment identity, or the latest report's freshness. Before using any report for remediation, record the latest successful run ID/time, report timestamp, deployed commit evidence, all three expected sections, and all read-only safety markers. If any value is unavailable, mark it unknown and keep production work read-only.

## Evidence levels

Use these labels consistently in progress reports:

- `REPOSITORY_VERIFIED`: source files and workflow definitions exist on the expected branch.
- `CI_VERIFIED`: required automated checks passed for the exact commit.
- `WORKFLOW_VERIFIED`: the relevant production-diagnostics workflow run completed successfully, with run URL and timestamp recorded.
- `DEPLOYMENT_VERIFIED`: an existing Hostinger deployment record confirms the exact commit/release is running.
- `REPORT_VERIFIED`: a fresh private report satisfies the runbook's complete contract and safety markers.
- `DATA_REVIEWED`: source evidence and row-level findings have been reviewed by a human.

Do not promote a lower level into a higher one by assumption. In particular, `CI_VERIFIED` does not imply `DEPLOYMENT_VERIFIED`, and `REPORT_VERIFIED` does not imply the data is correct.