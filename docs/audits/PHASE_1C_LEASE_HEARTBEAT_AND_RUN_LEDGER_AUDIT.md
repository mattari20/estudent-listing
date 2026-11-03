# Phase 1C — Lease Heartbeat and Execution Run Ledger

Date: 2026-11-03

Base revision: \`8a592e9d8e31302181903b12f2a96f91051e6d36\` (merged PR #988)

## Delivered in this change

- Extends the database-backed governed execution lease with an explicit heartbeat callback.
- Renews the lease only when the task key, owner token and unexpired lease still match.
- Throws on lost ownership; execution does not silently continue under an expired or superseded lease.
- Records a run row with task key, owner token, runner class, optional application revision, start/heartbeat/finish timestamps, outcome and sanitized failure class.
- Records append-only lifecycle events: started, heartbeat, completed and failed.
- Adds heartbeat calls between bootstrap profiles and source-monitoring policies/sources, and around manual discovery.
- Adds MySQL/SQLite feature coverage for lease contention through a second database connection, expiry recovery, stale-owner release, heartbeat renewal and failure recording.
- Extends formatting checks for the changed integration files.

## Important limits — not complete yet

This change does **not** complete all Phase 1C requirements:

1. Stable idempotency keys and uniqueness guarantees for retrieval runs, snapshots, observations, import batches, candidates and outcomes still require a separate audit/design and implementation.
2. The second-connection test verifies database claim exclusion but is not a simultaneous multi-process stress test.
3. The heartbeat is cooperative. A single blocking operation must return/control to the heartbeat caller before expiry; no asynchronous heartbeat thread is introduced.
4. The current two scheduler definitions remain. No provider-side Hostinger scheduler invocation has been established.
5. Production deployment, migration, runtime parity, AUTH-04, backup recoverability and provider cron/worker evidence remain separate gates.
6. GCU historical reconciliation has not been performed; rows #348–353 and batches #19–23 remain protected and unchanged.

## Safety

- CI uses isolated SQLite/MySQL only.
- No production migration, deployment, SSH/hPanel command, source retrieval or database mutation was performed.
- No candidate staging, approval, import, canonical promotion or publication was performed.
- Additional HEC profiles and AI extraction remain disabled.

## Acceptance status

This PR is an engineering sub-delivery for lease observability and renewal. It must pass exact-head Laravel CI, Test and isolated MySQL checks. It does not close Phase 1C.
