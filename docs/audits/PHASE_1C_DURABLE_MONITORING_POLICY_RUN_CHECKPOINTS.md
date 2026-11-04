# Phase 1C — Durable Monitoring Policy Run Checkpoints

Date: 2026-11-04
Base: `473805fa0c294f09e4af1d9f1c9f2c76a7250de9` (merged PR #999)

## Gap addressed

The shared governed execution ledger records the overall command run, but previously did not persist policy-level or source-level progress. A process could die after processing one source and before policy finalization, leaving no durable record of which policy/source had reached a completion boundary.

## Delivered

- Add `monitoring_policy_runs` with a durable policy attempt, optional parent governed execution run ID, status, start/finish timestamps, summary and sanitized failure code.
- Add `monitoring_policy_run_sources` with one unique checkpoint per source within a policy attempt, status, retrieval run ID, observation ID and timestamps.
- Link the monitoring command's per-policy attempts to the shared governed execution run UUID.
- Record a source as running before its pipeline starts; mark it completed only after retrieval, observation intelligence, alert/audit projection and discovery outcome processing have returned.
- Record failed retrievals and thrown pipeline exceptions at source level.
- When a later attempt starts, mark prior still-running attempts for that policy as interrupted, retaining their history.
- Add tests for exception checkpoints and recovery classification of an abandoned running attempt.
- Include the new migration and tests in isolated MySQL validation and formatting CI.

## Recovery semantics and limits

- This is a durable progress ledger and replay boundary, not a same-run continuation engine. A later invocation creates a new attempt and replays the due policy from the beginning.
- If a process dies after side effects but before the source completion checkpoint, that source may be replayed. Correctness still depends on the existing retrieval, snapshot, observation, event, alert, queue, and discovery idempotency protections.
- The checkpoint does not make every downstream side effect atomic with the checkpoint. It records progress after the existing pipeline returns.
- Exceptions during policy validation, heartbeat, checkpoint persistence, policy-state persistence or run finalization can still leave an attempt marked running until a later attempt classifies it as interrupted.
- No automated retry schedule, operator resume command, or stale-run timeout policy is introduced.
- This is not proof of production runtime/provider topology and does not certify production readiness.

## Safety

Repository and isolated CI only. No production DB, migration, deployment, SSH/hPanel, Artisan production command, cron/worker change, source retrieval, candidate staging/approval/import, canonical promotion or publication. GCU protected rows #348–353 and batches #19–23 remain untouched. AI extraction and the additional 20 HEC profiles remain disabled.

## Acceptance

Require exact-head Laravel CI, Project Controller and isolated MySQL checks. Inspect the MySQL checkpoint test result before merge. Phase 1C remains open.
