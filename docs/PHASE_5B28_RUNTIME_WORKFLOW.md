# Phase 5B.28 — Runtime-only diagnostic delivery operations

## Approved source

The workflow checks out the immutable approved main revision `3b0b1bc40206bb665f34064f265c2975f5ea19ef` and retrieves only `scripts/ops/phase-5b18-runtime-diagnostic.php`. The expected source blob is `2a354e97ef3a60ce446a5de34149d7783dc6f957`. The PHP source is not modified by this phase.

## One-shot execution boundary

`.github/workflows/hostinger-artisan-runtime-only.yml` is manual-only. Development and PR validation must never dispatch it. The production path is fixed to the Institutes application and PHP 8.5 executable. It verifies runner and remote SHA-256, strict SSH host fingerprint, remote file permissions and PHP lint before one PHP diagnostic invocation. No retry path exists.

The diagnostic writes only the fixed private report `storage/app/private/diagnostics/phase-5b18-artisan-runtime.json`. Existing reports are never overwritten. Report size is capped at 256 KiB; report bytes, SHA-256 and JSON are checked before artifact upload.

## Per-run temporary directory isolation

Each invocation receives the unique `REMOTE_DIR` output created by that same workflow run. Cleanup uses that output directly; it does not read or write a shared `current-run-path` marker. The runner and remote shell validate the fixed temporary-root prefix, canonical path and restrictive directory mode before removing the transferred script or lint output. No application directory or application Git metadata is removed or changed.

The private JSON report is outside the temporary directory and is never a cleanup target. Diagnostic stderr and report metadata are downloaded and retained in the workflow artifact before remote evidence cleanup. If evidence upload fails, remote stderr and metadata are retained; cleanup removes only the temporary PHP script and lint output and leaves the directory for manual recovery. Cleanup errors fail the workflow without deleting the report.

## Failure semantics

A failed upload, checksum check, PHP lint, missing report, invalid JSON, report-size violation, failed download, digest mismatch or artifact upload prevents a successful workflow result. The diagnostic invocation occurs only after transfer verification and lint; no failed gate can cause a second invocation. A nonzero diagnostic exit may still allow the report and stderr to be preserved, but the final workflow result remains failed.

## Explicit exclusions

This workflow does not run database inspection, migrations, route/API diagnostics, deployment, cache clearing, GCU qualification, HEC requests, or institution import/approval/promotion/publication. The only PHP diagnostic program executed is the approved standalone Phase 5B.18 script.

## Validation

The Test workflow runs `scripts/ci/test-hostinger-runtime-only-delivery.rb`. That contract test checks workflow scope, the one-shot gate ordering, report validation, no shared marker, canonical per-run cleanup and a two-run isolation fixture. Laravel CI and Project Controller are separately required exact-head PR checks. A PR does not authorize production workflow dispatch.
