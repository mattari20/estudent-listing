# Phase 1C — Monitoring Pipeline Exception Recovery

Date: 2026-11-04
Base revision: `503c21ca657a81539e8d52059362eddd30dc53fe` (merged PR #998)

## Failure boundary found

The monitoring batch already handled retrieval results that returned a non-completed status. However, exceptions thrown by the source pipeline escaped the per-source loop. In that case, the policy-level failure transition and `last_run_at` update at the end of the policy were skipped. This could leave a due policy looking as though it had not been attempted, even though part of its source work had executed.

## Change in this delivery

- Catch exceptions from the per-source monitoring pipeline.
- Count the source attempt as failed and continue with independent sources.
- Raise/update the existing deduplicated retrieval-failure alert.
- Append a monitoring audit event containing the exception class and policy/source identifiers.
- Do not copy exception messages into alert/audit records because they may contain response content or sensitive provider details.
- Allow the normal policy finalization path to increment consecutive failures, set last-failure/next-run state, and record last-run time.
- Add a regression test that injects a thrown pipeline exception and verifies failure counters, scheduling timestamps, alert and sanitized audit evidence.
- Run the regression test in both SQLite Laravel CI and isolated MySQL validation.

## Explicit limits

- This catches exceptions thrown by `SourceMonitoringPipeline::run`. Exceptions thrown by the heartbeat, policy validation, alert/audit persistence or later discovery outcome processing are not all converted into per-source failure outcomes by this change.
- The current policy finalization is still not protected by a durable per-policy checkpoint/transaction. Process termination (SIGKILL, host loss, runner cancellation) can still interrupt between source side effects and policy state persistence.
- The batch does not yet expose a first-class per-policy run identity/state machine with started, heartbeat, completed and failed checkpoints.
- This change does not claim full exactly-once processing or complete recovery after arbitrary process death.

## Safety

Repository and isolated CI only. No production DB, SSH/hPanel, Artisan production command, migration, deployment, cron/worker change, source retrieval or candidate staging/approval/import/promotion/publication was performed. GCU protected rows #348–353 and batches #19–23 remain untouched. AI extraction and the additional 20 HEC profiles remain disabled.

## Acceptance

Require exact-head Laravel CI, Project Controller, and isolated MySQL checks to pass. Phase 1C remains open after this bounded recovery fix.
