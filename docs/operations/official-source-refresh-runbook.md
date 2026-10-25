# Official-source refresh operator runbook

## Purpose

The official-source refresh path is a controlled correction mechanism for allowlisted fields on canonical Campus, Program, and ProgramOffering records. It is not an unattended bulk-import path.

## Safety gates

A proposal must be blocked unless all of the following are true:

- The source is official and its identity has been verified.
- The fetch succeeded and the evidence is confirmed to refer to the intended canonical entity.
- The evidence URL is a valid HTTP(S) URL without embedded credentials.
- A non-empty excerpt, content fingerprint, and timestamp with an explicit timezone are recorded.
- The proposed value materially differs from the reviewed current value.
- Entity identity is unambiguous and official evidence does not conflict.
- The canonical field still equals the expected value captured during review.

Only fields allowlisted by `OfficialSourceCanonicalRefreshService` may be updated. The canonical update and audit event must be committed in the same database transaction. A failed audit write must roll back the canonical change.

## Read-only Hostinger readiness check

1. Open GitHub Actions for this repository and select **Official Source Refresh Readiness (Read-only)**.
2. Select **Run workflow** on `main`. Keep the default absolute project path unless the deployed Laravel project is located elsewhere.
3. Review the run output for:
   - `application_code_status`
   - `table_exists`
   - `migration_applied`
   - `schema_ready`
   - `database_migrations=NOT_RUN`
   - `production_data_mutations=NOT_RUN`
4. Treat `APPLICATION_CODE_DRIFT_OR_UNVERIFIABLE`, `UNVERIFIABLE_NO_GIT_METADATA`, or `schema_ready=false` as a stop condition. Investigate before any migration or data change.
5. This diagnostic is read-only. It does not apply migrations, refresh canonical data, or authorize production writes.

## Before staging acceptance

- Confirm the deployed application revision and full audit-table schema.
- Apply migrations only through the separately approved deployment process.
- Use staging/dummy records to test success, stale expected values, policy blockers, audit metadata, and transaction rollback.
- Confirm rejected proposals leave both the canonical row and audit table unchanged.
- Capture the workflow run URL and test results in the Phase 2 closure report.

## Production boundary

A readiness result is not approval to modify production data. Review the migration impact and a record-level repair plan first. Do not bypass the production-write safety gate.
