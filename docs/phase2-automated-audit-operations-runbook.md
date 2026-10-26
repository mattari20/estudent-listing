# Phase 2 Automated Audit Operations Runbook

Updated: 2026-10-26

## Automated schedule

The workflow `.github/workflows/offering-gap-preflight.yml` is configured to run daily at 02:00 UTC (07:00 Pakistan Standard Time) and also supports manual dispatch. It reuses the existing `offering-gap-readonly-audit` environment secrets:

- `OFFERING_GAP_DB_HOST`
- `OFFERING_GAP_DB_PORT`
- `OFFERING_GAP_DB_DATABASE`
- `OFFERING_GAP_DB_USERNAME`
- `OFFERING_GAP_DB_PASSWORD`

No new MySQL user or duplicate secret set is required.

## What each run does

1. Checks the revision and required Laravel command/register files.
2. Installs production dependencies without running migrations.
3. Runs `institutes:offering-gap-preflight`.
4. Runs `institutes:offering-gap-reconcile`.
5. Requires both reports to declare zero database mutations and validates the expected 36 register rows.
6. Uploads a short-retention artifact with report JSON and command exit codes.

## What a successful workflow does not prove

A green workflow is not proof that all programme offerings are correct. The report can legitimately remain review-required or blocked. It also does not prove the MySQL account has database-level read-only grants: the account's actual grants must be verified separately.

## First scheduled-run acceptance checklist

- [ ] A run exists for the expected schedule and current main SHA.
- [ ] Both preflight and reconciliation steps exit successfully.
- [ ] Artifact is present and contains both JSON reports and exit-code files.
- [ ] Both reports show `database_mutations=0`, `mutation_performed=false`, and no production-write authorization.
- [ ] Both reports contain the expected 36 records.
- [ ] Report status and row-level blockers have been reviewed; no blocker is treated as fixed just because the workflow is green.
- [ ] No migrations or production data changes occurred.
- [ ] The MySQL user's actual grants and remaining elevated-credential risk are documented.

## Current unresolved work

Issue #585 remains the governing record for GCU candidate dispositions, reconciliation of historical promotion-failure events, and official-source verification of all 36 offering gaps. Do not close Phase 2 until its acceptance criteria are supported by fresh live evidence.

## Run links

- Workflow: https://github.com/mattari20/estudent-listing/actions/workflows/offering-gap-preflight.yml
- GCU/offering-gap remediation: https://github.com/mattari20/estudent-listing/issues/585
- Workflow implementation: https://github.com/mattari20/estudent-listing/issues/626
