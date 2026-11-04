# Phase 1C-22 — Chronological Snapshot Predecessor Ordering and Out-of-Order Recovery

Date: 2026-11-04  
Repository: `mattari20/estudent-listing`  
Starting `main`: `fc54741ea246fe1c284ba397117b90ab3d87ca55`  
Phase 1C-21 PR: #1006 — merged at the starting SHA.

## 1. Scope and current implementation

This package corrects predecessor selection for source snapshots and makes late-arrival comparison drift explicit. It does not alter discovery, approval, rejection, import, promotion or publication behavior.

The authoritative chronology field is the existing `source_snapshots.captured_at`. For successful HTTP retrieval, it records the time the response body was received and is reused for `source_retrieval_runs.completed_at`. Existing `started_at` records request initiation and is not substituted for the actual time the body became observable.

No new timestamp column is introduced. The existing `(source_id, captured_at)` index is retained; snapshot ID supplies deterministic ordering for timestamp ties.

## 2. Root cause

The former comparison query filtered out only the current snapshot and then selected the latest remaining snapshot by descending `captured_at`. It did not require the predecessor timestamp to be earlier than the current snapshot. When an older snapshot was compared after a newer one had already been persisted, the newer snapshot could be selected as the older snapshot's predecessor.

The former query also omitted an ID tie-breaker, so snapshots sharing a timestamp had no explicit deterministic order.

## 3. Correct chronology contract

Snapshots are ordered by the tuple:

`(captured_at ASC, id ASC)`

For current snapshot C, the predecessor is the row with the greatest tuple strictly less than C's tuple:

- `captured_at < C.captured_at`, or
- `captured_at = C.captured_at AND id < C.id`.

The predecessor query orders by `captured_at DESC, id DESC` and returns one row. Equal timestamps therefore have a stable result: the lower snapshot ID is earlier.

`SourceSnapshotService::latest()` uses the same descending tuple order.

## 4. Concurrency and retrieval timestamp

Snapshot capture and comparison both lock the source row inside a database transaction. This creates one per-source serialization point for supported service paths while allowing independent sources to proceed concurrently.

The capture service accepts an optional domain timestamp for deterministic backfill/test scenarios. Ordinary capture defaults to the time the snapshot payload is captured. Retrieval explicitly takes one timestamp after a successful response body is received, passes it into snapshot persistence, and then uses the persisted snapshot timestamp as the retrieval run completion time. If a retry reuses an existing capture key, the original snapshot timestamp remains authoritative.

The database's existing `source_snapshots(source_id, captured_at)` index supports source-scoped chronological lookup. No migration was needed for the ordering query itself.

## 5. Out-of-order arrival and explicit reconciliation

A late snapshot can change the correct predecessor of a later snapshot that already has a comparison observation. Automatically changing that observation would rewrite historical evidence and could invalidate already-created assertion links, change events or reverification queue work.

The implementation therefore:

1. Persists the new snapshot without changing any existing snapshot or observation.
2. Computes the new snapshot's predecessor using the strict chronological tuple.
3. Scans existing observations in chronological order.
4. Detects any observation whose recorded `previous_snapshot_id` differs from the predecessor implied by the current ordered snapshot set.
5. Inserts a row into `source_snapshot_reconciliation_requests` using a deterministic SHA-256 identity over the affected current snapshot and the expected/recorded predecessor IDs.
6. Leaves the request in `pending_review`; a replay finds the same identity and does not create duplicates.

The queue stores source, triggering snapshot, affected current snapshot, expected predecessor, recorded predecessor, identity key, status and diagnostic details. It does not rewrite observation rows or downstream events.

### Manual reconciliation boundary

A pending request is an explicit work item, not an automatic correction. Review must inspect whether the affected observation has assertion links, projected change events, discovery outcomes or open/resolved reverification work. Where downstream effects already exist, a correction requires a separately reviewed, idempotent remediation that preserves prior history. This package deliberately does not delete, rewrite or replay those downstream records.

Historical snapshots with missing or unreliable `captured_at` values cannot be chronologically reconstructed from database IDs alone. No historical timestamp backfill or observation rewrite is attempted.

## 6. Regression coverage

Added focused coverage for:

1. Normal A → B → C ordering and exact predecessor IDs.
2. A → C → B persistence/compare order, with B selecting A and C receiving a pending reconciliation request.
3. Delayed retrieval completion, asserting `captured_at` equals the actual response-observation/completion time.
4. Equal timestamps, resolved by ascending snapshot ID.
5. Identical capture-key replay preserving the original snapshot ID and timestamp.
6. Duplicate observation prevention through the existing unique current-snapshot identity.
7. Change-event and reverification idempotency through existing `SourceChangeIntelligenceTest` replay assertions.
8. Concurrent same-source retrieval persistence and comparison using independent PHP processes on MySQL.
9. A late B arrival after C has already been compared, asserting C's historical predecessor is unchanged and one pending repair request records expected B versus recorded A.
10. Failure after snapshot persistence but before comparison completion.
11. Retry of the completed retrieval without another HTTP request, creating exactly one observation.
12. Preservation of snapshot content, source ownership and historical observation links.

## 7. Isolated MySQL proof

