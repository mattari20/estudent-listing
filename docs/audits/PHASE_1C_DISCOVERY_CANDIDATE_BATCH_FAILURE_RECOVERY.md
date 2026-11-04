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
| Replay / duplicate candidates | A deterministic batch key is computed from source, profile, content hash, source type, extraction mode, extractor version and explicit superseded-batch ID. Existing logical batches are reused only when `status=validated` and `completed_at` is present; an incomplete matching legacy batch fails closed rather than being reported complete or silently duplicated. New nullable unique constraints protect batch/evidence/row/assertion identities. |
| Existing discovery outcome replay | Outcomes that claim candidates/duplicates/empty discovery are returned only after the referenced batch is found and confirmed validated with a completion timestamp. Missing/incomplete referenced batches fail closed. |
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
- Added regression tests proving incomplete legacy batches and successful outcomes referencing incomplete batches are never reported as completed.
- Added an isolated MySQL independent-process test. Two workers start behind a barrier and invoke the same profile/snapshot concurrently; both must return the same batch ID and the DB must contain one batch, two candidate rows, one evidence row and two assertion links.

## Filesystem boundary and limitation

The private review package consists of CSV and manifest files on the local filesystem. Filesystem writes are not transactionally coupled to MySQL. If the process dies after writing one artifact but before the second artifact or before DB commit, an orphaned private file/directory can remain. The package service reuses a package only when both files exist and the CSV checksum matches; otherwise a replay regenerates the package. This avoids treating a partial package as valid, but does not provide atomic filesystem/DB commit or automatic orphan cleanup. No outbox or cleanup worker is introduced in this delivery.

## Validation evidence

**Validated implementation head:** `5e55b41c23002ff3acf66fbbb69be4d918ee0b2f` (PR #1002; base `8c9c7e389a290bdddb842164b5bf2da70294baf4`).

| Workflow | Run / job | Result |
|---|---|---|
| Test | Run `41759567182`, job `126343758800` | PASS — 843 tests, 3,755 assertions |
| Laravel CI | Run `41759567170`, job `126343758594` | PASS — 843 tests, 3,755 assertions; Pint format and verification passed for 46 files |
| Institutes Directory Project Controller | Run `41759567189`, job `126343776353` | PASS — workflow syntax, offline fixtures, read-only inventory and report artifact |
| Isolated MySQL migration/recovery | Run `41759567167`, job `126343758707` | PASS — isolated MySQL 8.4 migration, schema and recovery workflow |

### Actual isolated MySQL log evidence

- `SourceChangeDiscoveryIdempotencyTest.php`: **6 tests, 40 assertions — PASS**.
- `SourceChangeDiscoveryServiceTest.php`: **5 tests, 17 assertions — PASS**.
- `InstituteDiscoveryAgentTest.php`: **16 tests, 115 assertions — PASS**.
- Dedicated discovery outcome batch consistency step — PASS.
- Dedicated candidate batch failure recovery step — PASS.
- Independent-process concurrency script — PASS. Log: two PHP processes concurrently persisted one logical discovery batch; both returned batch #16, with one batch, two candidate rows, one evidence record and two assertion links.
- Existing source snapshot/retrieval, event/reverification, alert/audit, execution-lease and monitoring-policy MySQL recovery steps also passed.
- The migration/schema and migration-ledger checks completed successfully.

An earlier full-suite run exposed a stale mock fixture that returned a hard-coded candidate batch ID without persisting a completed batch. The fixture was corrected to create a completed database batch; the final implementation head above passed all required workflows.

The PR subsequently received a documentation-only evidence/tracker update. All four required workflows are being rerun on that final documentation-inclusive PR head before merge.

## Safety and non-claims

- No production database, migration, deployment, SSH/hPanel, cron/worker, source retrieval, candidate staging, approval, import, promotion or publication operation is performed.
- No historical candidate is deleted or merged.
- AI extraction remains subject to existing restrictions; no AI profile is enabled or activated by this work.
- Additional 20 HEC profiles remain disabled.
- GCU rows #348–353 and batches #19–23 remain protected and untouched.
- No dummy production data.
- This is not exactly-once execution, a production concurrency proof, or a Phase 1C closeout. Phase 1C remains open and production readiness remains NOT CERTIFIED.
