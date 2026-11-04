# Phase 1C-26 — Production Workflow Isolation

**Repository:** `mattari20/estudent-listing`  
**Base:** `main`  
**Implementation branch:** `phase-1c26-production-workflow-isolation`  
**Scope:** Repository workflow and regression-test changes only.

## Security disposition

Ordinary pull requests and pushes to `main` must not start production SSH, production database, Artisan, deployment, migration, discovery, or private diagnostics operations. Production operations are now manual-dispatch only, bound to the exact current `main` SHA, require a reason and operation-specific confirmation, declare a protected GitHub environment, and share the `estudent-production-access` concurrency group with `cancel-in-progress: false` and `queue: max`, so pending runs queue instead of replacing one another (up to GitHub's 100-pending-run limit).

The dispatch guard is `scripts/ci/validate-production-dispatch.sh`. It rejects non-`workflow_dispatch` events, refs other than `refs/heads/main`, malformed or mismatched SHAs, a target that is no longer the current `origin/main` SHA, missing/short reasons, and incorrect confirmation phrases.

## Trigger matrix

| Workflow group | Before | Implemented trigger / protection |
|---|---|---|
| Hostinger read-only diagnostics and integrity audit | Main push and/or schedule | Manual dispatch only; exact-SHA guard; `production-readonly-diagnostics` |
| Step 3 candidate, record-level and reset inventory audits | Main push and manual | Manual dispatch only; exact-SHA guard; `production-readonly-diagnostics` |
| Catalog audit and UVAS public-directory verification | Pull request and ops-branch push | Manual dispatch only; exact-SHA guard; `production-readonly-diagnostics` |
| Offering-gap live DB preflight | Main push and daily schedule | Manual dispatch only; exact-SHA guard; existing `offering-gap-readonly-audit` environment |
| Official-source readiness and scheduled discovery | `workflow_run` handoffs and/or hourly schedule | Manual dispatch only; readiness uses `production-readonly-diagnostics`; discovery uses `production-operations` |
| Controlled migrations, deployment, geography seed and GCU qualification | Already manual, with operation-specific confirmation | Exact-SHA/reason/confirmation guard; `production-operations` |
| Private diagnostics retrieval and protected diagnostics verification | Manual and/or daily schedule | Manual dispatch only; exact-SHA guard; `production-readonly-diagnostics` |
| Public browser runtime diagnostics | Pull request and main push | Manual dispatch only |
| Public site monitor | Main push, schedule and manual | Schedule and manual retained; main-push trigger removed; no production credentials |
| Official-source page verification | Main push, weekly schedule and manual | Retained as repository-side external-source verification; no Hostinger SSH or production DB credentials |
| Ordinary CI, isolated MySQL, controller/orchestrator, frontend build, evidence validation | PR/push CI | Repository-only; no production SSH action or production credential reference |

## Workflow inventory

The production-facing inventory reviewed includes:

- `hostinger-readonly-diagnostics.yml`
- `automated-live-integrity-audit.yml`
- `offering-gap-preflight.yml`
- `step3-record-level-remediation-audit.yml`
- `step3-candidate-audit.yml`
- `readonly-reset-inventory.yml`
- `audit-next-institution-catalog.yml`
- `verify-uvas-public-directory.yml`
- `scheduled-institute-discovery.yml`
- `official-source-refresh-readiness.yml`
- `controlled-hostinger-code-deploy.yml`
- `controlled-discovery-outcome-migration.yml`
- `controlled-import-batch-duplicate-rows-migration.yml`
- `controlled-institution-field-approvals-migration.yml`
- `controlled-official-source-refresh-migration.yml`
- `controlled-pakistan-geography-seed.yml`
- `diagnose-hostinger-artisan-path.yml`
- `discovery-runtime-readiness.yml`
- `hostinger-artisan-runtime-only.yml`
- `hostinger-gcu-single-source-qualification.yml`
- `hostinger-readonly-offering-gap-audit.yml`
- `institutes-diagnostics-verify.yml`
- `institutes-private-diagnostics-report.yml`
- `secure-institutes-admin-recovery.yml`

The automatic production entry paths listed in the earlier Phase 1C-25 audit were removed. No production-facing workflow retains a `workflow_run` trigger. No PR-triggered production SSH workflow remains. The public monitor's scheduled HTTPS monitoring remains independent of repository pushes.

## Owner configuration required

Repository YAML cannot configure GitHub environment protection. Before merging or using these workflows, the repository owner must:

1. Create/configure environment `production-readonly-diagnostics` with required reviewers, prevent self-review, and restrict deployment branches to `main`.
2. Create/configure environment `production-operations` with required reviewers (preferably a distinct reviewer set), prevent self-review, and restrict deployment branches to `main`.
3. Verify existing environment `offering-gap-readonly-audit` has the same required-reviewer and main-only restrictions.
4. Add the required credentials as environment secrets, using least privilege:
   - Read-only SSH workflows: `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_USER`, `HOSTINGER_SSH_PRIVATE_KEY`, `HOSTINGER_SSH_PORT`, and where used `HOSTINGER_SSH_FINGERPRINT`.
   - Direct read-only DB workflow: `OFFERING_GAP_DB_HOST`, `OFFERING_GAP_DB_PORT`, `OFFERING_GAP_DB_DATABASE`, `OFFERING_GAP_DB_USERNAME`, `OFFERING_GAP_DB_PASSWORD`.
   - Private diagnostics retrieval: `INSTITUTES_DIAGNOSTICS_TOKEN` and `INSTITUTES_PRIVATE_REPORTS_TOKEN`.
5. After confirming every reference is covered by the environment secrets, remove obsolete repository-level copies of production credentials. Do not remove `INSTITUTES_ADMIN_RECOVERY_PASSWORD` until the separate legacy recovery workflow has been secured.
6. Secure `secure-institutes-admin-recovery.yml` separately. It is restored unchanged from `main` and remains a manual-only owner exception without the new environment gate or SHA guard. Do not dispatch it until it is protected or retired by the owner.

The existence of an `environment:` declaration in YAML does **not** prove reviewer protection is configured. Environment reviewers, wait timers, branch restrictions and secret placement were not read or changed in this task.

## Regression and safety evidence

- `scripts/ci/test-production-workflow-isolation.py` is run by the required `Test` workflow.
- It checks manual-only triggers for production-facing workflows, protected environment declarations and shared non-cancelling queued concurrency for gated workflows, exact-SHA dispatch guard usage, and absence of production SSH/secrets from ordinary CI workflows.
- Production `workflow_run` handoffs have been removed rather than left as an alternate authorization path.
- No production workflow was dispatched. No Hostinger, hPanel, SSH, production DB, production Artisan, deployment, migration, discovery, or production data operation was performed.
- PRs #1009–#1015 remain open and unmerged.
- GCU historical candidate rows #348–353 and batches #19–23 were not accessed or changed. AI extraction and the additional 20 HEC profiles remain disabled.

## Completion status

**Repository implementation:** pending exact-head CI and final review.  
**GitHub environment protection:** owner action required; not verified.  
**Phase 1C:** not complete.  
