# Production Read-Only Data-Gap Report Runbook

| Field | Value |
| --- | --- |
| Document ID | RUN-DB-DATA-GAP-001 |
| Status | Proposed for review; execute only after the containing release is deployed |
| Scope | Institutes Laravel application only |
| Application path | Hostinger `public_html/institutes` |
| Data changes | Prohibited |

## Purpose

Collect a consistent, read-only picture of directory population and data-quality gaps before planning any production correction or import. The report combines existing canonical readiness services with counts for geography, import workflow, source evidence and potential duplicate institution names.

It does not certify that all institutions in Pakistan have been collected, verify external sources, determine that similarly named records are true duplicates, or authorize any production change.

## Preconditions

- Confirm the reviewed commit is deployed using the existing Hostinger Git integration. Do not disconnect, recreate, or reconfigure it.
- Open Hostinger terminal at `public_html/institutes`, the Laravel application root.
- Do not reveal or paste `.env`, database passwords, application keys, or full connection strings.
- Keep Remote MySQL disabled.
- Do not run migrations, seeders, imports, promotion, or repair commands as part of this runbook.

If the deployed release is not confirmed or the command is not listed, stop. Do not attempt to install code manually on production.

## Run the report

First verify command availability:

```sh
php artisan list --raw | grep '^institutes:data-gap-report$'
```

Then run the report:

```sh
php artisan institutes:data-gap-report --json
```

Save the output privately for review. The command must return `mutation_performed: false`. It reports the configured database environment and operational counts; redact sensitive operational details before sharing.

## Report sections

- `database`: connection check and driver.
- `schema`: required canonical tables present or missing.
- `canonical_population`: total and active/inactive institutions, campuses, programs and offerings, plus admission intakes.
- `canonical_relationship_gaps`: active records whose required parent relationship is missing or inactive.
- `geography`: counts by type/status, integrity findings, and active campuses with missing/inactive geography.
- `duplicate_candidates`: groups of institution names that match after trimming and case-folding; these require human verification.
- `import_pipeline`: counts/statuses for available import batches, rows, conflicts, enrichment rows and promotion decisions.
- `source_evidence`: counts/statuses for available sources, evidence, snapshots, monitoring policies and retrieval runs.
- `limitations`: important boundaries on what the report can establish.

A missing optional operations table is reported as unavailable and raises a warning; do not treat missing reporting data as zero records. A critical status or missing required canonical table means stop and investigate.

## Evidence and documentation

Record:
1. exact deployed commit and how it was verified;
2. report timestamp, exit code and overall status;
3. counts and findings by section;
4. warnings, missing optional tables and unresolved questions;
5. the proposed correction order and why;
6. reviewer and link to the follow-up issue/PR.

Do not commit live production record-level details, personal data, secrets, connection strings or unredacted operational output to the public repository. Put only a sanitized summary and a secure evidence reference in documentation.

## Explicitly prohibited

- `php artisan migrate`, rollback, `migrate:fresh`, or schema-changing commands;
- any geography seed command;
- SQL `INSERT`, `UPDATE`, `DELETE`, `TRUNCATE`, or schema changes;
- importing, promoting, activating or publishing records;
- changes to Hostinger deployment settings or database exposure;
- claiming production verification when no report was actually run.

Any write requires a separate reviewed plan, a verified backup, a preflight report, and explicit owner approval for the specific change.


## Historical import batch review

For row-by-row reconciliation after the latest private report is retrieved, follow [`HISTORICAL_IMPORT_BATCH_REVIEW.md`](./HISTORICAL_IMPORT_BATCH_REVIEW.md). Keep the record-level matrix private, record missing inputs as unknown rather than zero, and preserve the read-only boundary until a record-specific change plan and explicit owner approval exist.


## Freshness and deployment identity gate

A merged commit or successful GitHub workflow is not proof that the same release is running on Hostinger. Record the deployed release/commit from the existing Hostinger deployment record before interpreting report values. Record the report's `checked_at` timestamp and the workflow run timestamp. If either is unavailable, label deployment identity or freshness `UNKNOWN` and stop before any remediation proposal.

When a protected diagnostics report is available, confirm the aggregate report, record-level audit, and geography preflight are present; each must declare `mutation_performed: false`, and geography preflight must declare `seed_command_executed: false`. Keep raw output in the private report repository only. Publish only a sanitized summary without internal IDs, institution-level audit rows, credentials, or raw JSON.

## Required evidence reconciliation before remediation

Before preparing a record-specific change set, reconcile the aggregate report with the record-level audit and geography preflight from the same retrieval run. Verify that the combined response and every section explicitly report `mutation_performed: false`; geography preflight must also report `seed_command_executed: false`. Record report time, workflow run time, and deployed release identity. Do not combine sections from different timestamps or use the historical counts in the correction plan as current data. Any mismatch, missing marker, non-zero diagnostic exit code, or unknown deployment identity blocks remediation planning.

## Report acceptance criteria

Accept a retrieval for analysis only if all are true: (1) the workflow run completed successfully; (2) the production release identity is independently confirmed; (3) the report timestamp is present and meets the freshness policy; (4) aggregate report, record-level audit, and geography preflight came from the same response/run; (5) each section's expected `check` value matches; (6) every section declares `mutation_performed: false`; (7) geography preflight declares `seed_command_executed: false`; and (8) command exit codes are reviewed. If any criterion fails, preserve the report privately for troubleshooting, mark the audit `BLOCKED`, and do not derive a correction list from it.