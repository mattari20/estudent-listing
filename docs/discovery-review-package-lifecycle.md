# Protected discovery review-package lifecycle

## Storage and identity

Review packages are private files on Laravel's local storage disk. Each package is scoped to both the immutable import batch and its source snapshot:

- institute-discovery-review-packages/batch-{batch_id}-snapshot-{snapshot_id}/candidates.csv
- institute-discovery-review-packages/batch-{batch_id}-snapshot-{snapshot_id}/manifest.json

Batch-scoped paths prevent two batches derived from the same source snapshot from accidentally sharing or exposing each other's CSV/manifest.

## Manifest and integrity

Manifest schema version 2 includes the batch ID, source/snapshot/evidence provenance, observed time, content hash, row counts, review status, and explicit flags confirming no canonical mutation and no automatic approval. The CSV SHA-256 checksum is recorded as checksums.candidates_csv_sha256.

The package service reuses an existing package only if its batch ID and snapshot ID match and the CSV checksum verifies. If files are absent or invalid, it regenerates the package from the requested batch's stored rows and provenance.

## Authorized retrieval

The operator download route is:

- GET /institutes/operations/discovery-packages/{importBatch}/csv
- GET /institutes/operations/discovery-packages/{importBatch}/manifest

The routes must remain inside the authenticated auth + institute.operator middleware group. Public routes must never expose these files. The controller checks that the manifest belongs to the requested batch and snapshot and that the CSV checksum is valid before returning either file. It sets Cache-Control: private, no-store, max-age=0 and X-Content-Type-Options: nosniff.

For legacy snapshot-scoped files, fallback is permitted only when the legacy manifest explicitly identifies the exact requested batch and snapshot. New packages are always written to batch-scoped paths.

## Outcome and reconciliation rules

- A package with candidates is marked review_required.
- A zero-row extraction is marked no_candidates, not treated as proof that a source has no programmes.
- CSV row count and manifest candidate count must reconcile with the batch rows.
- Manifest checksums are verified before package reuse or download.
- Missing, mismatched, or corrupt files fail closed with no public fallback.
- Package generation/retrieval never approves, promotes, publishes, or mutates canonical records.

## Deployment verification

CI proves code/test behavior only. After deployment, an authorized operator must verify the route through an authenticated session, confirm both CSV and manifest download for a known discovery batch, confirm an unauthenticated request is rejected, and check private/no-store headers. Do not use public diagnostics to disclose private package contents.
