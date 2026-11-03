# Phase 1C — Source Retrieval Idempotency

Date: 2026-11-03

## Scope

This delivery adds deterministic idempotency for source retrievals only. It does not claim complete pipeline idempotency.

## Contract

- A default retrieval key is derived from the source ID and current UTC hour slot.
- A unique nullable key is stored on source retrieval runs. Historical rows remain valid with a null key.
- Repeated calls for a completed key return the existing completed retrieval and do not fetch the source again.
- A fresh in-progress key is not started a second time.
- Failed or stale in-progress keys may be retried by reusing the same run row and key.
- The shared governed execution lease remains the primary cross-runner serialization mechanism; this key is a retry/idempotency backstop.
- A caller may supply an explicit key for deterministic retry behavior.

## Tests

- Completed retrieval reused with one HTTP request.
- Failed retrieval retried with the same run ID and key.
- Fresh in-progress retrieval not started twice.
- Existing public/private URL and HTTP failure regression tests retained.

## Boundaries

Still outstanding:
- true simultaneous multi-process MySQL lease contention;
- idempotency for snapshot capture, observations, import batches, candidate persistence and discovery outcomes;
- scheduler-owner convergence and provider-side Hostinger inventory;
- production revision/schema/backup evidence;
- authorized GCU historical reconciliation and final Phase 1C closeout.

No production migration, deployment, source retrieval, candidate staging, approval, import, canonical promotion or publication was performed by this change.
