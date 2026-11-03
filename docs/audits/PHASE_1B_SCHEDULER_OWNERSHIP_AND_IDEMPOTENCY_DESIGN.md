# Phase 1B — Scheduler Ownership, Locking and Idempotency Design

Date: 2026-11-03
Baseline: `d8a876ea0de43453b9a5c29e50b2fced78396e04`
Scope: repository inspection and design only; no live scheduler changes.

## 1. Observed execution paths

| Path | Evidence | Status / limitation |
|---|---|---|
| GitHub Actions schedule | `.github/workflows/scheduled-institute-discovery.yml` has cron `15 * * * *` | Configured workflow path; workflow enablement and future run success must be checked at execution time |
| GitHub workflow concurrency | Same workflow has group `scheduled-governed-institute-discovery`, `cancel-in-progress: false` | Serializes runs in this workflow group only; not a lock shared with Laravel/Hostinger |
| GitHub SSH command | Workflow invokes `php artisan institutes:bootstrap-agent` then `php artisan institutes:monitor-changes` on Hostinger | Confirmed in workflow source |
| Laravel scheduler | `bootstrap/app.php` schedules both commands hourly with `withoutOverlapping(55)` | Confirmed in application source |
| Hostinger scheduler invocation | AUTH-05 owner inspection found no saved user cron entry in inspected hPanel view; system/provider scope not visible | Unknown; absence is not proven |
| Persistent worker | Account process inspection found no persistent worker; provider scope unavailable | Unknown outside inspected account/process scope |
| Manual Artisan | Commands can be invoked directly by an authorized shell/operator | Exists as an execution path; operational policy and authorization remain separate |

Both GitHub Actions and Laravel schedule definitions target the same two commands. GitHub concurrency and Laravel `withoutOverlapping(55)` are independent mechanisms. A Laravel cache lock does not prove cross-runner exclusion unless the configured cache backend and shared storage are verified. No common execution lease is established by current evidence.

## 2. Risk statement

If Hostinger invokes Laravel `schedule:run` while GitHub's hourly workflow invokes the same commands, the two runners may overlap. The workflow concurrency group prevents only duplicate GitHub workflow runs in that group. Laravel's scheduler lock protects only runners sharing the same supported cache lock store. Neither fact proves exclusion across GitHub-hosted and Hostinger-hosted execution.

## 3. Recommended target architecture (decision proposal)

Use GitHub Actions as the single authoritative hourly orchestration owner because the existing workflow already provides:
- a bounded schedule and workflow concurrency group;
- approved application revision resolution;
- schema-handoff checks for migration-triggered runs;
- Hostinger SSH execution;
- pre/post canonical-count checks and discovery outcome checks;
- explicit no-AI, no-canonical-promotion and no-automatic-approval assertions.

Under this proposal, Laravel scheduler definitions for these same two commands must not independently run in production. Retain Artisan commands for explicit, authorized operator invocation and testing. This is a proposal, not a live decision: confirm Hostinger cron/provider scope and owner approval before disabling or changing any schedule.

Add a second, application-level shared execution lease before implementation. This lease is defence-in-depth for workflow retries, direct Artisan invocations and any residual scheduler overlap during migration. It must use a durable store shared by all authorized runners; do not assume the current cache driver is shared.

## 4. Execution lease contract

The implementation proposal must define:
1. Stable task keys, at minimum `institutes:bootstrap-agent` and `institutes:monitor-changes`, plus a run/correlation ID.
2. Atomic lease acquisition with unique owner token, acquired-at timestamp, expiry, heartbeat/renewal policy and bounded TTL.
3. A runner that cannot acquire a live lease exits with a distinct safe no-op/locked result and does not retrieve sources or write candidate batches.
4. Lease release only by the owner token that acquired it; a stale runner cannot release a successor's lease.
5. Expired lease recovery after process crash, with audit evidence and conservative TTL.
6. Explicit distinction between a completed run, failed run, abandoned run and skipped duplicate.
7. Manual invocation uses the same lease and cannot bypass it.
8. Lock acquisition failure, storage outage or ambiguous ownership fails closed before source retrieval or candidate writes.
9. A durable run ledger records task key, runner, revision, profile scope, start/end, outcome, lock owner and sanitized failure code. Do not log credentials or raw private payloads.
10. Retention/cleanup must not delete audit history or active leases.

## 5. Idempotency and side-effect contract

Before implementation, inspect and specify idempotency for each side effect:
- source registration/update by stable source identity;
- monitoring policy registration by source/profile identity;
- source snapshot uniqueness and retrieval run correlation;
- observation deduplication by source/snapshot/content identity;
- import batch creation keyed by a stable run/idempotency key;
- candidate row deduplication by batch + stable normalized identity;
- review package creation/reuse for the same batch;
- discovery outcome ledger exactly once per run ID.

Retries must resume or return the prior result for the same idempotency key, not create a second batch or duplicate observations. A new intentional refresh must use a new run ID and preserve prior batches as immutable history. Canonical institution/program tables remain untouched by discovery and monitoring.

## 6. Required concurrency and recovery tests

- Two concurrent bootstrap invocations: exactly one lease owner and no duplicate side effects.
- Two concurrent monitoring invocations: exactly one lease owner for the same scope.
- GitHub-triggered and Laravel/manual-triggered invocations: shared lease excludes overlap.
- Expired lease after simulated process death: safe recovery after TTL.
- Stale owner release after lease takeover: rejected.
- Lock-store unavailable: fail closed before source retrieval.
- Retry with same idempotency key: same run/batch result, no duplicate rows/observations.
- New intentional run key: new immutable batch, prior batch preserved.
- Partial failure after snapshot but before batch: deterministic resume/reconciliation behavior.
- Outcome ledger write failure: run is not falsely reported as complete.

## 7. Decision gates before code implementation

- Obtain provider/Hostinger confirmation for cron and worker scope, or document the precise unobservable boundary.
- Owner approves one authoritative scheduler.
- Confirm production cache/database capabilities using a separately authorized metadata-only method; AUTH-04 is currently bypassed/not verified.
- Choose and test the shared lease backend.
- Review lock schema/migration and recovery impact in isolated CI/MySQL before any production migration proposal.
- Approve a rollback plan and a narrowly scoped production authorization before changing schedules or lock configuration.

## 8. Explicit non-actions

This design did not run Artisan commands, inspect or change production cron/queue/worker state, connect to the production database, change Laravel schedule definitions, modify GitHub workflow triggers/concurrency, retrieve sources, create candidate batches, or deploy code. Provider-managed cron and workers remain unknown.
