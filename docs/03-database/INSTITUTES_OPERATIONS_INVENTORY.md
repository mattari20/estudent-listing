# Institutes Operations Inventory — Batch 5

| Field | Value |
| --- | --- |
| Project | eStudent.pk — Institutes & Education Directory |
| Status | Repository-derived inventory; hosting-side items remain unverified |
| Updated | 2026-10-12 |
| Scope | Existing scheduler, monitoring commands, health visibility, backups, notifications |

## Purpose

Record what is already present before adding any database-operations automation. This inventory is based on the repository branch and does not claim that hosting-side services have been inspected.

## Confirmed in repository

### Laravel scheduler

`bootstrap/app.php` registers two hourly commands:

- `institutes:bootstrap-agent` with `withoutOverlapping(55)`.
- `institutes:monitor-changes` with `withoutOverlapping(55)`.

The scheduler callback is application code; this alone does not prove that Hostinger's cron entry or Laravel scheduler is running in production. Verify that separately before relying on scheduled operations.

### Existing monitoring commands

- `institutes:monitor-health` reports the source-monitoring health summary through `MonitoringHealthService`.
- `institutes:monitor-alerts` lists up to 100 open or acknowledged monitoring alerts.
- `institutes:monitor-changes` is the existing monitoring execution command; its documented scheduling is external/configurable, while `bootstrap/app.php` currently registers it hourly.
- `institutes:database-health` is the new read-only database and directory diagnostic introduced on the review branch.
- `institutes:geography-seed-preflight` is the new read-only preview of the top-level geography seed impact introduced on the review branch.

The two new database commands have not yet been merged or deployed. Do not assume they exist on production.

### Existing monitoring UI and services

The repository contains monitoring operations/dashboard controllers, alert and policy services, monitoring history views, and tests covering monitoring operations and scheduling governance. These are source-monitoring features; they should not automatically be treated as a complete database-operations dashboard or backup monitor.

## Not verified from repository

| Area | Current evidence | Required verification |
| --- | --- | --- |
| Hostinger cron / Laravel scheduler execution | Application scheduler entries exist | Confirm the actual cron/scheduler invocation and last successful run in Hostinger |
| Database backup cadence and retention | No hosting-side evidence in this repository inventory | Inspect Hostinger backup settings and document retention, restore method, and last successful backup |
| Restore readiness | No restore-drill evidence found in this inventory | Arrange a restore drill only in a non-production environment |
| Notifications for database-health failures | Existing monitoring alerts concern source monitoring; no database-health notification integration established here | Inventory configured delivery channels without exposing credentials, then propose a separate reviewed change |
| Production availability of the new commands | Commands exist only on the review branch | Merge after review, wait for approved auto-deployment, then verify command availability using the runbook |

## Safe next actions

1. Complete independent review and required CI for the current pull request.
2. After merge and approved auto-deployment, follow `PRODUCTION_READ_ONLY_VERIFICATION_RUNBOOK.md` exactly.
3. Verify scheduler and backup configuration in Hostinger without changing the existing Git deployment connection or enabling Remote MySQL.
4. Record observed settings and evidence; do not invent backup status or claim scheduler health without direct verification.
5. Create separate reviewed specifications before enabling recurring database-health checks, notifications, backup automation, or production remediation.

## Safety boundary

This inventory is documentation-only. It does not change application scheduling, production data, database schema, hosting configuration, backup settings, or notification credentials. It does not authorize migrations, seeding, or repairs.
