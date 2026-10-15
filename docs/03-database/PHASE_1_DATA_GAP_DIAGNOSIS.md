# Phase 1 — Production Data-Gap Diagnosis

| Field | Value |
| --- | --- |
| Document ID | PH1-INST-DIAG-001 |
| Updated | 2026-10-15 |
| Repository | `mattari20/estudent-listing` |
| Phase status | Repository implementation and documentation in progress; production verification blocked |
| Production writes | Not authorized and not performed |

## Objective

Establish a safe, evidence-based baseline before any production data correction or institute import. The baseline must distinguish repository facts, observed production facts, and assumptions.

## Work completed from repository inspection

- Reviewed the existing `institutes:database-health` command and `InstituteDataReadinessService` so the new report can reuse existing canonical population and relationship checks.
- Reviewed `GeographyIntegrityService` and `GeographyGapDetectionService` for geography hierarchy and campus-location gap detection.
- Reviewed the import-batch, import-row, conflict, source, evidence, source-snapshot and source-monitoring table definitions.
- Confirmed the existing report logic can identify institution/campus/program/offering/intake counts and relationships; the new report adds a consolidated view of optional import and source-evidence operational tables.
- Added automated feature tests asserting the report output and that existing canonical records remain present after the report runs.
- Added `PRODUCTION_DATA_GAP_REPORT_RUNBOOK.md` and updated `INSTITUTES_OPERATIONS_INVENTORY.md`.

## Current production evidence

The public directory was previously observed to return a search result for University of Engineering and Technology Lahore while showing zero campuses and zero programs for that result. This is a real observed symptom from a browser screenshot, but it does not distinguish between absent records, inactive records, missing relationships, stale cached results, or other production-data conditions.

No live production database report has been executed as part of this repository audit. The current deployed commit has not been independently confirmed through a Hostinger deployment record during this phase. Consequently, no production table counts or root cause are claimed here.

## Implementation under review

The branch `phase-1-data-gap-report` adds:

- `app/Console/Commands/DataGapReport.php` — read-only `institutes:data-gap-report --json`.
- `tests/Feature/DataGapReportTest.php` — automated coverage for output and no-write behavior.
- `docs/03-database/PRODUCTION_DATA_GAP_REPORT_RUNBOOK.md` — execution and evidence procedure.
- An updated operations inventory covering existing and new diagnostics.

The command reports database connectivity, required schema availability, canonical population, structural relationship gaps, geography distribution/integrity, candidate duplicate institution-name groups, import pipeline counts/statuses, and source/evidence/snapshot/monitoring counts where those optional tables exist. It labels missing optional tables as unavailable rather than claiming that they contain zero rows. It explicitly declares `mutation_performed: false`.

## Acceptance criteria

| Criterion | Status | Evidence / next action |
| --- | --- | --- |
| Reuse existing canonical readiness services | Complete in implementation | `DataGapReport.php` uses `InstituteDataReadinessService` |
| Include geography and relationship gaps | Complete in implementation | Report sections and existing services |
| Include import/evidence operational counts where available | Complete in implementation | Optional-table-aware reporting |
| Add automated feature tests | Added; CI pending | Confirm test workflow passes for the PR |
| Document safe execution and interpretation | Complete in repository change | Data-gap report runbook |
| Update operations inventory | Complete in repository change | Operations inventory Batch 6 |
| Verify reviewed commit deployed to Hostinger | Blocked | Requires Hostinger deployment record |
| Execute report against production DB | Blocked | No direct Hostinger terminal/database access in this session |
| Record real production counts and confirmed root cause | Blocked | Must use actual report output after deployment |
| Approve a correction plan based on live findings | Not started | Do only after production report review |

## Safety and correction policy

This phase is read-only. Do not run migrations, seeders, imports, promotions, publication actions, or SQL writes. Do not alter Hostinger's existing Git integration or enable Remote MySQL.

After the change passes CI and is merged/deployed through the existing approved process, run the report using the runbook. Keep raw output private; commit only a sanitized summary. Then classify gaps, prioritize the correction plan, verify backups, and obtain explicit approval before any production write.

## Phase 1 completion rule

The repository-side deliverables are complete only when the new report's CI tests pass and its documentation is reviewed. The full production-diagnosis phase cannot be marked fully complete until the deployed version and actual production report have been verified. Do not mark blocked production checks as complete.
