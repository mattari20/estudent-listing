# Hostinger database-grants verification feasibility review

Reviewed: 2026-10-26
Related PR: https://github.com/mattari20/estudent-listing/pull/757

## Decision

Do not merge or execute PR #757 as-is. It expects `OFFERING_GAP_DB_HOST`, `OFFERING_GAP_DB_PORT`, `OFFERING_GAP_DB_DATABASE`, `OFFERING_GAP_DB_USERNAME`, and `OFFERING_GAP_DB_PASSWORD` secrets for a separate protected database connection. The project owner has reported Hostinger support's answer that this shared-hosting database supports one MySQL user in hPanel and does not support adding a second user to the existing database through that interface. The required secrets and their grants have not been independently verified.

The branch is also based on an older revision than the current report-artifact workflow. It should not be merged just because its prior CI checks passed.

## Safe next steps

1. Keep the current Hostinger report workflow's SSH fingerprint verification enabled.
2. Ask Hostinger for a supported way to inspect effective grants for the existing database account or to provide a genuinely least-privilege audit identity on this hosting plan.
3. If only the application account is available, do not claim it is SELECT-only; mark grants verification as blocked and keep audit commands strictly read-only by contract.
4. If a supported least-privilege credential becomes available, implement it on a fresh branch from current `main`, keep secrets out of logs/artifacts, and verify the exact grants before running the audit.
5. Keep the report-level review and zero-mutation checks as independent controls.

## Scope and guardrails

This note does not close PR #757 automatically, create secrets, change grants, run a workflow, or authorize production writes. No migrations, approvals, promotions, or deletions are authorized.