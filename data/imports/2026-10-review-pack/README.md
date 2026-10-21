# Institution import and approval review pack — 2026-10

**Purpose:** provide a repeatable, source-led candidate dataset for testing the institution CSV importer and the new smart-table approval/rejection workflow.

**Status:** research/staging only. This package has not been uploaded, approved, promoted, or published. The CSV rows are candidates, not verified canonical records.

## Files

- `institution-candidates-v1.0.csv` — 10 institution identity candidates using the current institution import schema columns: `name, official_name, short_name, slug, institution_type, status`.
- `institution-source-manifest-v1.0.csv` — official-site and HEC source leads, scope notes, and outstanding verification cautions.
- Existing LUMS/UVAS enrichment candidates remain in `docs/09-data-enrichment/staging/`; they are separate campus/program datasets and must not be added to the institution CSV.

## Critical workflow

1. Confirm the deployed application and active schema template match the repository version.
2. Before upload, inspect the existing canonical institution directory and identity-match results. These ten candidates may already exist in the database; the CSV is not a claim that they are all new.
3. Upload only through the authenticated import UI, using the institution template and a clearly labelled source such as `HEC recognised institutions — review pack 2026-10`.
4. Create/capture the exact HEC and institutional source evidence in the application. A URL in this manifest is a research lead, not an application evidence record or a completed verification.
5. Review every row for identity matches, aliases, duplicate slugs, unresolved conflicts, current name, and entity type. Do not bulk-approve institution identity rows; the smart-table bulk-approve path intentionally skips them.
6. For the smart-table UI test, use eligible campus/program enrichment candidates for bulk approval and rejection. Approval is not publication. Use the reason field and confirm the per-row counts.
7. Do not promote campus candidates until parent identity, source evidence, and geography mapping are verified. In particular, the staged campus CSVs intentionally leave geography IDs blank.
8. After testing, export/record the batch summary and audit events. If a row was rejected, re-import a fresh candidate batch for any later retest rather than treating a rejection as reversible.
9. Do not claim that a successful GitHub merge proves Hostinger has deployed the code; verify the deployed revision before UI testing.

## Important interpretation

- This is a real-institution candidate dataset, not synthetic test data.
- It is designed to test CSV header/row validation, duplicate and identity review, and staged approval controls.
- It is **not** designed for one-click bulk publication.
- HEC campus recognition, study centres, constituent institutions, and programme offerings are separate concepts. Do not flatten them into institution rows.
- The manifest deliberately marks sources as research leads because source/evidence capture and row-by-row review remain outstanding.

## Existing enrichment candidates

- LUMS: `docs/09-data-enrichment/staging/lums-campus-candidates-v1.0.csv` and `lums-program-candidates-v1.0.csv`.
- UVAS: `docs/09-data-enrichment/staging/uvas-campus-candidates-v1.0.csv` and `uvas-program-candidates-v1.0.csv`.

Those files are useful for testing the enrichment review queue, but current canonical parent IDs and geography must be checked against the live application at time of use.
