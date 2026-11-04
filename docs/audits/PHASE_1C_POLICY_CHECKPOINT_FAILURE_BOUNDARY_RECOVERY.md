# Phase 1C — Monitoring Policy Checkpoint Failure-Boundary Recovery

Date: 2026-11-04
Base: `0ea84e9f2ffbdf948713a65d8afc1dc2e306e6d8` (merged PR #1000)

## Scope

Repository and isolated CI only. No production database, migration, deployment, SSH/hPanel, cron/worker, source retrieval, candidate staging, approval, import, promotion or publication operation is part of this change.

## Failure-boundary decisions

| Boundary | Recovery behavior |
|---|---|
| Policy validation exception | A policy attempt is opened before validation; validation failure is recorded with fixed code `policy_validation_failed`, then the original exception propagates. |
| Heartbeat exception / lost ownership | Heartbeats remain outside retrieval-error classification. The exception aborts the batch; no later source or policy is processed. The current policy/source attempt remains running for later interrupted classification. |
| Policy-run insertion failure | Propagates before source processing. No unlocked fallback or source retrieval occurs. |
| Per-source checkpoint insertion failure | Propagates before pipeline invocation. |
| Per-source failure/completion checkpoint failure | Propagates; the policy cannot be finalized successfully. A still-running attempt is classified as interrupted on a later invocation. |
| Policy-state persistence exception | Policy state, `last_run_at`, and policy-run finalization execute in one DB transaction and roll back together. |
| `last_run_at` update failure | Same transaction rolls back policy state and leaves policy attempt running. |
| Policy-run finalization failure | Same transaction rolls back policy state and `last_run_at`; no success checkpoint is recorded. |
| Abrupt process termination | No in-process recovery is claimed. The durable attempt/source states remain; the next invocation marks still-running prior policy attempts interrupted and replays the policy from the beginning. |

## Infrastructure versus retrieval failures

Ordinary network/URL-policy/HTTP retrieval failures are recorded as failed retrieval runs with safe class/status codes. Snapshot, observation, alert/audit projection, checkpoint, policy-state and database exceptions propagate as infrastructure failures; they are not converted into ordinary retrieval failures. Exception messages are not persisted by the retrieval service. Policy validation uses a fixed failure code.

## Ownership and replay guarantees

- Heartbeat checks occur before policy work, after validation, before each source, after the pipeline returns, before source completion, and before policy finalization.
- A heartbeat failure escapes and prevents subsequent processing.
- Source completion is recorded only after the pipeline and its downstream observation/alert/audit/discovery processing return and ownership is checked again.
- Policy state, `last_run_at`, and final policy status are transactionally committed together.
- Previous attempt rows are retained; new attempts classify still-running attempts as interrupted.
- Replay starts from the beginning and relies on existing downstream idempotency keys.
- Checkpoints are not atomic with all downstream side effects; a process can die after a side effect and before its checkpoint. This is replay exposure, not exactly-once execution.
- A heartbeat is a cooperative ownership check, not a database fencing token around every downstream side effect. No stronger guarantee is claimed.

## Validation evidence

Pending exact PR-head workflows. Required: Laravel CI, Test, isolated MySQL migration/recovery job and Institutes Directory Project Controller. The isolated MySQL job must explicitly show `SourceMonitoringBatchRecoveryTest.php` and `SourceRetrievalTest.php` passing. Record run/job IDs and test totals here before merge.

## Limitations and blockers

- A hard process kill cannot write a final failed/interrupted status; the next successful attempt performs interrupted classification.
- No automatic retry cadence, stale-run timeout, same-attempt resume, operator recovery command or provider scheduler convergence is introduced.
- The heartbeat check is cooperative; it cannot fence every external/downstream side effect at database level.
- No production runtime/provider/DB state is inferred from isolated CI.
- Phase 1C remains open; production readiness remains NOT CERTIFIED.

## Protected data and safety

GCU rows #348–353 and batches #19–23 remain protected and unchanged. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data is created.
