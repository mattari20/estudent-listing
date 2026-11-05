# Phase 1C — Retrieval snapshot/run finalization recovery

Date: 2026-11-04  
Repository: mattari20/estudent-listing  
Base: e9feccedbb20078c3af24656e6399d55383b6722

## Failure boundary

Successful retrieval previously persisted the source snapshot and then updated the retrieval-run row in a separate write. If the final run update failed after the snapshot insert, the run remained started while the snapshot already existed. The existing capture key is derived from the retrieval-run ID, so a retry can reuse the persisted snapshot, but the specific post-snapshot/pre-finalization failure path lacked direct regression proof.

## Remediation implemented

- Extracted the retrieval-run completion write into SourceRetrievalRunFinalizer, preserving the existing field values and authoritative snapshot timestamp.
- The finalizer checks that the update was persisted and returns the refreshed retrieval-run model.
- Added failure injection at the finalization boundary after a real snapshot has been persisted.
- The regression test verifies the run remains started with no snapshot FK after the injected exception, while exactly one snapshot row exists.
- After the stale-in-progress interval, retrying with the same idempotency key retrieves the same payload, reuses the existing capture-key snapshot, completes the original run row, and retains the original snapshot captured_at as completed_at.
- The test asserts one retrieval-run row and one snapshot row after recovery.

## Recovery semantics and limits

Snapshot persistence and retrieval-run completion remain separate database writes; this does not claim a single atomic transaction across both writes. Recovery is idempotent for the same run/capture key and same content. If the remote body changes after partial persistence, the existing capture-key content mismatch fails closed rather than silently replacing the snapshot.

Existing source-snapshot ordering and independent-process MySQL proofs remain part of the isolated MySQL workflow. The new feature test is included in the existing SourceRetrievalTest suite.

## Safety and status

Repository and isolated CI only. No production DB, Hostinger, Artisan, migration, deployment, cron/worker, source retrieval, discovery or candidate lifecycle operation was performed.

IMPLEMENTED and validated on reconciliation commit `dee687a8388c90e79dff37f9af1b685a6beaa951`: Test #41939778703, Laravel CI/Pint #41939778718, Project Controller #41939778858 and isolated MySQL #41939778734 all passed. The focused MySQL SourceSnapshot/SourceRetrieval suites passed 31 tests / 116 assertions. A tracker/audit-only commit follows; fresh checks on that final documentation-inclusive head are required. MERGED and PRODUCTION VERIFIED are not claimed.

GCU rows #348–353 and batches #19–23 remain protected. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data.
