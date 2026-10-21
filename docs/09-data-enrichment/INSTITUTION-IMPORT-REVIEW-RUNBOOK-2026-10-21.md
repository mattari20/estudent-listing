# Institution import review runbook — 2026-10-21

## Current verified state

- The canonical production identity export and recorded-alias export ran successfully in read-only mode.
- The current snapshot contained 10 canonical institutions and 10 recorded institution-name aliases.
- The candidate pack was reduced to one importer CSV with 9 rows.
- COMSATS University Islamabad was held out because the production snapshot already contains a COMSATS Lahore Campus institution record; resolve the institution/campus hierarchy before importing that candidate.
- The clean CSV schema and staging flow have CI coverage. Bulk approval/rejection guardrail tests are also in CI.
- The production public-directory smoke monitor, Laravel CI, frontend build, and read-only diagnostics all passed for the latest merged bulk-review safeguards commit.

## The only importer CSV

Use:
`data/imports/2026-10-review-pack/institution-candidates-clean-v1.0.csv`

Do not upload the original ten-row research candidate file, the source manifest, or this report as importer input.

## Controlled review sequence

1. Sign in to the Institutes operations UI with an authorized operator account.
2. Upload the clean CSV using the institution import workflow and the configured schema profile.
3. Verify that the returned batch contains 9 rows and inspect validation errors.
4. Review identity matches and open conflicts. Do not use bulk approval for institution identity decisions; resolve these through the dedicated identity workflow.
5. Capture and verify official source evidence for every row. The source manifest is a research lead list, not proof that evidence has been captured in the application.
6. Review each candidate's official name, short name, slug, and institution type/status. The CSV intentionally leaves `institution_type` and `status` blank to avoid guessing.
7. Bulk reject only rows whose rejection reason is clear and recorded. Open conflicts must remain held for review.
8. Approve only after evidence and identity review. Approval is not promotion.
9. Check promotion-readiness gates and promote only explicitly approved, evidence-backed rows.
10. Confirm batch counts, per-row audit history, and public listing outcomes after the operator-approved promotion.

## Safety boundaries

- CI tests use an isolated test database and synthetic records.
- The production identity export is SELECT-only.
- Do not automatically approve, promote, or publish candidates based only on exact-match deduplication.
- No live import or promotion is included in this runbook or in the completed CI checks.
- If counts, conflict status, source evidence, or parent identity do not agree, stop and investigate instead of forcing a decision.

## Evidence of current CI

Latest workflow runs for the merged bulk-review safeguards commit are linked from the repository Actions tab. Verify the run SHA before treating any future run as evidence for a later commit:
https://github.com/mattari20/estudent-listing/actions
