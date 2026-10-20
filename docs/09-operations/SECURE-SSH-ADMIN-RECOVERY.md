# Secure SSH administrator recovery

## Purpose

This workflow provides a narrowly scoped, manually authorized recovery path when every Institute administrator is locked out. It connects over the existing Hostinger SSH GitHub secrets and does not expose password values in workflow logs.

## Required GitHub Actions secrets

In the private repository settings, configure these repository Actions secrets:

- `HOSTINGER_SSH_HOST`
- `HOSTINGER_SSH_USER`
- `HOSTINGER_SSH_PRIVATE_KEY`
- `HOSTINGER_SSH_PORT`
- `INSTITUTES_ADMIN1_TEMP_PASSWORD`
- `INSTITUTES_ADMIN2_TEMP_PASSWORD`

The first four are the existing SSH connection secrets. Create the two temporary-password secrets only for this controlled recovery. Each must be at least 12 characters and contain uppercase and lowercase letters, a number, and a symbol. Do not put passwords in workflow inputs, commits, issue comments, chat, or screenshots. Use two different temporary passwords. After the recovery succeeds and each administrator has changed their password, delete both temporary-password secrets from GitHub.

## Runbook

1. Confirm the Hostinger deployment is on the expected main-branch commit and the SSH secrets are present.
2. Open **Actions → Secure Institutes Administrator Recovery → Run workflow**.
3. Keep the project path at the verified default unless the read-only path diagnostic has established a different path.
4. The existing administrator source email defaults to `m_attari20@yahoo.com`; the target Admin_01 login defaults to `mattari20@gmail.com`. The workflow aborts if it cannot find the source admin, if the target email belongs to a different user, if the second admin email conflicts with a non-admin, or if required schema is missing.
5. Set the second administrator's name and unique email. Type exactly `RECOVER-AND-ENSURE-TWO-ADMINS` in the confirmation field and run the workflow.
6. Check the workflow result. It reports only account IDs and pass/fail statuses; it never prints password values or password hashes.
7. Sign in to `https://estudent.pk/institutes/login` with each administrator's temporary password. Immediately change each password at the forced password-change page.
8. Delete `INSTITUTES_ADMIN1_TEMP_PASSWORD` and `INSTITUTES_ADMIN2_TEMP_PASSWORD` from repository Actions secrets. Verify both accounts can sign in again using their new private passwords.

## Safety behavior

- Uses the existing administrator row identified by the source email; it does not delete users or create a replacement row for Admin_01.
- Checks email conflicts before changing account records.
- Applies only the `must_change_password` migration, not all pending migrations.
- Runs account writes in a database transaction.
- Rotates the existing administrator's remember token and forces both accounts to change their temporary password.
- Does not reset the second administrator if that email already belongs to an admin.
- Aborts on ambiguous identity, role mismatch, email conflict, missing secrets, invalid temporary-password policy, or missing schema.
- Never logs passwords, password hashes, or database credentials.

## Deployment and limits

The workflow is deliberately `workflow_dispatch` only. It must not execute on every deployment. The repository's Hostinger Git deployment does not automatically run this recovery workflow. It runs directly over SSH against the deployed project and is independent of normal code deployment.

The workflow is a controlled recovery tool, not a replacement for routine account management. Once access is restored, create regular operator accounts through **Operations → Users → Create User** and use the UI for ordinary account management.

## Verification scope

A successful workflow proves that the SSH job connected, the targeted migration is present, the intended account records exist with hashed credentials, and the same Admin_01 row ID was preserved. A separate interactive sign-in smoke test is still required to verify the complete browser session and password-change flow.
