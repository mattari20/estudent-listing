# eStudent Institutes Directory — Master Tracker

Last reviewed: 2026-10-27
Repository: https://github.com/mattari20/estudent-listing
Production safety: diagnostic/read-only work is preferred. No destructive data operation or production migration is authorized by this tracker.

## Operating rules
- Work in small, reviewable batches; link every change to a PR, CI run, or diagnostic report.
- Do not call a task complete without evidence from CI or the deployed system.
- Treat imported/test data as unverified until source evidence and identity checks pass.
- For live database changes, inspect schema first, check migration state, require an explicit confirmation gate, and stop on partial/inconsistent state.
- Report blockers plainly; never describe a workflow as deployed if it was only a dry run.

## Ten major delivery steps

| # | Workstream | Status | Exit evidence / acceptance gate |
|---|---|---|---|
| 1 | Platform foundation and Phase 2 closure | In progress | Listing/search/pagination/detail routes pass live checks; import, approval, promotion and rollback tests pass; Phase 2 gates below are documented |
| 2 | Verified master education dataset | Planned | Institution, campus, program and offering rows have source evidence, normalized identity, duplicate/dependency checks and coverage report |
| 3 | Zero-additional-cost discovery agent | Planned | Scheduled discovery creates review packages only; no automatic promotion of unverified candidates |
| 4 | Source refresh and change history | In progress | Official source registry, fingerprints, before/after observations, safe refresh and failure reporting verified |
| 5 | Geography and maps | Planned | Normalized location fields, coordinate provenance, nearby search and map UX verified |
| 6 | Admissions and student decision data | Planned | Eligibility, admissions dates, fees, scholarships and recognition data include source and last-verified date |
| 7 | Ratings, reviews and community | Planned | User ratings/comments have moderation, abuse controls and clear separation from official rankings |
| 8 | Advanced search and recommendations | Planned | Filters/comparisons/recommendations use verified data and disclose missing fields |
| 9 | SEO, performance and growth | Planned | Canonicals, structured data, sitemap, indexing and performance checks have evidence |
| 10 | Security, administration and operations | Planned | Least privilege, audit trail, monitoring, recovery procedure, performance and handover docs verified |

## Phase 2 closure gates

| Gate | Required evidence | Status |
|---|---|---|
| 1. Approved code revision deployed | Hostinger revision output matches approved application revision | Blocked / re-verify |
| 2. Official-source schema verified | Read-only table, columns, indexes and migration-row report | Blocked / re-verify |
| 3. Migration failures are surfaced | Workflow exits non-zero on SQL/PHP error; CI and workflow logs prove it | In progress — PR #826 |
| 4. Fresh live integrity baseline | Timestamped counts and exception report from read-only diagnostic | Pending |
| 5. Row-level verification audit | IDs, official source evidence, duplicate/dependency impact and proposed action | Pending |
| 6. Duplicate/geography/relationship checks | Repeatable report with exceptions listed | Pending |
| 7. Import and recovery tests | Import validation, approval, promotion, rollback and failure-path test results | Pending |
| 8. Live route regression | Listing, search, pagination and valid detail URL checks | Pending |
| 9. Security and evidence audit | Roles, source evidence, approval history and secrets handling checked | Pending |
| 10. Closure report and handoff | All gates pass or remaining exceptions explicitly accepted and assigned | Pending |

## Batch report format
At the end of each batch record:
1. Scope and PRs/issues touched.
2. Changes made (and changes deliberately not made).
3. CI/test results with links.
4. Deployment/schema/data evidence, distinguishing dry-run from actual execution.
5. Remaining blockers and risk.
6. Next smallest safe batch.
7. Completion estimate only when measurable against the gates above.

## Immediate next actions
1. Correct and validate the PHP exception handler in PR #826 before considering merge.
2. Re-run CI and review the migration workflow diff.
3. Use the migration workflow in read-only mode to inspect the live schema and migration record; do not rerun a potentially partial migration blindly.
4. Capture a fresh read-only integrity baseline.
5. Complete the row-by-row unverified-record audit and prepare a deletion/replacement plan; do not delete until the exact IDs, evidence and dependencies have been reviewed.
