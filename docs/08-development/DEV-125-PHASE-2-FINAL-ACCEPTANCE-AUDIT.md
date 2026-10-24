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


## Live acceptance recheck (24 October 2026)

### Verified from the latest main commit

- Main commit `d47afe9d2049c903e04052063e4020abea1a1702` passed all six post-merge workflows: Test, Laravel CI, frontend build, Step 3 evidence-register validation, public-site monitor, and Hostinger read-only diagnostics.
- Hostinger read-only diagnostics compared the deployed application paths with the intended application-code revision and reported `APPLICATION_CODE_MATCH`. The deployed Git HEAD was `d30a78949dd555759e880ed9f4bcb2de9fe692fc`; the expected application-code revision was `b5743b39f69a4588abcfe2ab9dab445b8c748194`. These hashes differ because the check compares application paths, not documentation-only commits.
- The latest diagnostics reported 19 institutions, 22 campuses, 85 programs, 55 program offerings, and 5 admission intakes. The successful governed discovery run on 23 October recorded identical before/after canonical counts and reported zero canonical mutations, no automatic approval/promotion/publication, and paid AI calls disabled.
- The current diagnostics reported 3 captured source snapshots, 2 active source-monitoring policies, and 2 completed source-retrieval runs.
- The Hostinger `crontab -l` check returned `missing_or_unreadable`; this is not proof that no scheduler exists, but Hostinger cron cannot be counted as verified from this check.

### Scheduler status — still an exit gate

- The scheduled GitHub Actions workflow is configured hourly and has executed on schedule.
- Its latest observed run on 24 October at 05:48 UTC failed closed with `BLOCKED_APPLICATION_CODE_REVISION_MISMATCH`: deployed application revision `6259c3dd4d016f23e67daf31a7eb10ba32da4104` did not match that run's target `8ca5e282b18bf4cea2440e481939af76d6d73f63`. The workflow explicitly reports that no source retrieval or database writes ran in that failed attempt.
- A later Hostinger diagnostic at 06:19 UTC reported application-code match against the then-current expected application revision. Therefore the mismatch appears stale relative to that later diagnostic, but a subsequent scheduled discovery run must succeed against the current revision before the scheduler gate is closed.
- An earlier scheduled run on 23 October at 22:08 UTC completed successfully with before/after canonical counts unchanged. That is positive evidence of the guarded runner, but it does not replace a successful run after the latest application-code revision was confirmed.

### Still unverified on the live host

1. Confirm the discovery-outcome migration is applied and the table is available in live schema/migration status.
2. Sign in as an authorized operator and load discovery run history; verify pagination and empty state.
3. Download a real valid CSV and manifest as an operator and confirm private/no-store and nosniff headers.
4. Verify guest access is denied on the live host for both history and package download routes.
5. Verify private package paths are outside the public web root and direct public access is denied.
6. Obtain a successful scheduled discovery run against the current application-code revision and verify its recorded outcome.
7. Reconfirm canonical before/after counts remain identical during that successful run.

### Exit decision

**Phase 2 remains OPEN.** Repository CI and the latest read-only application-revision check are green, but live operator acceptance and a current successful scheduled run are not yet independently verified. No production writes, migrations, promotions, publications, or package deletion were performed by this audit. Do not mark Phase 2 complete until every remaining live gate above has evidence.


## Critical update after PR #603 (24 October 2026, 06:25 UTC)

- The post-merge Hostinger Read-only Diagnostics run `40070109931` failed. Its log reports `deployment_revision_status=APPLICATION_CODE_DRIFT_OR_UNVERIFIABLE`, with deployed Git HEAD `1e280ccecd82537bcf42ba8da846999b87021466` and expected application-code revision `b5743b39f69a4588abcfe2ab9dab445b8c748194`.
- GitHub compare shows the expected revision is two application-code commits ahead of the deployed HEAD: `dc37f5219ccfdeaa1a9d11dd04a664736dbb7861` (live offering preflight connection) and `b5743b39f69a4588abcfe2ab9dab445b8c748194` (live campus identity in preflight).
- A prior controlled deployment run `40068100755` successfully checked out `dc37f5219ccfdeaa1a9d11dd04a664736dbb7861`, passed `php artisan --version`, and ran `institutes:offering-gap-preflight` in read-only mode. It did not deploy the later `b5743b39f69a4588abcfe2ab9dab445b8c748194` revision. A later diagnostic nevertheless observed the older deployed HEAD, so the deployment state must be re-established and verified rather than inferred from the earlier successful run.
- The controlled deployment workflow is intentionally `workflow_dispatch` only and requires `confirm_deployment=true` to change production application files. The available GitHub connector actions in this session can inspect runs and repository content but do not expose workflow dispatch. No attempt was made to bypass that deployment gate or change the workflow into an automatic production deploy.
- The failed diagnostic remained read-only and explicitly reports that no production records were changed. Do not run migrations or offering inserts as part of resolving this code-revision mismatch.

### Current exit decision

**Phase 2 remains OPEN and deployment drift is an active blocker.** The next required action is a controlled dispatch of `Controlled Hostinger Code Deployment` against the latest main application-code revision with `confirm_deployment=true`, followed immediately by a fresh Hostinger Read-only Diagnostics run. The deployment's own read-only offering preflight may report `BLOCKED_ROWS_REMAIN`; that is an evidence gate and must not be bypassed. After code alignment is proven, continue live migration, operator/download security, scheduler outcome, and canonical-count acceptance checks.
