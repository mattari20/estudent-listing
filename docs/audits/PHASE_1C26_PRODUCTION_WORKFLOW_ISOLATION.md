# Phase 1C-26D — Production Workflow Isolation and Recovery Retirement

**Repository:** `mattari20/estudent-listing`  
**PR:** #1016  
**Branch:** `phase-1c26-production-workflow-isolation`  
**Base:** `main` at `92e748f7aeedb25d098775be6ff16abbbda23749`  
**Scope:** Repository workflow and regression-test changes only.

## Current disposition

The unsafe executable workflow `.github/workflows/secure-institutes-admin-recovery.yml` has been deleted from this PR branch. Its former runbook, `docs/09-operations/SECURE-SSH-ADMIN-RECOVERY.md`, is now a retirement record. Administrator recovery is not available through this repository until a separately reviewed, fully protected replacement is implemented.

The owner-supplied GitHub Actions screenshot is evidence that **Recover Existing Institutes Administrator** was disabled in the Actions UI. Treat this as owner-supplied account-level evidence; the connected repository integration cannot independently query the workflow's UI enabled/disabled state. Keep it disabled. Do not re-enable or dispatch it.

The owner must remove or rotate `INSTITUTES_ADMIN_RECOVERY_PASSWORD` after confirming the retired implementation is no longer required. No secret was read or modified.

## Required controls for every remaining production workflow

The structural regression suite parses the actual workflow YAML and scans every `.github/workflows/*.yml` and `.github/workflows/*.yaml` file. Any production-access workflow must satisfy all of the following:

- Top-level `workflow_dispatch` only; no push, pull-request, schedule, or workflow-run trigger.
- Required string inputs for the exact target SHA, operation reason/ticket, and operation-specific confirmation.
- Checkout of `${{ github.sha }}` with full history before authorization.
- The shared `scripts/ci/validate-production-dispatch.sh` guard before production credentials or remote access.
- Guard bindings for `inputs.target_sha`, `inputs.operation_reason`, and `inputs.confirm_production_operation`, plus a non-empty operation-specific expected phrase.
- A protected environment: `production-readonly-diagnostics`, `production-operations`, or `offering-gap-readonly-audit`, according to operation.
- Shared concurrency group `estudent-production-access`, `cancel-in-progress: false`, and `queue: max`.
- No production credentials at workflow or job scope before the guard; credentials remain step-scoped and least-privilege.
- Detection of SSH actions, direct SSH/SCP/SFTP shell commands, non-local MySQL/MariaDB/PostgreSQL client commands, Artisan commands in protected production environments, and production database/SSH secret references. Ordinary CI and local scheduled checks are explicitly tested to remain free of production access.

The regression also asserts that the retired administrator-recovery workflow is absent, tests an unsafe SSH recovery reintroduction fixture, tests direct remote SSH and production DB secret detection, and preserves ordinary CI `push` and `pull_request` triggers.

## Workflow audit inventory

All 35 workflow files present at the start of Phase 1C-26D were enumerated and inspected. The retired administrator-recovery workflow is the one removed, leaving 34 workflow files on the PR branch.

Production-facing diagnostics, audits, and verification workflows reviewed:

- `audit-next-institution-catalog.yml`
- `automated-live-integrity-audit.yml`
- `diagnose-hostinger-artisan-path.yml`
- `discovery-runtime-readiness.yml`
- `hostinger-artisan-runtime-only.yml`
- `hostinger-readonly-diagnostics.yml`
- `hostinger-readonly-offering-gap-audit.yml`
- `official-source-refresh-readiness.yml`
- `readonly-reset-inventory.yml`
- `step3-candidate-audit.yml`
- `step3-record-level-remediation-audit.yml`
- `verify-uvas-public-directory.yml`
- `institutes-diagnostics-verify.yml`
- `institutes-private-diagnostics-report.yml`
- `offering-gap-preflight.yml`

Controlled production operations and migrations reviewed:

- `controlled-hostinger-code-deploy.yml`
- `controlled-discovery-outcome-migration.yml`
- `controlled-import-batch-duplicate-rows-migration.yml`
- `controlled-institution-field-approvals-migration.yml`
- `controlled-official-source-refresh-migration.yml`
- `controlled-pakistan-geography-seed.yml`
- `hostinger-gcu-single-source-qualification.yml`
- `scheduled-institute-discovery.yml`

The scheduled discovery workflow has a top-level manual-dispatch trigger; its conditional workflow-run handoff branch is not an event trigger. No production-facing workflow should gain a top-level `workflow_run` trigger. The discovery dispatch additionally requires `confirm_discovery=AUTHORIZE-GOVERNED-INSTITUTE-DISCOVERY`; the shared guard validates this separate value before the Hostinger SSH action. The generic production confirmation for discovery is also unique to that workflow.

