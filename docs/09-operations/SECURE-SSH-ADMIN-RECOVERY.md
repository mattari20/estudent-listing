# Administrator recovery workflow retirement

## Status

The executable GitHub Actions workflow `.github/workflows/secure-institutes-admin-recovery.yml` is retired and must remain absent. It is not an approved recovery mechanism and must not be reintroduced or dispatched.

The owner-supplied GitHub Actions screenshot supports that the corresponding workflow was disabled in the Actions UI. Keep that disabled state in place as defense in depth. This is owner-supplied evidence, not an independently queried account setting. Repository deletion does not itself prove or change the account-level disabled setting.

## Why it was retired

The legacy workflow could connect to production Hostinger and change an existing administrator password. This phase retires that password-reset mechanism rather than attempting to preserve compatibility with a legacy production-access path.

## Future operation policy

The `production-reset` environment remains reserved for separately designed, explicitly approved future operations. No administrator password-reset workflow or replacement is provided by this retirement record. Any future operation requires its own owner-approved design, least-privilege credentials, exact-target authorization, manual-only execution, and security regression coverage. Do not create or invoke an alternate password-reset path as a workaround.

## Owner action — obsolete secret

After confirming that the retired implementation is no longer required, the repository owner must remove or rotate the GitHub Actions secret `INSTITUTES_ADMIN_RECOVERY_PASSWORD`. This repository change does not inspect or modify the secret. Record the owner's completion separately without recording the secret value.

## Verification boundary

This is a repository-code retirement only. It does not access Hostinger, SSH, the production database, production Artisan, production workflows, GitHub secrets, environment reviewers, or branch protection. It does not verify production readiness or authorize a production operation.
