# LUMS and UVAS staging candidates — review only

**Status:** Draft candidate payloads for validation; not approved for import or production promotion.

These CSVs are deliberately kept separate by institution and entity type. They use the existing enrichment schema v1.0. The names are based on the source review in `../PHASE-1-LUMS-UVAS-SOURCE-REVIEW-2026-10-20.md` and are candidates, not a claim that the list is complete.

## Files

- `lums-campus-candidates-v1.0.csv` — one proposed LUMS main campus.
- `lums-program-candidates-v1.0.csv` — five programme-name candidates.
- `uvas-campus-candidates-v1.0.csv` — five campus-name candidates.
- `uvas-program-candidates-v1.0.csv` — eleven programme-name candidates.

## Mandatory gates before import

1. Confirm each canonical institution is still active and that IDs remain LUMS `8` and UVAS `5`; do not rely on these IDs without checking the current live database.
2. Use the authenticated Operations UI to create/verify source records and capture exact URL, relevant excerpt, snapshot and content hash where supported. A URL in a research document alone is not an application evidence record.
3. Verify the live v1.0 template from the running application before upload; do not assume a code checkout and deployed schema are identical.
4. Run duplicate detection and row validation. Resolve any candidate flagged duplicate or invalid before review.
5. Resolve geography using the current active geography hierarchy and official campus evidence. All campus rows currently leave `geography_id` blank intentionally; do not promote them until the required geography mapping is verified. Coordinates and postal codes are not invented.
6. Verify each programme name and current availability against official pages; establish campus-specific offerings only where an official source explicitly supports them. In particular, do not attach programmes to UVAS Layyah based on this list.
7. Review source, provenance, source-readiness, import-readiness and promotion-readiness screens. Require explicit approval before promotion.
8. After any authorized promotion, run read-only diagnostics and re-test both public profiles. Confirm correct parent relationships, geography, active counts, and audit events.

## Important behavior of the current importer

The enrichment importer creates an active source record and a validated batch from the CSV, but that is not the same as capturing row-level evidence or approving/promoting rows. Keep this package in review-only status until the gates above are satisfied. Do not upload or promote it as a shortcut to remove the empty-state messages.
