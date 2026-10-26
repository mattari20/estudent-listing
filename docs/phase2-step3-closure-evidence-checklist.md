## Closure is evidence-based
Step 3 remains open until the current live reports are reviewed and every blocking finding is resolved or explicitly dispositioned. Passing CI, merging documentation, or loading the website is not sufficient evidence of data remediation.

## Required evidence package
1. Exact deployed commit and deployment verification result.
2. Fresh read-only GCU candidate audit artifact with all 112 baseline candidates accounted for, plus any refreshed batch separately identified.
3. Failure-event reconciliation mapping all 76 baseline events to stable row IDs or explicitly unresolved identity cases.
4. Fresh 36-row offering-gap report with every row assigned a documented disposition and official evidence links.
5. Duplicate/identity collision review and proposed repair plan with before/after values for every proposed canonical change.
6. Automated test results for refresh idempotency, extraction boundaries, event grouping, and gap-report row counts.
7. Explicit confirmation that no production data writes/migrations occurred during the audit-only stage.

## Closure decision
- `BLOCKED`: any required live artifact is missing, stale, invalid, or incomplete.
- `REVIEW_REQUIRED`: artifacts exist but one or more rows lack evidence/reviewer disposition.
- `READY_FOR_AUTHORIZED_REPAIR`: row-level plan and duplicate checks are complete, but no write is implied.
- `CLOSED`: all acceptance criteria in issue #585 are met and exact run/artifact/commit links are recorded.

## Non-negotiable guardrails
Do not mark closed based on workflow success alone. Do not promote, approve, delete, migrate, or rewrite canonical production data without a separately reviewed row-level plan and explicit authorization.