Repository-only and scheduled workflows were also reviewed, including `test.yml`, `laravel-ci.yml`, `isolated-mysql-approval-migration-validation.yml`, `institution-import-preflight.yml`, `offering-gap-source-verification.yml`, `institutes-public-monitor.yml`, `public-browser-runtime-diagnostics.yml`, `project-controller.yml`, `project-command-orchestrator.yml`, `build-frontend-assets.yml`, and evidence-register validation. The public monitor and source-verification schedules are repository-side/public HTTPS checks, not production SSH or production DB entry paths. Ordinary CI retains its normal PR/push triggers and isolated MySQL service.

The isolation test is the final authority for the full workflow inventory: any production access newly introduced in any workflow is subject to the same structural validation, not a filename-based allowlist.

## Owner configuration evidence and remaining actions

### Supplied owner evidence (not independently API-verified)

- The owner-supplied GitHub Actions screenshot supports that **Recover Existing Institutes Administrator** was disabled in the Actions UI. This does not remove the executable workflow from `main`; that workflow remains on `main` until PR #1016 is integrated. The screenshot is recorded as owner evidence, not as an independently queried account state.
- The owner reports active `main` ruleset **25936221** requires the `test` status check and has no bypass actors. The connected GitHub integration used for this audit does not expose ruleset reads, so the ruleset ID, required-check configuration, and bypass-actor list remain owner-supplied rather than independently API-verified. Do not infer additional ruleset settings from this statement.

### Owner configuration still required

Repository YAML does not prove GitHub environment protection is configured. The owner must independently verify:

1. `production-readonly-diagnostics` has required reviewers, self-review prevention, and main-only deployment restrictions.
2. `production-operations` has required reviewers, self-review prevention, and main-only deployment restrictions.
3. `offering-gap-readonly-audit` has equivalent reviewer and branch restrictions.
4. Production SSH, database, and private-diagnostics credentials are scoped to the appropriate protected environments and not left as unnecessary repository-level secrets.
5. `INSTITUTES_ADMIN_RECOVERY_PASSWORD` is removed or rotated after confirming the retired recovery implementation is no longer needed.

No GitHub secrets, environment settings/reviewers, branch protection, required checks, or wait timers were changed.

## Safety and validation boundary

- No Hostinger, hPanel, SSH, production DB, production Artisan, deployment, migration, discovery, candidate operation, or production workflow dispatch was performed.
- GCU historical rows #348–353 and batches #19–23 were not accessed or modified.
- AI extraction and the additional 20 HEC profiles remain disabled.
- PRs #1009–#1015 and PR #1016 remain unmerged.
- Exact-head Test [41832226242](https://github.com/mattari20/estudent-listing/actions/runs/41832226242) and Laravel CI [41832226232](https://github.com/mattari20/estudent-listing/actions/runs/41832226232) both passed on final PR head `741c487a3f11b271f93cf00638cc427f6dbf9701` (887 tests / 3,961 assertions; 27 workflow-isolation regression tests).
- Isolated MySQL is only applicable if the migration workflow's migration-specific path filter matches; this task changes workflow/security documentation and tests, not migration files.

## Completion classification

- **IMPLEMENTED:** Recovery workflow retirement, retirement documentation, and structural regression changes are present on the PR branch.
- **TESTED:** Yes; final exact-head Test and Laravel CI passed on `741c487a3f11b271f93cf00638cc427f6dbf9701` (runs 41832226242 and 41832226232).
- **MERGED:** No; PR #1016 remains open.
- **PRODUCTION VERIFIED:** No; no production operation was performed, and GitHub environment protection/secret disposition remain owner actions.
- **Phase 1C-26D:** Repository implementation and exact-head CI are complete. Integration remains blocked pending obsolete-secret retirement evidence and independent verification of all three protected environments.
- **Phase 1C / production readiness:** Not complete or declared.


## Phase 1C-28 remediation update

The Phase 1C-28 changes extend the isolation contract rather than weakening it:

- The scanner now enumerates both YAML extensions.
- Workflow-level production secret references are rejected even when no job is otherwise classified as production-facing.
- Guard detection requires an executable `run` command invoking `scripts/ci/validate-production-dispatch.sh`; a step name or arbitrary text mention is not accepted.
- Production secret exposure in earlier steps is treated as an access boundary and rejected unless the guard has already executed.
- Generic `AUTHORIZE-PRODUCTION-OPERATION` is not accepted as an operation-specific phrase. The controlled deployment, migration, seed, GCU qualification, and discovery workflows use distinct expected phrases.
- Regression fixtures cover workflow-scope secrets, both YAML extensions, non-executable guard mentions, generic confirmation, and secrets supplied to a step before authorization.
- The discovery workflow requires both the shared target-SHA/reason/manual-dispatch guard and its dedicated `confirm_discovery` value before SSH.

Final exact-head validation is complete for PR head `741c487a3f11b271f93cf00638cc427f6dbf9701`: Test run 41832226242 and Laravel CI run 41832226232 both succeeded. These results apply only to that exact head. The owner-level environment controls and obsolete-secret retirement remain separate integration prerequisites.
