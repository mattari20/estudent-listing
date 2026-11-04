# Phase 1C-25C — Production Workflow Environment Binding and Trigger Isolation

**Repository:** `mattari20/estudent-listing`  
**Base:** `main` at `92e748f7aeedb25d098775be6ff16abbbda23749`  
**Branch:** `phase-1c25c-production-environment-binding`  
**Scope:** Repository workflow and regression-test changes only. No production access or workflow dispatch.

## 1. Owner-confirmed environment configuration

The owner reports that these three GitHub Environments exist and are restricted to `main`:

- `production-readonly-diagnostics`
- `offering-gap-readonly-audit`
- `production-reset`

The owner reports required reviewers, wait timers, and administrator bypass are disabled. The owner reports that `offering-gap-readonly-audit` contains the five existing `OFFERING_GAP_DB_*` secrets, and `production-readonly-diagnostics` currently contains no secrets. These are owner-reported facts; this repository-only task did not query or change environment settings or secret values.

No fourth environment is assumed or created. In particular, `production-operations` does not exist for this task.

## 2. Production workflow inventory and disposition

The current workflow tree contains 34 workflow files after retirement of the obsolete administrator recovery workflow. The 26 production-facing workflows below are classified by capability and protected as follows. Every active read-only diagnostic workflow is manual-only, declares its owner-confirmed environment, and checks the exact dispatch SHA/reason/unique confirmation before external production access. Credential-backed SSH/API diagnostics additionally validate required environment-scoped credentials after the guard and before credential use. The public HTTP-only monitors do not use SSH, database credentials, or private API tokens; they require the same manual dispatch guard and protected environment, but correctly have no private credential-validation step.

### 2.1 Read-only production diagnostics — 17 workflows

| Workflow | Purpose / access | Triggers after change | Environment | Credential names referenced | Automatic production access prevented / remaining owner action |
|---|---|---|---|---|---|
| `audit-next-institution-catalog.yml` | Read-only catalog/data-gap report over Hostinger SSH | `workflow_dispatch` only | `production-readonly-diagnostics` | `PRODUCTION_READONLY_HOSTINGER_SSH_HOST`, `_USER`, `_PRIVATE_KEY`, `_PORT` | Yes; add the four environment secrets before manual use |
| `automated-live-integrity-audit.yml` | Read-only integrity report / Artisan over Hostinger SSH | `workflow_dispatch` only | `production-readonly-diagnostics` | Same four `PRODUCTION_READONLY_HOSTINGER_SSH_*` values | Yes; add the four environment secrets before manual use |
| `diagnose-hostinger-artisan-path.yml` | Read-only remote runtime/path diagnostics | `workflow_dispatch` only | `production-readonly-diagnostics` | Same four `PRODUCTION_READONLY_HOSTINGER_SSH_*` values | Yes; add the four environment secrets before manual use |
| `discovery-runtime-readiness.yml` | Read-only discovery runtime readiness | `workflow_dispatch` only | `production-readonly-diagnostics` | Same four `PRODUCTION_READONLY_HOSTINGER_SSH_*` values | Yes; add the four environment secrets before manual use |
| `hostinger-artisan-runtime-only.yml` | Read-only Artisan runtime diagnostic delivery | `workflow_dispatch` only | `production-readonly-diagnostics` | `PRODUCTION_READONLY_HOSTINGER_SSH_HOST`, `_USER`, `_PRIVATE_KEY`, `_PORT`, `_FINGERPRINT` | Yes; add all five environment secrets before manual use |
| `hostinger-readonly-diagnostics.yml` | Read-only Hostinger diagnostics | `workflow_dispatch` only | `production-readonly-diagnostics` | Same four `PRODUCTION_READONLY_HOSTINGER_SSH_*` values | Yes; add the four environment secrets before manual use |
| `official-source-refresh-readiness.yml` | Read-only production schema/readiness diagnostics | `workflow_dispatch` only | `production-readonly-diagnostics` | Same four `PRODUCTION_READONLY_HOSTINGER_SSH_*` values | Yes; add the four environment secrets before manual use |
| `readonly-reset-inventory.yml` | Read-only schema/reset inventory; does not reset | `workflow_dispatch` only | `production-readonly-diagnostics` | Same four `PRODUCTION_READONLY_HOSTINGER_SSH_*` values | Yes; add the four environment secrets before manual use |
| `step3-candidate-audit.yml` | Read-only candidate audit | `workflow_dispatch` only | `production-readonly-diagnostics` | Same four `PRODUCTION_READONLY_HOSTINGER_SSH_*` values | Yes; add the four environment secrets before manual use |
| `step3-record-level-remediation-audit.yml` | Read-only record-level remediation audit | `workflow_dispatch` only | `production-readonly-diagnostics` | Same four `PRODUCTION_READONLY_HOSTINGER_SSH_*` values | Yes; add the four environment secrets before manual use |
| `verify-uvas-public-directory.yml` | Read-only public-directory verification via Hostinger SSH | `workflow_dispatch` only | `production-readonly-diagnostics` | Same four `PRODUCTION_READONLY_HOSTINGER_SSH_*` values | Yes; add the four environment secrets before manual use |
| `institutes-diagnostics-verify.yml` | Authenticated read-only diagnostics API verification | `workflow_dispatch` only | `production-readonly-diagnostics` | `PRODUCTION_READONLY_INSTITUTES_DIAGNOSTICS_TOKEN` | Yes; add the environment token before manual use |
| `institutes-private-diagnostics-report.yml` | Retrieve diagnostics and store report in private repository | `workflow_dispatch` only | `production-readonly-diagnostics` | `PRODUCTION_READONLY_INSTITUTES_DIAGNOSTICS_TOKEN`, `PRODUCTION_READONLY_INSTITUTES_PRIVATE_REPORTS_TOKEN` | Yes; add both environment tokens before manual use |
| `institutes-public-monitor.yml` | Public HTTP smoke check of live Institutes pages; no SSH/DB/Artisan | `workflow_dispatch` only | `production-readonly-diagnostics` | None | Yes; main-only environment and manual dispatch guard |
| `public-browser-runtime-diagnostics.yml` | Public Chromium diagnostics against live Institutes pages/API; no SSH/DB/Artisan | `workflow_dispatch` only | `production-readonly-diagnostics` | None | Yes; main-only environment and manual dispatch guard |
| `hostinger-readonly-offering-gap-audit.yml` | Read-only offering-gap preflight/reconciliation over Hostinger SSH | `workflow_dispatch` only | `offering-gap-readonly-audit` | `OFFERING_GAP_READONLY_HOSTINGER_SSH_HOST`, `_USER`, `_PRIVATE_KEY`, `_PORT`, `_FINGERPRINT` | Yes; add the five SSH environment secrets; existing DB secrets are not substituted for these SSH credentials |
| `offering-gap-preflight.yml` | Read-only offering-gap database preflight/reconciliation | `workflow_dispatch` only | `offering-gap-readonly-audit` | Existing `OFFERING_GAP_DB_HOST`, `_PORT`, `_DATABASE`, `_USERNAME`, `_PASSWORD` | Yes; owner reports these five DB secrets already exist; step-level validation follows the dispatch guard |

