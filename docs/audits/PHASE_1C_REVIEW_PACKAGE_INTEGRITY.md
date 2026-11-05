# Phase 1C — Review-package row-set and evidence integrity

Date: 2026-11-04  
Repository: mattari20/estudent-listing  
Base: e9feccedbb20078c3af24656e6399d55383b6722

## Finding

The previous package manifest validated batch/snapshot identity and CSV checksum but did not bind reuse to current persisted row contents. A database row could change while an internally checksum-valid CSV/manifest pair remained reusable. Referenced evidence records were not reloaded and checked against the batch source and snapshot.

## Remediation implemented

- Bumped the manifest schema to version 3; schema-v2 packages are rebuilt.
- Reloads the batch, source and deterministically ordered rows while holding the per-package filesystem lock.
- Revalidates that the batch remains validated/completed and the snapshot still belongs to the batch source.
- Computes a SHA-256 digest over the complete ordered persisted row set, including IDs, row numbers, statuses, match state, canonical/parent association, payload, normalized payload, validation errors, notes and discovery identity key.
- Reloads evidence IDs referenced by package input and row provenance. Each evidence record must belong to the exact batch source and snapshot; when the batch has a discovery identity key, the evidence must carry the same key.
- Computes a stable evidence-set digest over evidence identity, ownership, reference, content hash, observed/published/expiry timestamps and status.
- Reuse requires both digests to match current database state. Changed rows or evidence invalidate the package and cause deterministic regeneration under the existing lock.
- Package generation does not modify candidate rows, evidence, approvals or canonical records.

## Regression coverage

Added tests for changed persisted rows, changed evidence metadata, cross-snapshot evidence rejection and legacy schema-v2 manifest rebuilding. The independent-process MySQL package writer proof remains wired into the isolated MySQL workflow. Exact-head validation passed on `dfba25b67fa83f9ced5fec6b32298befc2f91d3e`: Test #41873542956, Laravel CI #41873542830 (including formatting checks), Project Controller #41873542898 and isolated MySQL #41873542828; all four runs completed successfully.

## Scope and limitations

Evidence ownership is validated at batch/source/snapshot scope, and by batch discovery identity when available. The current data model does not provide a separate foreign-key association from each evidence row to each individual candidate row; this implementation does not invent one.

The database and local filesystem remain separate transaction domains. The package lock coordinates writers on the same filesystem; it is not a distributed multi-host storage lock.

No production database, filesystem, Hostinger, Artisan, deployment, scheduler, discovery or candidate lifecycle operation was performed.

## Status

IMPLEMENTED and TESTED on the PR branch. The four exact-head checks above passed on `dfba25b67fa83f9ced5fec6b32298befc2f91d3e`. MERGED and PRODUCTION VERIFIED are not claimed.

GCU historical candidate rows #348–353 and batches #19–23 remain protected. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data was created.
