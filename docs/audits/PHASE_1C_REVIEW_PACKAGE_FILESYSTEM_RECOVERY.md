# Phase 1C-18 — Review-package filesystem recovery and orphan reconciliation

Date: 2026-11-04

Repository: \`mattari20/estudent-listing\`

Base \`main\`: \`25024ebfb4e31563590e46819a3a4dba50c01511\`

Status: implementation prepared; exact-head validation is pending.

## 1. Phase 1C-17 evidence reconciliation

PR #1002 is merged. Its final implementation head was
\`c182311b749e7c094c7d2c13b00812c220eea5f0\`; merge commit and verified main
SHA are \`25024ebfb4e31563590e46819a3a4dba50c01511\`.

The final exact-head runs are:

| Workflow | Run | Job | Result |
|---|---:|---:|---|
| Test | #41759860084 | 126344720822 | PASS — 843 tests, 3,755 assertions |
| Laravel CI | #41759860096 | 126344720492 | PASS — 843 tests, 3,755 assertions; Pint 46 files |
| Institutes Directory Project Controller | #41759860104 | 126344786882 | Workflow job PASS |
| Isolated MySQL migration/recovery | #41759860039 | 126344720417 | PASS |

The actual MySQL job log confirms:
- isolated MySQL 8.4 schema migration, exact schema/ledger verification and a
  successful idempotent second migration run;
- independent-process execution-lease exclusion: one process acquired the
  lease, one was rejected, and exactly one run was recorded;
- source snapshot/retrieval, discovery outcome idempotency, outcome-to-batch
  consistency, candidate failure recovery, source event/reverification,
  alert/audit and policy checkpoint recovery steps passed;
- the candidate-batch two-process test returned batch #16 from both workers,
  with one batch, two candidate rows, one evidence row and two assertion links.

The Project Controller job itself completed successfully. Its read-only
inventory report was not a full project readiness pass: it reported 2/5
inventory tasks passed and marked the Hostinger read-only diagnostics task
BLOCKED. That separate runtime limitation remains unresolved; this workflow
result must not be represented as production readiness.

## 2. Existing filesystem failure boundary

Before this phase, candidate/evidence/batch persistence was transactional in
MySQL, but the private review package was a pair of files on Laravel's local
private disk. The previous service wrote the final CSV and manifest paths
directly and reused a pair after checking only a subset of manifest identity
fields and the CSV checksum.

This left these cases insufficiently controlled:
- a process could stop after writing only one artifact;
- a failed manifest write or DB rollback could leave orphan files;
- a manifest could have the wrong package type or incomplete identity;
- there was no filesystem writer/cleanup lock;
- cleanup could not safely distinguish an orphan from a valid package;
- the old prune command only recognized legacy snapshot-scoped paths and did
  not verify database ownership, age of every artifact, unexpected files or
  symlink/path boundaries.

No database outbox or queue worker is introduced. Existing package generation
is synchronous and called from the discovery transaction path. The codebase
does not establish a production queue worker, so adding an asynchronous
outbox consumer would create an unsupported operational dependency. The
recovery model remains deterministic replay plus bounded filesystem
reconciliation.

## 3. Recovery design

The implementation uses the existing local private disk and batch/snapshot
scoped deterministic path:

- \`institute-discovery-review-packages/batch-{batch_id}-snapshot-{snapshot_id}/candidates.csv\`
- \`institute-discovery-review-packages/batch-{batch_id}-snapshot-{snapshot_id}/manifest.json\`

A lock file under the dedicated root's \`.locks\` directory serializes
generation and reconciliation for the same batch/snapshot identity. The lock
is acquired with an exclusive OS file lock; lock-file symlinks and inode
replacement are rejected.

Each artifact is written to a unique temporary file in the same package
directory and installed with an atomic rename. The manifest is installed last
and acts as the pair's completion marker. A reader/replay never treats a
missing, incomplete or checksum-invalid pair as ready.

Before reuse, the service requires:
- a completed, validated discovery batch;
- a snapshot belonging to the batch's source;
- package type and schema version;
- exact batch ID, batch key, entity type, source ID/type and snapshot ID/hash;
- the expected CSV filename and 64-character SHA-256 checksum;
- a checksum match, candidate count matching persisted batch rows and a
  status consistent with an empty/non-empty batch;
- explicit false values for canonical mutation and automatic approval.

Invalid known artifacts are regenerated under the same lock. Unexpected files
or filesystem objects are not overwritten. Generation does not alter candidate
rows, approve candidates or mutate canonical records.

## 4. Database/filesystem rollback semantics

MySQL remains authoritative for candidate and batch state. Filesystem writes
cannot participate in the MySQL transaction. A rollback after package writes
can leave a directory, but it cannot create a valid package for a missing
database batch. Reconciliation treats a package directory as an orphan only
when its batch row is absent and the artifacts exceed the grace period.

If a batch row exists, including an incomplete or otherwise ambiguous batch,
its package directory is preserved. Package creation itself refuses incomplete
batches. This is deliberately conservative: no candidate/database record is
deleted, merged or rewritten.

## 5. Cleanup safety contract

Command:

\`php artisan institutes:prune-discovery-review-packages\`

- Dry-run is the default.
- Deletion requires explicit \`--apply\`.
- Minimum age defaults to 168 hours and cannot be configured below 24 hours.
- Only exact batch/snapshot package directories beneath the dedicated local
  private root are considered.
- Generation and cleanup share the same per-package lock.
- Every entry must be a regular file with a known package or temporary-file
  name. Unknown files, symlinks, path escapes, recent artifacts, existing
  database batches and inspection failures are skipped or fail closed.
- The report includes exact package paths, candidate artifact paths and
  reasons.
- Apply removes only old orphan files and their now-empty package directory;
  it does not remove valid packages or touch database records.

The command is never run against production in this phase.

## 6. Regression coverage added

The recovery feature test covers interrupted CSV-only creation, missing CSV,
missing manifest, invalid checksum, invalid manifest identity, incomplete
batch refusal, orphan dry-run reporting, stale temporary artifacts, valid
package preservation, recent orphan preservation, explicit apply, unexpected
files, symlink protection, source/snapshot mismatch and replay without
candidate-row changes.

An isolated-MySQL multiprocess proof starts two independent PHP processes
against one completed batch and verifies that they converge on the same
verified CSV/manifest pair, with one creator and one reuser. The MySQL
workflow is extended to run the focused package tests and concurrency proof.
The normal Test workflow runs the full Laravel suite; Laravel CI formats and
checks the changed PHP files.

Exact final PR-head results, test totals and actual MySQL log findings will be
recorded here after all four required workflows pass.

## 7. Safety

Repository and isolated CI only. No production DB access, migrations,
deployment, SSH/hPanel, cron/worker changes, source retrieval, candidate
staging, approval, import, promotion or publication.

GCU rows #348–353 and batches #19–23 remain protected. AI extraction and the
additional 20 HEC profiles remain disabled. No dummy production data and no
historical candidate deletion or merging.

## 8. Remaining limitations and next work

- MySQL and local filesystem are not a single atomic transaction. A stale
  orphan can remain until the operator reviews and explicitly applies the
  dry-run cleanup.
- OS file locking coordinates processes sharing the same local filesystem;
  it is not a distributed lock for multiple hosts with independent local
  disks. No multi-host storage topology is assumed.
- A valid DB batch is always preserved by cleanup, even if its package is
  corrupt; a later deterministic package replay is required to repair it.
- Project Controller's separate Hostinger read-only diagnostics task remains
  blocked; this phase does not certify production runtime or readiness.

Next Phase 1C engineering task after this delivery: **institution identity
conflict/resolution persistence under replay and concurrent attempts**,
including durable conflict uniqueness, resolution history, and
independent-process MySQL coverage. Phase 1C remains open.
