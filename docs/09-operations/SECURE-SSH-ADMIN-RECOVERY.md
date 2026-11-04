# Administrator recovery workflow retirement

## Status

The executable GitHub Actions workflow `.github/workflows/secure-institutes-admin-recovery.yml` is retired from this branch. It is not an approved recovery mechanism and must not be reintroduced or dispatched.

The owner-supplied GitHub Actions screenshot supports that the corresponding workflow was disabled in the Actions UI. Keep that disabled state in place as defense in depth. This is owner-supplied evidence, not an independently queried account setting. Repository deletion does not itself prove or change the account-level disabled setting.

## Why it was retired

The legacy workflow could connect to the production Hostinger account and change an existing administrator password. It did not implement the repository's current protected production-dispatch contract: exact current-main SHA authorization, an operation reason, operation-specific confirmation, the shared production concurrency queue, and the approved `production-operations` environment with a guard before credential exposure.

A workflow being disabled in the GitHub UI is not a substitute for removing its executable production-access implementation or for structural CI enforcement.

## Recovery policy

Administrator recovery must use a separately reviewed replacement before any recovery operation is considered. That replacement must:

- Be manually dispatched only.
- Require the exact 40-character target SHA and verify it equals both the dispatch event SHA and current `main`.
- Require a valid operation reason or ticket reference and operation-specific confirmation.
- Run the shared `scripts/ci/validate-production-dispatch.sh` guard before any production credential is exposed or production connection is opened.
- Use the protected `production-operations` environment.
- Use the shared `estudent-production-access` concurrency group with cancellation disabled and pending-run queue protection.
- Keep credentials scoped to the guarded operation step and use least privilege.

No replacement is provided by this retirement record. Do not create or invoke an alternate password-reset path as a workaround.

## Owner action — obsolete secret

After confirming that the retired implementation is no longer required, the repository owner must remove or rotate the GitHub Actions secret `INSTITUTES_ADMIN_RECOVERY_PASSWORD`. This repository change does not inspect or modify the secret. Record the owner's completion separately without recording the secret value.

## Verification boundary

This change is a repository-code retirement only. It does not access Hostinger, SSH, the production database, production Artisan, production workflows, GitHub secrets, environment reviewers, or branch protection. It does not verify production readiness or authorize a production operation.
