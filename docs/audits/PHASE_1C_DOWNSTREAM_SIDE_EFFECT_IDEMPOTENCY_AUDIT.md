# Phase 1C — Downstream Side-Effect Idempotency Audit

Date: 2026-11-03
Baseline: PR #990 merged at \`82bb869d60834c7f87567bd814ca36b1348608e7\`

## Scope

Read-only repository inspection of source snapshots, source-change observations, discovery outcomes and downstream assertion/event/reverification side effects. No production access or execution.

## Findings

### 1. Retrieval run idempotency — implemented, bounded

PR #990 adds a unique nullable idempotency key to \`source_retrieval_runs\`. Each new retrieval call defaults to a fresh UUID; callers can pass the same explicit key to reuse a completed or in-progress logical operation. Failed/stale runs can be retried on that same key. This correctly distinguishes a new monitoring retrieval from a retry.

### 2. Snapshot capture — no idempotency key / no content uniqueness

\`SourceSnapshotService::capture()\` always inserts a new snapshot. The schema indexes source/time and source/hash but does not enforce a unique operation key. Repeated capture after a timeout/retry can create multiple snapshots for the same logical retrieval. Do not use a unique source/content hash constraint: identical content captured at different times is a legitimate new observation.

Required design: persist a stable capture operation key (normally derived from the retrieval-run ID) with a unique constraint; retries of the same retrieval return the same snapshot. Different retrieval run IDs must be allowed to create distinct snapshots even if content hashes match.

### 3. Observation comparison — application-level replay guard, race gap

\`SourceChangeObservationService::compare()\` first queries for an observation by \`current_snapshot_id\` and returns it if found. The database schema has only a non-unique index on \`current_snapshot_id\`. Two concurrent callers can both see no row and insert duplicate observations. Add a unique constraint on \`current_snapshot_id\` after verifying historical duplicates are absent or safely reconciled; implement race-safe insert/retrieve behavior and MySQL tests.

### 4. Discovery outcomes — append-only duplicate risk

\`SourceChangeDiscoveryService::record()\` unconditionally creates an \`InstituteDiscoveryRunOutcome\`. The table has no idempotency key or unique key for observation/profile/operation. Replaying the same observation can append duplicate outcomes and may rerun discovery. Add an explicit operation identity (observation ID + profile key + outcome stage/version as appropriate) and unique constraint; distinguish intentional reprocessing under a new extractor version from replay of the same run.

### 5. Assertion links / events / queue — partial downstream idempotency

- \`SourceAssertionImpactService\` uses \`firstOrCreate\` for (observation, assertion), but schema-level uniqueness must be verified before treating concurrent calls as safe.
- \`SourceChangeEventProjector\` uses \`firstOrCreate\` with a timestamp-sensitive key; retry timing may not be stable.
- \`SourceChangeReverificationService\` uses \`firstOrCreate\` for an open queue item; status changes can permit later duplicates. Define whether reopening is intended and use a stable observation/link key if one event should enqueue once.

### 6. Monitoring batch / policy state / alerts

\`SourceMonitoringBatchService\` iterates policies and sources, invokes the pipeline, raises alerts and records audit entries. The shared governed execution lease reduces overlapping governed runners, but the service has no explicit run-scoped idempotency key passed through every side effect. Failure/retry after partial completion can repeat some writes. Audit/alert uniqueness and policy state transitions require a separate inspection before implementation.

## Required engineering sequence

1. Add snapshot operation key tied to retrieval-run ID and a unique database constraint.
2. Make observation creation race-safe with unique current snapshot identity.
3. Add discovery outcome operation identity with version-aware replay semantics.
4. Verify/add unique constraints for assertion links and stable event/reverification queue keys.
5. Add failure-injection and retry tests that simulate interruption between each side effect.
6. Run isolated MySQL concurrency tests with independent processes/connections; SQLite-only tests are insufficient.
7. Update the tracker with exact-head CI evidence before merge.

## Safety and non-claims

- This is repository-only audit work. No production database, SSH/hPanel, Artisan, migration, deployment, source retrieval or candidate staging was performed.
- No canonical approval, import, promotion or publication was performed.
- GCU rows #348–353 and batches #19–23 remain protected and untouched.
- Additional HEC profiles and AI extraction remain disabled.
- Phase 1C remains IN PROGRESS; this audit is not a readiness certificate or pilot authorization.
