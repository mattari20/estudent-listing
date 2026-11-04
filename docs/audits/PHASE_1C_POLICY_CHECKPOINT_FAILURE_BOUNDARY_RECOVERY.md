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

<badge>Verified exact PR head</badge> `0f99212a500625b2792b8a1a0cdd1c186abeff1a` (PR #1001; base `0ea84e9f2ffbdf948713a65d8afc1dc2e306e6d8`).

| Workflow | Run / job | Result |
|---|---|---|
| Test | Run `41755801042`, job `126331495558` | PASS — 832 tests, 3,670 assertions |
| Laravel CI | Run `41755801150`, job `126331495504` | PASS — 832 tests, 3,670 assertions; Pint formatted and verified 29 files |
| Institutes Directory Project Controller | Run `41755801052`, job `126331563276` | PASS — controller syntax, offline fixtures, read-only inventory and report artifact |
| Isolated MySQL Approval Migration Validation | Run `41755801173`, job `126331495974` | PASS — migration/schema/ledger/idempotency and all isolated MySQL recovery steps |

### MySQL test log evidence

The isolated MySQL workflow log explicitly records:

- `SourceMonitoringBatchRecoveryTest.php`: **12 tests, 70 assertions — PASS**.
- `SourceSnapshotTest.php` + `SourceRetrievalTest.php`: **23 tests, 65 assertions — PASS**.
- Approval-specific migration tests: **17 tests, 87 assertions — PASS**.
- Execution lease recovery tests: **6 tests, 18 assertions — PASS**.
- Discovery outcome idempotency: **3 tests, 8 assertions — PASS**.
- Source change intelligence: **1 test, 8 assertions — PASS**.
- Monitoring alerts/audit: **6 tests, 16 assertions — PASS**.
- Independent PHP-process lease exclusion: PASS; one process acquired the MySQL lease, the competing process was rejected, and exactly one run was recorded.
- Dedicated step `Validate monitoring policy recovery and durable checkpoints on MySQL`: **completed successfully**.

All four workflows above were associated with the exact PR head SHA. The earlier failed run was due to a test-file namespace escape and was corrected; a separate full-suite compatibility issue around a policy-state test double and the safe HTTP status diagnostic was also corrected. The results above are from the final verified head, not those earlier attempts.

## Limitations and blockers

- A hard process kill cannot write a final failed/interrupted status; the next successful attempt performs interrupted classification.
- No automatic retry cadence, stale-run timeout, same-attempt resume, operator recovery command or provider scheduler convergence is introduced.
- The heartbeat check is cooperative; it cannot fence every external/downstream side effect at database level.
- No production runtime/provider/DB state is inferred from isolated CI.
- Phase 1C remains open; production readiness remains NOT CERTIFIED.

## Protected data and safety

GCU rows #348–353 and batches #19–23 remain protected and unchanged. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data is created.
