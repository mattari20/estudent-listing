# DEV-125 — Phase 2 final acceptance audit

## Scope
This batch adds regression coverage for guest access to operator-only discovery history and package downloads, and confirms package retention dry-run preserves private files.

## Repository configuration findings
- Laravel scheduling is registered in `bootstrap/app.php`.
- `institutes:bootstrap-agent` runs hourly with `withoutOverlapping(55)`.
- `institutes:monitor-changes` runs hourly with `withoutOverlapping(55)`.
- The deterministic discovery command is `institutes:discover {profile=cui-lahore}`; AI extraction is opt-in via `--ai`.
- Monitoring invokes `SourceChangeDiscoveryService`, which records `no_change`, `empty_snapshot`, `discovery_disabled`, `failed`, `candidates_created`, `empty_candidates`, and `no_matching_profile` outcomes.
- The source monitor is scheduled; a live Hostinger scheduler heartbeat still must be checked separately.

## Remaining live acceptance
1. Run read-only Hostinger diagnostics and compare the deployed revision with the current intended `main` commit.
2. Confirm the discovery run outcome migration is present in live schema/migration status.
3. Authenticate as an operator and load the run history page.
4. Download an existing valid package and confirm private/no-store and nosniff headers.
5. Verify guest access is denied on the live host.
6. Verify scheduler execution records a run outcome without AI credentials.
7. Confirm package paths remain in Laravel private storage, outside the public web root.
8. Confirm discovery-only execution does not modify canonical institution/program records.

## Safety
No live production writes, migrations, promotions, or package deletion are performed by this audit. Package retention remains dry-run unless explicitly invoked with `--apply`.
