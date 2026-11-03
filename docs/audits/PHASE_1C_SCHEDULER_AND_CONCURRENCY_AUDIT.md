# Phase 1C — Scheduler and Cross-Runner Concurrency Audit

Date: 2026-11-03
Repository: `mattari20/estudent-listing`
Verified main baseline: `ce61b95b724bd41626ec41f608124ecf4393d82b`
Scope: repository source inspection and CI configuration only
Status: CONCURRENCY RISK CONFIRMED — PRODUCTION INVOCATION SCOPE NOT VERIFIED

## Executive finding

The repository defines two independent hourly scheduling paths for the same governed commands:

1. GitHub Actions workflow `.github/workflows/scheduled-institute-discovery.yml` schedules at minute 15 of every hour and invokes the two commands over Hostinger SSH.
2. Laravel `bootstrap/app.php` schedules both commands hourly and applies Laravel `withoutOverlapping(55)`.

The GitHub workflow has concurrency group `scheduled-governed-institute-discovery`, with `cancel-in-progress: false`. This serializes runs in that GitHub workflow group only. Laravel `withoutOverlapping(55)` uses Laravel's scheduler lock mechanism. The repository contains no evidence that the two mechanisms share one durable lock namespace across GitHub-hosted and Hostinger-hosted execution.

**Risk:** if Hostinger/provider cron invokes Laravel `schedule:run` while a GitHub Actions SSH execution is active, both paths may execute the same commands concurrently. The exact provider-side invocation remains UNKNOWN; the duplicate scheduling definitions are confirmed, but live Hostinger scheduler invocation is not established by repository inspection.

## Exact source evidence

| Area | File | Observed behavior |
|---|---|---|
| Laravel schedule | `bootstrap/app.php` | `institutes:bootstrap-agent` hourly with `withoutOverlapping(55)` |
| Laravel schedule | `bootstrap/app.php` | `institutes:monitor-changes` hourly with `withoutOverlapping(55)` |
| GitHub schedule | `.github/workflows/scheduled-institute-discovery.yml` | Cron `15 * * * *` |
| GitHub concurrency | Same workflow | Group `scheduled-governed-institute-discovery`, cancellation disabled |
| Remote execution | Same workflow | SSH to Hostinger and execute the two Artisan commands |
| Bootstrap command | `app/Console/Commands/BootstrapInstituteAgent.php` | Registers/updates sources and policies; may perform initial discovery unless `--no-discovery` is supplied |
| Monitoring command | `app/Console/Commands/MonitorSourceChanges.php` | Runs source monitoring batch unless `--dry-run` is supplied |

## Existing protections and limitations

### Bootstrap / candidate persistence

- Source registration uses `updateOrCreate` by source URL.
- Bootstrap checks the latest automated-discovery/AI batch and skips populated batches or current extractor-version empty outcomes.
- Candidate persistence uses a database transaction and `lockForUpdate()` on the source row.
- Existing snapshot/batch lookup is used to reuse results for the same snapshot.
- These controls reduce duplicate work, but do not provide one shared command-level execution lease across all runners.
- Source registration and monitoring-policy setup occur before candidate-persistence locking. They are not protected by a common command lease.

### Monitoring

- Monitoring creates retrieval-run records and captures source snapshots.
- Observation comparison first checks for an existing observation for the current snapshot.
- This is an application-level reuse guard, not a global runner lease.
- A concurrent retrieval may create distinct snapshots and therefore distinct observations; command-level mutual exclusion is not established by these checks.

### CI limitation

- `.github/workflows/laravel-ci.yml` runs the full Laravel suite on SQLite.
- `.github/workflows/test.yml` also runs the full Laravel suite on SQLite.
- An isolated MySQL workflow exists for the institution-field-approvals migration, but it does not run the full discovery/monitoring concurrency suite.
- Therefore existing green full-suite CI does not prove MySQL row-locking behavior or cross-runner exclusion for the discovery and monitoring pipeline.

## PR #986 validation evidence

PR #986 was merged at `ce61b95b724bd41626ec41f608124ecf4393d82b`.

Exact-head workflow runs on PR head `c6e5c062f6c5d0c15873d3cf0cd6de8caaff0261`:
- Laravel CI run #41629643613: PASS — 798 tests, 3,531 assertions.
- Test run #41629643597: PASS — 798 tests, 3,531 assertions.
- Institutes Directory Project Controller run #41629643640: PASS.

The Laravel/Test runs use SQLite as described above. They do not validate production state or MySQL concurrency.

## Required remediation design

1. Add one durable shared execution lease for the two governed commands, keyed by command/task scope.
2. Require all scheduled and manual entry points to acquire the same lease before source registration, retrieval, monitoring-policy changes, snapshots, or candidate writes.
3. Use an atomic database-backed claim or another verified shared durable backend. Do not use an in-process mutex.
4. Store a unique owner token, run ID, task key, acquired-at, expiry and heartbeat.
5. Release only when the owner token still matches; stale owners must not release a successor's lease.
6. Fail closed if the lease backend is unavailable or ownership is ambiguous.
7. Add append-only execution outcomes and stable idempotency keys for retrieval, snapshots/observations, batches and candidate rows.
8. Add isolated MySQL integration and concurrency tests covering simultaneous bootstrap, monitoring, cross-entrypoint contention, expiry, stale-owner release and failure recovery.
9. After the shared lease passes, converge to one authoritative scheduler. The preferred design remains GitHub Actions, subject to provider evidence and owner approval.
10. Remove the Laravel schedule entries only after the actual Hostinger/provider invocation path and rollback are established.

## Open operational questions

- Does Hostinger or another provider invoke `php artisan schedule:run`? The repository cannot answer this.
- Are persistent queue workers or supervisors running? Not established by this repository audit.
- Is the production database schema ready for a shared lease/run-ledger migration? Not verified.
- Is the production cache/database backend shared by every execution path? Not verified.

## Non-actions

No production database, SSH, hPanel, Artisan, cron, queue or worker operation was performed. No migration, deployment, schedule change, source retrieval, candidate staging, approval, import, canonical promotion or publication occurred. GCU rows #348–353 and batches #19–23 remain untouched. Additional HEC profiles and AI extraction remain disabled.

## Decision

The next safe engineering step is the shared lease and idempotency implementation with isolated MySQL tests. Scheduler removal/convergence must follow, not precede, that protection and confirmation of provider-side invocation. Production rollout remains separately authorized.
