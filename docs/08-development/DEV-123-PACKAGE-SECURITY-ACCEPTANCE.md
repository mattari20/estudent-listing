# DEV-123 — Package security acceptance

This batch adds regression coverage for the package retention safety boundary.

- An aged package is listed in dry-run mode and remains intact.
- The same package is deleted only when `--apply` is explicitly provided.
- Incomplete package directories and directory names outside the allowed positive snapshot ID pattern are ignored.
- Tests use fake local storage only.

The route-level access policy still requires live acceptance: unauthenticated requests must be denied by the existing `auth` and `institute.operator` middleware; valid CSV and manifest downloads must return private, no-store responses; invalid source types, invalid file names, missing provenance, and missing package files must return 404.

No production files are deleted by this test batch.
