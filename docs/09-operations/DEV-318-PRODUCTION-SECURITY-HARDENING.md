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
