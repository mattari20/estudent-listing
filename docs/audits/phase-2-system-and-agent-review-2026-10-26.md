# Phase 2 System, Security, and Discovery Agent Review

**Prepared:** 2026-10-26  
**Scope:** Static review of the repository's route groups, read-only audit workflow, and scheduled discovery workflow. This is code/configuration evidence, not a live Hostinger execution result.

## 1. Import diagnostic change

PR #823 updates `institutes:import-integrity-audit` to:
- report `completed` as execution status rather than asserting data health;
- list failed batch IDs, entity/source type, status and timestamps;
- emit a coarse category inferred from stored batch notes without exposing the raw notes;
- mark each category as inferred, not a proven root cause;
- declare zero database mutations and test that rows/batches remain unchanged.

Validation for commit `37e17f3a1cec3d4b2b707bcc0544d859b4c34335`:
- Test workflow passed: https://github.com/mattari20/estudent-listing/actions/runs/40320992065
- Laravel CI passed: https://github.com/mattari20/estudent-listing/actions/runs/40320992217

## 2. Route security observations

Static review of `routes/web.php` shows public directory routes for listing, search, sitemap, robots and a throttled automation diagnostics endpoint. Operator operations are grouped behind `auth` and `institute.operator`, including import review, evidence capture, discovery run history, decisions, promotions, reversals, monitoring, and bulk actions. The route file also declares login/setup and password-reset endpoints.

Required follow-up checks:
- Confirm setup is unavailable or safely locked after initial bootstrap.
- Verify every state-changing operator action uses POST/PUT/PATCH/DELETE and CSRF middleware.
- Verify route-level role middleware is paired with record-level ownership/policy checks.
- Verify diagnostics output contains no credentials, private source payloads, or exception details.
- Run guest, ordinary authenticated user, operator, and administrator authorization tests for all mutation groups.
- Review throttling on expensive search/import/source-fetch paths and validate upload MIME/size limits.

This static pass does not certify that every controller and policy is safe. The existing feature suite includes guest-access regression tests for many operator endpoints; the full suite and route-to-role matrix still need a current-head run/review.

## 3. Scheduled discovery workflow observations

`.github/workflows/scheduled-institute-discovery.yml`:
- runs hourly and on workflow dispatch/push to the workflow file;
- uses read-only repository permissions and non-cancelling concurrency;
- resolves a target application-code revision and blocks on deployed revision mismatch or tracked working-tree changes;
- blocks scheduled AI extraction when a profile enables it, preserving the zero-extra-AI-cost requirement;
- checks canonical counts before/after discovery;
- runs bootstrap and source monitoring, but explicitly checks canonical counts remain unchanged;
- emits no automatic approval, promotion, publication, or migrations;
- requires an outcome ledger entry when monitoring policies were due.

This workflow can still create discovery outcomes/candidates and source snapshots. Those writes are intended staging/monitoring activity, not canonical publication. Before release, verify the latest scheduled run's result and ensure operator review is available for any generated candidate package.

## 4. Live deployment and fresh-import gates

Not yet proven by this static review:
- latest deployed revision matches the newly reviewed main revision;
- current Hostinger read-only integrity and public smoke checks pass after the reviewed changes;
- all current migrations/schema and indexes match code expectations;
- source verification has been completed for any proposed canonical data;
- a representative fresh import passes validation, preview, duplicate review, approval, idempotent promotion, and recovery without auto-publication.

Historical dummy-data counts are not acceptance targets. A new timestamped, revision-tagged pre-import baseline must be generated after reviewed code is deployed. Do not reset or delete data without a separate, reviewed cleanup plan.

## 5. Status

- Import audit diagnostic: implemented on PR #823 branch; CI passed.
- Audit plan: documented on PR #822 branch; CI for the latest documentation-only commit still requires confirmation.
- Route/security review: static observations documented; controller-level and live authorization verification remains.
- Discovery workflow: safety guards observed in code; latest live run still needs verification.
- Fresh import: not authorized/readiness not yet certified.
