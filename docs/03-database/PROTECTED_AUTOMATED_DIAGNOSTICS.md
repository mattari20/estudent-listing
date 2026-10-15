# Protected Production Diagnostics Endpoint

| Field | Value |
| --- | --- |
| Status | Implemented in repository; not production-enabled |
| Route | `GET /institutes/automation/diagnostics` |
| Authentication | Bearer token from `INSTITUTES_DIAGNOSTICS_TOKEN` |
| Rate limit | 5 requests per minute |
| Command | `institutes:data-gap-report --json` |
| Writes | None |

## Security behavior

- Returns 404 until `INSTITUTES_DIAGNOSTICS_TOKEN` is configured with at least 32 characters.
- Rejects requests without a valid bearer token; comparison uses `hash_equals`.
- Runs only the existing read-only data-gap report command; callers cannot choose a command or supply SQL.
- Adds `Cache-Control: no-store`.
- Rate-limits requests.
- Returns the structured report and command exit code, without exposing environment variables, credentials, exception messages, or raw SQL errors.
- The report includes aggregate operational counts. Treat it as private even though it is sanitized.

## Enablement procedure — not yet performed

1. Review and merge the pull request after CI passes.
2. Confirm the merged commit is deployed by the existing Hostinger Git deployment.
3. Generate a unique, random secret of at least 32 characters using a trusted password manager. Do not reuse a login password or SSH key.
4. Add it to the Laravel environment as `INSTITUTES_DIAGNOSTICS_TOKEN` without committing it to GitHub or placing it in a URL. Do not share it in chat.
5. Store the same value as a GitHub Actions repository secret named `INSTITUTES_DIAGNOSTICS_TOKEN`.
6. Add a workflow that sends the secret only in the HTTPS Authorization header, validates TLS normally, captures the JSON response, and uploads only the sanitized report as a private Actions artifact. Never echo the token or request headers.
7. Test missing-token, invalid-token, valid-token, rate-limit, report JSON, and no-mutation behavior before enabling a schedule.

## Stop conditions

Do not enable the workflow if HTTPS certificate validation fails, the token is exposed in logs, the route responds without authentication, or the returned report contains unexpected sensitive fields. Do not alter the existing Hostinger Git deployment integration, enable Remote MySQL, or perform production writes. No production environment variable or GitHub secret has been configured by this repository change.
