# DEV-318 — Production Security Hardening

## Scope

This batch hardens the production security boundary without changing the public institute directory contract.

## Changes

### Secure session cookies

Session cookies now default to secure=true when APP_ENV=production.

The existing SESSION_SECURE_COOKIE environment variable remains the explicit override, so operators can control the setting when required by the deployment topology.

http_only=true and same_site=lax remain enabled.

### Login throttling

Institute operator login attempts are now rate-limited per normalized email and client IP.

- Maximum failed attempts: 5
- Decay window: 60 seconds
- Successful authentication clears the throttle
- A throttled request returns HTTP 429
- Existing session regeneration remains in place after authentication
- Non-operator accounts are logged out and their session is invalidated

This is deliberately scoped to the institute login endpoint and does not affect public institute discovery.

## Validation

Regression coverage is provided by tests/Feature/InstituteSecurityHardeningTest.php.

The test suite verifies:

1. A valid non-operator account cannot remain authenticated through the institute login flow.
2. Repeated invalid login attempts are throttled after five failures.

CI execution is not claimed by this document unless GitHub reports a completed workflow run.

## Deployment requirement

For production deployments, use:

- APP_ENV=production
- APP_DEBUG=false
- a strong non-empty APP_KEY
- HTTPS for the application URL
- SESSION_SECURE_COOKIE=true when HTTPS is used

Do not commit real environment secrets to the repository.

## Account password and recovery controls

The account-security change set adds:

- Current-password verification before an authenticated operator changes a password.
- A minimum password length of 12 characters plus letter, mixed-case, number, and symbol rules.
- Administrator-only account creation and role assignment; the operator middleware protects operations routes.
- A generic forgot-password response so the page does not reveal whether an email belongs to an authorized account.
- Rate limits on forgot-password form requests, reset-link requests, and reset submissions.
- Password reset tokens managed by Laravel's password broker, with a 60-minute expiry and one-time use.
- Remember-token rotation after a successful password change or reset.

## Account-security release checklist

Do not treat password recovery as production-ready until all applicable checks are complete:

- [ ] Latest Test and Laravel CI workflows pass for the exact commit being released.
- [ ] Review the PR diff and confirm account creation remains behind the administrator middleware.
- [ ] Configure Hostinger SMTP values privately in the production environment: MAIL_MAILER, MAIL_SCHEME, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, and MAIL_FROM_NAME. Never commit real credentials.
- [ ] Verify the sender address/domain and confirm outbound mail works from the deployed environment.
- [ ] Back up the production database and confirm the normal migration/rollback procedure before applying the password-reset-token table migration.
- [ ] Apply the migration during the approved deployment window; do not assume CI's temporary SQLite database changes production.
- [ ] Test a real authorized operator recovery email end-to-end, including link expiry and successful one-time reset.
- [ ] Confirm unknown and non-operator email addresses receive the same public response and cannot reset a password.
- [ ] Confirm an operator can change their password only after supplying the current password, and an operator cannot create accounts.
- [ ] Confirm HTTPS, APP_ENV=production, APP_DEBUG=false, a strong APP_KEY, and secure session cookies remain configured.
- [ ] Record deployment and smoke-test evidence before marking the feature released.

The latest successful CI run validates the automated test environment only. It does not prove that Hostinger SMTP is configured, the production migration has run, or a live email has been delivered.


## Administrator-assisted password recovery

For the initial team of two administrators and two operators, paid SMTP is not required for routine recovery.

- An administrator may reset another account, but may not reset their own password through user management.
- A reset generates a strong temporary password. It is displayed once for the administrator to copy and share privately; never place it in email, logs, or support tickets.
- The account is marked as requiring a password change. Operator middleware redirects the account to Change Password until the temporary password is replaced.
- Reset rotates the remember token and records an audit event.
- If both administrators are locked out, recovery must use authorized hosting access and a controlled server-side procedure. Never add a public bypass.
- Keep two independently accessible administrator accounts and test recovery with non-production accounts before release.
- Email reset is marked unavailable until a transactional mail provider is configured and delivery is tested.

This code change requires applying `2026_10_17_000002_add_must_change_password_to_users_table.php` in each deployed environment after a verified backup.