The dedicated independent-process script requires `DB_CONNECTION=mysql` and is wired into the isolated MySQL workflow. It creates two independent PHP workers behind a start barrier, persists retrieval runs and snapshots for the same source with deliberately different chronology timestamps, and compares the resulting database rows.

It verifies:
- B selects A;
- C selects B if B is visible before C comparison, or retains its earlier A comparison and receives a pending reconciliation request if C completed first;
- a forced late B arrival after C comparison preserves C's stored predecessor and creates exactly one request;
- exact capture replay reuses the same snapshot;
- repeated comparison does not create duplicate observations or reconciliation requests.

Final workflow IDs, exact PR head, actual MySQL output and test totals will be appended after exact-head validation. SQLite feature tests are not represented as the MySQL concurrency proof.

## 8. Phase 1C-21 merge reconciliation

- PR #1006 is merged at `fc54741ea246fe1c284ba397117b90ab3d87ca55`.
- Its final documentation-inclusive head was `514c87b4539821dbd31b9d01a932a39080749f2a`.
- Exact-head Test #41776912871 and Laravel CI #41776912841 passed with 880 tests / 3,877 assertions; Project Controller #41776912867 job passed with internal inventory REVIEW_REQUIRED 2/5; isolated MySQL #41776912864 passed, including operator transition 15 tests / 55 assertions and independent-process proof.
- The merge commit was verified as the Phase 1C-22 starting `main` SHA.
- Eight post-merge push workflows were observed. Six repository workflows passed. Hostinger Read-only Diagnostics #41778840327 and Automated Live Data Integrity Audit #41778840448 failed at their production-facing diagnostic steps. The Hostinger workflow skipped its subsequent public endpoint checks. These are not production readiness passes; no retries or production investigation were initiated.

## 9. Changed files

- `app/Services/SourceSnapshotService.php`
- `app/Services/SourceChangeObservationService.php`
- `app/Services/SourceRetrievalService.php`
- `app/Models/SourceSnapshotReconciliationRequest.php`
- `database/migrations/2026_11_04_000013_create_source_snapshot_reconciliation_requests_table.php`
- `tests/Feature/SourceSnapshotTest.php`
- `tests/Feature/SourceRetrievalTest.php`
- `tests/Support/verify-source-snapshot-ordering-multiprocess.php`
- `.github/workflows/laravel-ci.yml`
- `.github/workflows/isolated-mysql-approval-migration-validation.yml`
- `docs/PROJECT_MASTER_TRACKER.md`
- `docs/audits/PHASE_1C_OPERATOR_TRANSITION_ATOMICITY.md`
- `docs/audits/PHASE_1C_SNAPSHOT_CHRONOLOGICAL_ORDERING.md`

## 10. Remaining limitations and safety

- Reconciliation requests require reviewed downstream remediation; no automatic historical observation rewrite is performed.
- Snapshots with unreliable/missing capture timestamps cannot be ordered from insertion IDs as a substitute for observation chronology.
- Snapshot persistence and retrieval-run completion remain separate writes; retry after a comparison interruption is covered, but a changed remote payload during a retry of a partially persisted retrieval remains fail-closed through capture-key/content mismatch.
- No distributed exactly-once guarantee is claimed.
- Review-package row-set/evidence binding, database-enforced append-only audit retention, retrieval finalization failure boundaries, monitoring FK/direct concurrency assumptions and legacy nullable identity-key inventory remain separate P2 work.
- Production readiness is not certified.

Repository and isolated CI only. No production database, Hostinger/hPanel, production Artisan, deployment, cron/worker/schedule, source retrieval/discovery, staging, approval, rejection, import, promotion or publication operation was initiated by this Phase 1C-22 work. GCU rows #348–353 and batches #19–23 remain protected; AI extraction and the additional 20 HEC profiles remain disabled; no dummy production data was created.


## 11. Merge-safety gate

A merge of PR #1007 to `main` is not currently safe under the production restrictions:

- `.github/workflows/hostinger-readonly-diagnostics.yml` runs on every push to `main`. It invokes `appleboy/ssh-action@v1.2.0` and performs Hostinger diagnostics plus public production HTTP checks.
- `.github/workflows/automated-live-integrity-audit.yml` runs on pushes to `main` when application/database paths change. This PR changes `app/**` and `database/migrations/**`, so the push path matches. It invokes `appleboy/ssh-action@v1.2.0` and runs `php artisan institutes:data-integrity-report` on Hostinger, which accesses the production database read-only.
- The PR #1006 merge previously demonstrated both workflows starting on the merge push: Hostinger Read-only Diagnostics run #41778840327 and Automated Live Data Integrity Audit run #41778840448. Both failed at their production-facing diagnostic steps; no successful production readiness result was recorded.
- The current Phase 1C-22 request explicitly prohibits Hostinger SSH, production diagnostics and production database access. No workflow trigger, environment, secret or SSH behavior is changed by this PR.
- Therefore PR #1007 must remain open/draft even if all four repository/isolated-CI checks pass. Do not merge until the repository owner separately authorizes a safe workflow-separation change or another demonstrably non-production merge path. No bypass, skip or weakening of required checks is permitted.
