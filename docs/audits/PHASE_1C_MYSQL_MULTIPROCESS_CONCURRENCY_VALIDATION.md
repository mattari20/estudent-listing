# Phase 1C — Independent-Process MySQL Lease Contention Validation

Date: 2026-11-04
Base: `4a98fc11e87282389251c57afb5396f555392e02` (merged PR #997)

## Purpose

The existing lease feature test uses a nested service call in one PHP process. It verifies a live lease blocks re-entry, but it does not prove that two independent PHP processes sharing MySQL contend correctly.

This change adds a dedicated isolated-MySQL process test. The first PHP worker acquires the task lease and publishes a filesystem barrier while holding it. Only after that barrier is observed does the parent start a second PHP worker against the same MySQL database and task key.

## Required assertions

- The first independent process reports `ACQUIRED`.
- The second independent process reports `REJECTED` while the first lease is live.
- Exactly one governed execution run row exists for the unique task key.
- The workflow runs this test only in its isolated MySQL service after migrations and the existing lease lifecycle tests.

## Scope and limitations

- The test proves one simultaneous two-process contention scenario against the CI MySQL service.
- It is not a high-volume stress test and does not prove every runner/provider topology.
- It does not validate production DB configuration, deployed schema, provider cron/worker state or Hostinger connectivity.
- It does not establish that all source, policy, batch, candidate and monitoring side effects are globally idempotent under process death.
- No production database, deployment, SSH/hPanel, Artisan production command, schedule/worker change, source retrieval, candidate staging/approval/import, canonical promotion or publication is performed.

## Acceptance

The PR must pass exact-head Laravel CI, Test and isolated MySQL checks. The isolated MySQL run must show the new independent-process step passing. Phase 1C remains open for scheduler-owner evidence, provider/production readiness, protected GCU read-only reconciliation authorization, end-to-end failure-boundary coverage and final go/no-go evidence.