#### Public HTTP-only exception and authorization boundary

`institutes-public-monitor.yml` and `public-browser-runtime-diagnostics.yml` perform public-page/browser HTTP checks only. They do not access Hostinger SSH, the production database, Artisan, or authenticated private diagnostics APIs, and must not reference production credentials. They are therefore classified separately from credential-backed diagnostics by the regression scanner using executable job behavior (SSH actions/production secret references versus public Institutes HTTP targets), not by filename.

Both workflows remain bound to `production-readonly-diagnostics`, expose only `workflow_dispatch`, require `target_sha`, `operation_reason`, and `confirm_production_operation`, and run the shared authorization guard before any public HTTP request. The guard requires the immutable dispatch SHA to be the exact current `main` SHA, a bounded reason, and the workflow-specific confirmation phrase. Push, pull-request, and schedule triggers are rejected. The public HTTP exception removes only the unnecessary private-credential-validation requirement; it does not relax authorization, environment, trigger, or write-operation controls.

Each active read-only job uses the shared dispatch guard before the first production credential or production HTTP access. The guard requires: `workflow_dispatch`, `refs/heads/main`, an exact 40-character target SHA equal to the immutable event SHA and current remote `main`, a bounded operation reason, and an operation-specific confirmation phrase. Credential checks do not print values and fail before the SSH/API action if an environment secret is absent.

### 2.2 Write-capable production operations — explicitly blocked

These workflows are manual-only and their production jobs have a literal fail-closed `if: false`. They are not assigned to a read-only environment or to an environment that does not exist. They continue to reference legacy repository-level Hostinger secret names in their skipped job definitions; no runner starts and no credentials are exposed while the job remains skipped.

