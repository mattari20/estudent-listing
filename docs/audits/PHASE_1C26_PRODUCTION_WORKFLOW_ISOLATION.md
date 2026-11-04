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
6. **Administrator recovery is an unresolved executable exception.** `secure-institutes-admin-recovery.yml` still accepts `workflow_dispatch`, invokes `appleboy/ssh-action`, and consumes `HOSTINGER_SSH_*` plus `INSTITUTES_ADMIN_RECOVERY_PASSWORD`. The repository-edit attempt to add its environment/guard was rejected by the available GitHub write action, so the file was left unchanged rather than attempting a workaround. It does not have the exact-SHA guard, operation-reason input, `production-operations` environment, or shared concurrency group. **Owner action before any further merge/use:** in GitHub Actions open “Recover Existing Institutes Administrator” and choose **Disable workflow**; verify GitHub shows it disabled. Then remove or rotate `INSTITUTES_ADMIN_RECOVERY_PASSWORD` after confirming the recovery is no longer needed. Re-enable only after a separately reviewed change adds the full guard and environment protection. Do not dispatch it.

The existence of an `environment:` declaration in YAML does **not** prove reviewer protection is configured. Environment reviewers, wait timers, branch restrictions and secret placement were not read or changed in this task.

## Regression and safety evidence

- `scripts/ci/test-production-workflow-isolation.py` is run by the required `Test` workflow.
- It checks manual-only triggers for production-facing workflows, protected environment declarations and shared non-cancelling queued concurrency for gated workflows, exact-SHA dispatch guard usage, and absence of production SSH/secrets from ordinary CI workflows.
- Production `workflow_run` handoffs have been removed rather than left as an alternate authorization path.
- No production workflow was dispatched. No Hostinger, hPanel, SSH, production DB, production Artisan, deployment, migration, discovery, or production data operation was performed.
- PRs #1009–#1015 remain open and unmerged.
- GCU historical candidate rows #348–353 and batches #19–23 were not accessed or changed. AI extraction and the additional 20 HEC profiles remain disabled.

## Phase 1C-26A regression repair addendum

The first structural audit found three workflows that placed production credentials in job-level `env`, exposing them to the guard step before it ran. This was corrected:

- `institutes-diagnostics-verify.yml`: diagnostics token is now scoped to the authenticated verification step after the guard.
- `offering-gap-preflight.yml`: DB credentials are scoped to the post-guard validation and read-only Artisan steps; the duplicate environment key was removed.
- `institutes-private-diagnostics-report.yml`: diagnostics and private-repository tokens are scoped to the post-guard retrieval step.

The Python regression suite now parses actual YAML with `scripts/ci/read-workflow-yaml.rb`, distinguishes YAML `uses`/secret fields from arbitrary `run` text, checks top-level event keys, checks job environments and guard ordering, and tests positive/negative dispatch-guard cases using an isolated temporary Git remote. The Laravel deployment-handoff test now parses top-level workflow events instead of rejecting the literal word `workflow_run` inside comments or embedded scripts. Existing discovery and migration contract scripts were updated to the manual-only protected dispatch design.

The guard now restricts `operation_reason` to 8–128 safe ASCII characters to prevent multiline/control-character log injection. The required `Test` workflow also runs the offline Project Controller unit suite, Command Orchestrator unit suite, and offline controller smoke check; these do not invoke live repository inventory or production services.

## Exact-head validation snapshot

- `main`: `92e748f7aeedb25d098775be6ff16abbbda23749`
- PR #1016 head: `5a2dd4ea621f2c991b0522e9753ebb893873836b`
- `Laravel CI`: run `41814322540`, exact-head success. Laravel PHPUnit and Pint verification passed.
- `Test`: run `41814322351`, exact-head failure only at the final structural production-isolation gate. The preceding workflow YAML parser, offline Project Controller tests, offline Command Orchestrator tests, controller smoke check, existing migration/discovery/GCU/runtime contracts, and `composer test` all passed. The final audit reports only `secure-institutes-admin-recovery.yml` as unprotected.
- Isolated MySQL migration workflow was not dispatched: this PR changes workflow/test code, not database migrations, and its migration-focused PR path filter did not match.
- No production-facing workflow was dispatched.

**Current blocker:** the structural audit correctly flags `secure-institutes-admin-recovery.yml`. Its SSH action and recovery secret remain executable through manual dispatch without the exact-SHA guard, reason input, `production-operations` environment or shared concurrency. A repository write attempting to secure this legacy workflow was blocked by the available GitHub write action. It was not bypassed. The new isolation test intentionally remains red until the owner disables that workflow or it is remediated in an authorized change.

## Completion status

**Repository implementation:** core workflow isolation and regression repairs implemented; administrator-recovery remains an executable security exception. The isolation Test intentionally fails on that single exception.  
**GitHub environment protection:** owner action required; not verified. Administrator-recovery must be disabled by the owner pending remediation.  
**Phase 1C-26A:** NOT COMPLETE; owner must disable/remediate administrator-recovery, then rerun exact-head CI.  
**Phase 1C:** not complete.  
