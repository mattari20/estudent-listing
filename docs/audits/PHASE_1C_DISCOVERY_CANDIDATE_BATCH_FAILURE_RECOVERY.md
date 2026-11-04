# Phase 1C — Discovery and Candidate-Batch Failure Recovery

Date: 2026-11-04
Base: `8c9c7e389a290bdddb842164b5bf2da70294baf4`

## Scope and baseline

Repository-only implementation and isolated CI. Current `main` was verified identical to the supplied baseline before edits. PRs #993–#1001 and the existing downstream idempotency, MySQL concurrency, discovery outcome and policy checkpoint audits were inspected.

## Existing protections retained

- PR #993: unique snapshot capture identity and unique observation-per-current-snapshot identity.
- PR #994: unique discovery outcome idempotency key, versioned by observation, profile, extraction mode and extractor version.
- PRs #995–#997: source change event, reverification queue, alert and audit replay protections.
- PR #998: independent PHP process lease contention proof against isolated MySQL.
- PRs #999–#1001: monitoring exception recovery and durable policy/source checkpoints with failure-boundary recovery.
- `InstituteDiscoveryAgentService::persistCandidates()` already encloses evidence, batch, rows, assertions, final batch update and private review package creation in a database transaction while locking the source row.
- `InstituteIdentityDiscoveryService::stageSnapshot()` already uses the same source-row transaction/lock pattern.
- `SourceChangeDiscoveryService` already reuses the unique outcome key and retries an existing `failed` outcome rather than treating it as completed.

No duplicate implementation of these protections was added.

## Confirmed failure boundaries

| Boundary | Existing behavior / recovery |
|---|---|
| Discovery outcome creation | Unique outcome key prevents duplicate logical outcomes. A failed outcome can be updated on successful replay. An infrastructure failure inserting the outcome propagates; the already-committed candidate batch can be reused on retry. |
| Candidate batch creation | Candidate persistence is inside a transaction. Insert failure rolls back evidence and candidate data. A deterministic unique batch identity now protects the logical operation at the database layer. |
| Partial candidate row persistence | Any row failure rolls back the whole candidate transaction; no partial batch rows are committed. New rows have deterministic unique identities. |
| Evidence persistence | Evidence insertion is part of the same transaction and now has a unique batch-scoped identity. Failure rolls back the candidate batch. |
| Assertion/link persistence | Assertion rows are part of the same transaction and now have deterministic unique identities derived from their candidate row. Failure rolls back evidence, batch and candidate rows. |
| Batch completion/finalization | The final batch update remains inside the transaction. An update failure rolls back all related DB writes; no completed batch is committed. |
| Replay / duplicate candidates | A deterministic batch key is computed from source, profile, content hash, source type, extraction mode, extractor version and explicit superseded-batch ID. Existing logical batches are reused; new nullable unique constraints protect batch/evidence/row/assertion identities. |
| Interrupted transaction | A process death before commit is handled by database rollback. A subsequent attempt reuses the same deterministic identity and creates one committed logical batch. |
| Concurrent same-source attempts | Existing source-row lock serializes persistence; independent-process MySQL test exercises two concurrent attempts. Database unique keys are the final duplicate guard. |
| Related-record consistency | Evidence, batch, rows, assertions and batch finalization remain in one DB transaction. Discovery outcome is a separate later commit and can be retried without duplicating the batch. |

## Implementation

- Added `DiscoveryPersistenceIdentityService` with deterministic SHA-256 identities for discovery batches, evidence, candidate rows and candidate assertions.
- Added nullable unique identity columns to `import_batches`, `evidence`, `import_rows` and `assertions`. Historical rows remain NULL and are not rewritten, deleted or merged.
- Applied the same identities to deterministic/AI-mode generic discovery and deterministic institution identity discovery. Existing legacy-batch lookup remains as a compatibility fallback.
- Preserved the source-row lock and transaction boundary. No weaker approval, identity resolution or canonical promotion behavior was introduced.
- Added SQL failure injection for evidence insert, batch insert, second candidate-row insert, assertion insert and batch finalization update.
- Added review-package failure rollback/retry coverage.
- Added outcome-insert failure replay and partial candidate failure/recovery coverage.
- Added an isolated MySQL independent-process test. Two workers start behind a barrier and invoke the same profile/snapshot concurrently; both must return the same batch ID and the DB must contain one batch, two candidate rows, one evidence row and two assertion links.

## Filesystem boundary and limitation

The private review package consists of CSV and manifest files on the local filesystem. Filesystem writes are not transactionally coupled to MySQL. If the process dies after writing one artifact but before the second artifact or before DB commit, an orphaned private file/directory can remain. The package service reuses a package only when both files exist and the CSV checksum matches; otherwise a replay regenerates the package. This avoids treating a partial package as valid, but does not provide atomic filesystem/DB commit or automatic orphan cleanup. No outbox or cleanup worker is introduced in this delivery.

## Validation evidence

Pending exact PR-head Laravel CI, Test, isolated MySQL and Project Controller checks. The MySQL log must explicitly show:
- `InstituteDiscoveryAgentTest.php` failure-injection and replay tests passing.
- `SourceChangeDiscoveryIdempotencyTest.php` outcome failure/replay tests passing.
- `verify-discovery-batch-multiprocess.php` passing with one logical batch across two PHP processes.

Record exact run/job IDs and test totals here before merge.

## Safety and non-claims

- No production database, migration, deployment, SSH/hPanel, cron/worker, source retrieval, candidate staging, approval, import, promotion or publication operation is performed.
- No historical candidate is deleted or merged.
- AI extraction remains subject to existing restrictions; no AI profile is enabled or activated by this work.
- Additional 20 HEC profiles remain disabled.
- GCU rows #348–353 and batches #19–23 remain protected and untouched.
- No dummy production data.
- This is not exactly-once execution, a production concurrency proof, or a Phase 1C closeout. Phase 1C remains open and production readiness remains NOT CERTIFIED.