| Workflow | Capability | Triggers | Environment | Current credential references | Disposition |
|---|---|---|---|---|---|
| `controlled-discovery-outcome-migration.yml` | Production migration | `workflow_dispatch` only | None | Legacy Hostinger secret references remain inside the explicitly skipped job | Blocked; no production environment assigned |
| `controlled-hostinger-code-deploy.yml` | Production deployment | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values | Blocked |
| `controlled-import-batch-duplicate-rows-migration.yml` | Production migration | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values | Blocked |
| `controlled-institution-field-approvals-migration.yml` | Production migration | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values | Blocked |
| `controlled-official-source-refresh-migration.yml` | Production migration/index repair | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values | Blocked |
| `controlled-pakistan-geography-seed.yml` | Production seed/write operation | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values | Blocked |
| `hostinger-gcu-single-source-qualification.yml` | Remote source qualification and private report write | `workflow_dispatch` only | None | `HOSTINGER_SSH_HOST`, `_USER`, `_PRIVATE_KEY`, `_PORT`, `_FINGERPRINT` | Blocked; do not treat as a read-only diagnostic |
| `scheduled-institute-discovery.yml` | Discovery/source monitoring with possible production writes | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values; `GITHUB_TOKEN` for artifact handoff | Blocked; duplicate job-level condition removed so the fail-closed condition cannot be overridden |

No write-capable production operation is authorized by this phase. These jobs remain blocked; do not assign them to either read-only environment or assume a fourth environment exists. Any future operation requires a separately approved design and explicit owner authorization before its workflow or environment configuration is changed.

### 2.3 Administrator password-reset recovery — retired

The executable `.github/workflows/secure-institutes-admin-recovery.yml` has been deleted from this PR branch. The password-reset mechanism is retired, not merely disabled. The non-executable retirement runbook is retained at `docs/09-operations/SECURE-SSH-ADMIN-RECOVERY.md`.

The owner-supplied screenshot previously supported that the Actions UI workflow was disabled. That account-level setting is not independently verified by repository access; keep it disabled as defense in depth. Repository deletion does not change GitHub account settings.

### 2.4 Remaining repository-only workflows

The remaining nine workflows are not production credential/SSH/DB access paths based on the current static inspection:

- `build-frontend-assets.yml`
- `institution-import-preflight.yml`
- `isolated-mysql-approval-migration-validation.yml`
- `laravel-ci.yml`
- `offering-gap-source-verification.yml` (local Artisan command; external official-source HTTP checks, no production DB credentials)
- `project-command-orchestrator.yml`
- `project-controller.yml`
- `step3-evidence-register-validation.yml`
- `test.yml`

Their useful CI, local SQLite/isolated MySQL validation, repository automation and source-verification functionality is retained. Ordinary CI retains its normal push/pull-request triggers and does not reference production SSH/database credentials.

## 3. Credential migration plan — owner action only

No secret values were read, created, copied, rotated, deleted, or modified.

### `production-readonly-diagnostics`

Owner must add these environment-scoped secrets before manually running the corresponding workflows:

- `PRODUCTION_READONLY_HOSTINGER_SSH_HOST`
- `PRODUCTION_READONLY_HOSTINGER_SSH_USER`
- `PRODUCTION_READONLY_HOSTINGER_SSH_PRIVATE_KEY`
- `PRODUCTION_READONLY_HOSTINGER_SSH_PORT`
- `PRODUCTION_READONLY_HOSTINGER_SSH_FINGERPRINT` (needed by the runtime diagnostic that verifies the host fingerprint)
- `PRODUCTION_READONLY_INSTITUTES_DIAGNOSTICS_TOKEN`
- `PRODUCTION_READONLY_INSTITUTES_PRIVATE_REPORTS_TOKEN`

The environment currently has no secrets per owner report. SSH/API workflows now check for the required environment-scoped credentials after the authorization guard and fail before attempting the connection/request if any required value is missing. The two public HTTP diagnostics need no secrets.

### `offering-gap-readonly-audit`

The owner reports that these five database secrets already exist in this environment:

- `OFFERING_GAP_DB_HOST`
- `OFFERING_GAP_DB_PORT`
- `OFFERING_GAP_DB_DATABASE`
- `OFFERING_GAP_DB_USERNAME`
- `OFFERING_GAP_DB_PASSWORD`

The Hostinger-based offering-gap audit additionally needs its dedicated environment-scoped SSH credentials:

- `OFFERING_GAP_READONLY_HOSTINGER_SSH_HOST`
- `OFFERING_GAP_READONLY_HOSTINGER_SSH_USER`
- `OFFERING_GAP_READONLY_HOSTINGER_SSH_PRIVATE_KEY`
- `OFFERING_GAP_READONLY_HOSTINGER_SSH_PORT`
- `OFFERING_GAP_READONLY_HOSTINGER_SSH_FINGERPRINT`

Do not assume the database secrets can substitute for SSH credentials.

### `production-reset`

