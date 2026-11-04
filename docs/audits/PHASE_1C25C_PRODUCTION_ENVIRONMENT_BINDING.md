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

The current workflow tree contains 35 workflow files. The 26 production-facing workflows below are classified by capability and protected as follows. Every active read-only diagnostic workflow is manual-only, declares its owner-confirmed environment, checks the exact dispatch SHA/reason/unique confirmation before production access, and validates required environment credentials before attempting SSH/API access. The two public HTTP diagnostics have no production credentials but use the same dispatch guard and environment boundary.

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

Each active read-only job uses the shared dispatch guard before the first production credential or production HTTP access. The guard requires: `workflow_dispatch`, `refs/heads/main`, an exact 40-character target SHA equal to the immutable event SHA and current remote `main`, a bounded operation reason, and an operation-specific confirmation phrase. Credential checks do not print values and fail before the SSH/API action if an environment secret is absent.

### 2.2 Write-capable production operations — explicitly blocked

These workflows are manual-only and their production jobs have a literal fail-closed `if: false`. They are not assigned to a read-only environment or to an environment that does not exist. They continue to reference legacy repository-level Hostinger secret names in their skipped job definitions; no runner starts and no credentials are exposed while the job remains skipped.

| Workflow | Capability | Triggers | Environment | Current credential references | Disposition |
|---|---|---|---|---|---|
| `controlled-discovery-outcome-migration.yml` | Production migration | `workflow_dispatch` only | None | `HOSTINGER_SSH_HOST`, `_USER`, `_PRIVATE_KEY`, `_PORT` | Blocked; requires owner-authorized operations environment before any reactivation |
| `controlled-hostinger-code-deploy.yml` | Production deployment | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values | Blocked |
| `controlled-import-batch-duplicate-rows-migration.yml` | Production migration | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values | Blocked |
| `controlled-institution-field-approvals-migration.yml` | Production migration | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values | Blocked |
| `controlled-official-source-refresh-migration.yml` | Production migration/index repair | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values | Blocked |
| `controlled-pakistan-geography-seed.yml` | Production seed/write operation | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values | Blocked |
| `hostinger-gcu-single-source-qualification.yml` | Remote source qualification and private report write | `workflow_dispatch` only | None | `HOSTINGER_SSH_HOST`, `_USER`, `_PRIVATE_KEY`, `_PORT`, `_FINGERPRINT` | Blocked; do not treat as a read-only diagnostic |
| `scheduled-institute-discovery.yml` | Discovery/source monitoring with possible production writes | `workflow_dispatch` only | None | Same four `HOSTINGER_SSH_*` values; `GITHUB_TOKEN` for artifact handoff | Blocked; duplicate job-level condition removed so the fail-closed condition cannot be overridden |

No `production-operations` environment was created or declared. If the owner later wants any of these operations enabled, that requires a separate explicit authorization to create and protect an operations environment, migrate credentials into it, and review each workflow's operation-specific safeguards. Until then, these jobs remain blocked.

### 2.3 Reset/recovery — separately isolated and blocked

| Workflow | Purpose | Triggers | Environment | Credential references | Disposition |
|---|---|---|---|---|---|
| `secure-institutes-admin-recovery.yml` | Password reset/recovery of existing administrator | `workflow_dispatch` only | `production-reset` | Legacy repository-level `INSTITUTES_ADMIN_RECOVERY_PASSWORD` and `HOSTINGER_SSH_HOST`, `_USER`, `_PRIVATE_KEY`, `_PORT` remain referenced in the skipped job | Explicit `if: false`; environment bound to `production-reset`; before any future reauthorization, owner must migrate these credentials to environment-scoped secrets and independently approve the workflow |

The repository integration cannot independently verify the Actions UI enabled/disabled toggle. A prior owner-supplied screenshot was recorded in the Phase 1C-26D audit as evidence that the workflow was disabled in the UI. This branch additionally makes the job unconditionally skipped and binds it to `production-reset`. PR #1016 proposes deleting this workflow; it remains present on `main` until a separately authorized integration.

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

The recovery job is skipped and bound to this environment. The workflow still contains legacy repository-level references to `INSTITUTES_ADMIN_RECOVERY_PASSWORD` and `HOSTINGER_SSH_*`. Before any future reauthorization, the owner must move the required recovery password and SSH connection credentials into `production-reset` environment secrets and update the workflow to use only those environment-scoped names. Do not enable or dispatch it as part of this phase.

### Repository-level secrets that must not be deleted yet

Do not delete remaining repository-level `HOSTINGER_SSH_*`, `INSTITUTES_ADMIN_RECOVERY_PASSWORD`, `INSTITUTES_DIAGNOSTICS_TOKEN`, or `INSTITUTES_PRIVATE_REPORTS_TOKEN` until all dependent workflows have been migrated, reviewed, and independently verified. The write-capable workflows remain blocked and still reference the legacy Hostinger names. This phase did not inspect or change the values.

## 4. Relationship to PR #1016 and prior Phase 1C work

- PR #1016 remains open and untouched. Its Phase 1C-26D audit records the Phase 1C-28 scanner hardening and requires a `production-operations` environment for write-capable operations. Its workflow changes overlap this phase and cannot be merged unchanged under the owner-confirmed three-environment policy.
- This branch preserves the useful objective of #1016—manual-only production access, exact-SHA/reason/confirmation guard, credential ordering, concurrency isolation and regression coverage—but uses only the three owner-confirmed environments.
- Rather than assigning deployment, migration, seed, qualification or discovery workflows to a read-only environment or silently introducing `production-operations`, this branch leaves their production jobs skipped.
- PRs #1009–#1016 remain open and unmerged. Their branches and commits were not modified; this PR branch incorporates the approved, non-conflicting #1016 security test and recovery-retirement material.
- The Phase 1C-25B workflow inventory was rechecked against all 35 current workflow files. The 10 previously highlighted automatic production-access workflows were a subset, not the complete production-connected inventory.
- The Phase 1C-23 execution checkpoint and Phase 1C-24 final acceptance audit were reviewed. The Phase 1C-26D/#1016 document was reviewed, including its Phase 1C-28 remediation update. No separately named Phase 1C-25B, 1C-28B or 1C-29 audit files were present in the current `main` audit directory; the owner-supplied 25B findings and the existing #1016 documentation were used as continuity evidence.

## 5. Regression coverage and CI

`scripts/ci/test-production-environment-binding.rb` is wired into `.github/workflows/test.yml` and validates:

1. All 35 workflow files are inventoried.
2. All 17 read-only production workflows are manual-only, have required inputs, and declare the correct owner-confirmed environment.
3. SSH/API credentials are step-scoped, environment-specific, validated after the guard, and not present at workflow/job scope.
4. Public HTTP diagnostics are manual-only and guarded.
5. The offering-gap DB workflow retains its dedicated environment and validates DB credentials after authorization.
6. The eight write-capable production workflows and the separate reset/recovery workflow are manual-only and explicitly skipped.
7. Reset/recovery is bound only to `production-reset`; blocked operations do not use an undeclared/read-only environment.
8. No workflow declares `production-operations` or any other unconfirmed environment.
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