The environment is preserved and reserved for separately designed, explicitly approved future operations. No password-reset workflow is provided or authorized by this phase. Do not restore the retired executable recovery mechanism as a compatibility workaround.

### Repository-level secrets that must not be deleted yet

Do not delete remaining repository-level `HOSTINGER_SSH_*`, `INSTITUTES_ADMIN_RECOVERY_PASSWORD`, `INSTITUTES_DIAGNOSTICS_TOKEN`, or `INSTITUTES_PRIVATE_REPORTS_TOKEN` until all dependent workflows have been migrated, reviewed, and independently verified. The write-capable workflows remain blocked and still reference the legacy Hostinger names. This phase did not inspect or change the values.

## 4. Relationship to PR #1016 and prior Phase 1C work

- PR #1016 remains open and unmerged. Its 30 overlapping files were reconciled against this branch rather than merged blindly.
- This branch is authoritative for the 27 overlapping workflow files: it binds active read-only diagnostics to `production-readonly-diagnostics` or `offering-gap-readonly-audit`, and leaves write-capable production jobs explicitly skipped without assigning an undeclared environment.
- The #1016 structural isolation scanner, YAML reader, and non-executable recovery-retirement runbook are incorporated here with tests adapted to the three-environment policy. The old password-reset workflow itself is deleted.
- The shared dispatch guard in this branch requires manual dispatch, `main`, exact current-main SHA, bounded reason, and a unique operation-specific confirmation. Environment-scoped credentials are checked after the guard. Read-only diagnostic workflows do not reference repository-level Hostinger or diagnostics-token secrets.
- No `production-operations` environment is declared or assumed. `production-reset` remains reserved for explicitly approved future operations.
- PRs #1009–#1016 remain open and unmerged. This consolidation does not merge or close any PR.
- The Phase 1C-25B workflow inventory was rechecked against all 35 current workflow files. The 10 previously highlighted automatic production-access workflows were a subset, not the complete production-connected inventory.
- The Phase 1C-23 execution checkpoint and Phase 1C-24 final acceptance audit were reviewed. The Phase 1C-26D/#1016 document was reviewed, including its Phase 1C-28 remediation update. No separately named Phase 1C-25B, 1C-28B or 1C-29 audit files were present in the current `main` audit directory; the owner-supplied 25B findings and the existing #1016 documentation were used as continuity evidence.

## 5. Regression coverage and CI

`scripts/ci/test-production-environment-binding.rb` is wired into `.github/workflows/test.yml` and validates:

1. All 34 workflow files are inventoried after retiring the obsolete administrator recovery workflow.
2. All 17 read-only production workflows are manual-only, have required inputs, and declare the correct owner-confirmed environment.
3. Credential-backed SSH/API workflows use step-scoped environment-specific credentials validated after the guard and before access; public HTTP-only workflows require no private credentials and reject any production credential reference.
4. Public HTTP diagnostics are manual-only, protected-environment-bound, exact-SHA/reason/operation-confirmation guarded, and credential-free; positive/negative fixtures distinguish them from credential-backed diagnostics.
5. The offering-gap DB workflow retains its dedicated environment and validates DB credentials after authorization.
6. The eight write-capable production workflows are manual-only and explicitly skipped, with no production environment assigned.
7. The obsolete administrator password-reset workflow is absent; its retirement runbook remains.
8. Only the three owner-confirmed environments may be declared; no workflow references `production-operations`.
9. Ordinary CI retains push/pull-request triggers and remains free of production secrets/SSH actions.
10. The shared guard enforces event type, main ref, exact current SHA, bounded reason, operation-specific confirmation, and execution from its isolated checkout.
11. Workflow-specific concurrency groups and cancellation semantics are retained and regression-tested.
12. Blocked write-capable/reset jobs have exactly one fail-closed job-level condition, preventing duplicate YAML keys from overriding the block.

The CI suite is intended to run on the dedicated PR. No production workflow was dispatched. No production database or Hostinger operation was performed.

## 6. Safety boundary and completion status

- No Hostinger, hPanel, SSH, production database, production Artisan, deployment, migration, reset, discovery, candidate staging, approval, import, canonical promotion or publication was accessed or executed.
- No GitHub Environment settings, repository/environment secrets, branch protection, rulesets, reviewers, bypass settings or wait timers were changed.
- GCU historical candidate rows #348–353 and batches #19–23 were not accessed or modified.
- AI extraction remains disabled; the additional 20 HEC identity profiles remain disabled for live monitoring.
- No PR was merged.

**Phase 1C-25C repository status:** awaiting exact-head PR CI and final review.  
**Production verification:** not performed and not claimed.  
**Phase 1C overall:** remains open; no phase-wide completion declaration is made.
