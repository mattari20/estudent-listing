# Phase 1C — Final Production Verification

**Audit date:** 2026-11-05 (UTC)  
**Repository:** `mattari20/estudent-listing`  
**Production URL:** https://estudent.pk/institutes/  
**Target branch:** `main`  
**Audit mode:** GitHub repository and CI evidence; historical public/production workflow evidence; no new production access.

## 1. Final decision

**CONDITIONAL — INTEGRATED MAIN CI PASSES; ISOLATED MYSQL AND PRODUCTION VERIFICATION INCOMPLETE.**

The implementation packages are merged and the latest `main` revision has successful Test, Laravel CI/Pint, Project Controller, Command Orchestrator and frontend-build runs. The latest integrated `main` SHA has not yet received a current isolated-MySQL workflow run. Current production deployment parity, production schema/migration ledger, append-only trigger presence, monitoring checkpoint schema, production legacy NULL identity inventory and current browser behavior remain unverified.

A green GitHub run is not evidence that the Hostinger deployment or production database matches the repository.

## 2. Verified GitHub baseline

- **Latest verified `main`:** `4cf97e8e82a7e4cd673f99287be9b224f260a929`
- **Latest application-code revision:** `acad19aaf62f12933a5af05db974fa7b9ebab0af`
- GitHub compare from the application-code revision to current `main` reports one commit ahead, zero behind, and only three documentation files changed. Therefore the application-code tree at `acad19a` is unchanged by the later documentation-only `main` commit.
- Current `main` commit timestamp: 2026-11-05T01:28:50Z.
- Latest application-code merge timestamp recorded for PR #1013: 2026-11-05T01:22:00Z.

### PR merge inventory

Live GitHub PR metadata was checked on 2026-11-05.

| PR | Scope | Merge SHA | Merged at (UTC) |
|---|---|---|---|
| #1009 | Review-package row/evidence integrity | `b8add81c79f40ddf7891c24229cb8835c739e280` | 2026-11-05 00:30:09 |
| #1010 | Retrieval finalization recovery | `0e197c760202c628eb5065bc86207b045d1414f8` | 2026-11-05 01:00:56 |
| #1011 | Append-only audit retention | `18ed563ef34c64faab4d9f4b072b55730469cf87` | 2026-11-05 01:11:51 |
| #1012 | Monitoring checkpoint integrity/concurrency | `dcfe251ae7ef31b658e4c74cfa51632aed56eaad` | 2026-11-05 01:16:14 |
| #1013 | Read-only legacy NULL identity inventory | `acad19aaf62f12933a5af05db974fa7b9ebab0af` | 2026-11-05 01:22:00 |
| #1014 | Fail-closed issue-comment parser | `580cf62c2622f448e202cf261e9ebec8baebc72e` | 2026-11-05 01:07:16 |
| #1015 | Final Phase 1C documentation carrier | `4cf97e8e82a7e4cd673f99287be9b224f260a929` | 2026-11-05 01:28:51 |
| #1017 | Production workflow isolation/security | `7cd152e0626082ca112192b529f3148e68d1d960` | 2026-11-04 16:43:30 |

PR #1016 remains **OPEN and unmerged**, but is superseded by merged PR #1017 and is not an implementation dependency. Do not revive or merge #1016.

## 3. Integrated-main engineering CI

The following completed push-triggered runs all tested the exact current `main` SHA `4cf97e8e82a7e4cd673f99287be9b224f260a929`:

| Workflow | Run ID | Conclusion | Evidence |
|---|---:|---|---|
| Test | [41948801313](https://github.com/mattari20/estudent-listing/actions/runs/41948801313) | PASS | PHPUnit: 914 tests, 4,023 assertions |
| Laravel CI | [41948801331](https://github.com/mattari20/estudent-listing/actions/runs/41948801331) | PASS | PHPUnit: 914 tests, 4,023 assertions; Pint passed on 69 files |
| Project Controller | [41948801390](https://github.com/mattari20/estudent-listing/actions/runs/41948801390) | PASS | Controller validation and read-only inventory job completed |
| Command Orchestrator | [41948801338](https://github.com/mattari20/estudent-listing/actions/runs/41948801338) | PASS | Command job and supported controller checks completed |
| Frontend build | [41948801347](https://github.com/mattari20/estudent-listing/actions/runs/41948801347) | PASS | Build completed; assets already up to date |

The Test workflow log also reports successful production workflow isolation checks: 17 read-only workflows, 8 explicitly blocked operations, 34 workflow files total, retired administrator recovery workflow absent, and environment-specific production secret identifiers. These are repository-contract checks, not proof that GitHub environment secrets are currently configured or that production settings are correct.

### Isolated MySQL gap

No isolated-MySQL run was found on current `main` SHA `4cf97e8`. The current `isolated-mysql-approval-migration-validation.yml` triggers on pull requests and manual dispatch, not ordinary main pushes.

That workflow contains the required MySQL coverage, including:
- append-only audit retention;
- MySQL audit trigger naming, rollback removal, reinstallation and idempotency;
- monitoring checkpoint recovery and independent-process concurrency;
- legacy NULL identity inventory safety and MySQL coverage.

Earlier successful PR-head runs are historical evidence only; they are not a current integrated-main run. The integrated MySQL acceptance item remains **NOT VERIFIED on current main**.

## 4. Public website, API and browser evidence

### Historical public monitor

Run [#41820478375](https://github.com/mattari20/estudent-listing/actions/runs/41820478375) completed successfully on older SHA `92e748f7aeedb25d098775be6ff16abbbda23749` (2026-11-04T11:23:49Z).

Observed:
- `https://estudent.pk/institutes/` returned HTTP 200 with expected directory content.
- The page reported an empty directory; the workflow recorded `EMPTY_DATA`.
- No verified published institution was available for a detail-page test.

This is historical public HTTP evidence, not a current-main verification.

### Historical browser diagnostic

Run [#41770133572](https://github.com/mattari20/estudent-listing/actions/runs/41770133572) failed on older SHA `54bf5e6b9878080951b521b61a261a233f18322a` (2026-11-04T03:56:17Z).

Observed:
- Directory page: HTTP 200; empty-state heading and summary present.
- Institutions API: HTTP 200, `application/json`, valid Laravel paginator JSON, total 0.
- Page-size selection to 12: HTTP 200.
- Search navigation to `/institutes/search?q=COMSATS`: HTTP 404.
- Browser summary: zero page errors and zero target syntax errors, but overall FAIL because search navigation returned 404.

Current `main` source registers `GET /institutes/search` in `routes/web.php`; this does not prove that the currently deployed Hostinger code has the route. The older browser failure must not be silently treated as either a current failure or a current pass.

The current public monitor and browser diagnostic workflows are `workflow_dispatch` only and use the `production-readonly-diagnostics` environment with operation-specific confirmation. No current-main public workflow run was available in the Actions history reviewed for this audit. Direct page/API retrieval from this audit environment was also unavailable. Therefore current public HTTP, API, search, pagination and JavaScript behavior are **NOT VERIFIED**.

For a manual current-main dispatch, use target SHA `4cf97e8e82a7e4cd673f99287be9b224f260a929`, a safe operation reason, and the workflow-specific confirmation phrase:
- Public monitor: `AUTHORIZE-READONLY-DIAGNOSTICS-PUBLIC-SITE-MONITOR`
- Browser diagnostics: `AUTHORIZE-READONLY-DIAGNOSTICS-PUBLIC-BROWSER`

## 5. Hostinger workflow and production database boundary

The current `.github/workflows/hostinger-readonly-diagnostics.yml` was inspected together with its dispatch guard and `scripts/ops/phase-5b18-runtime-diagnostic.php`.

### Current controls and behavior

- Trigger: `workflow_dispatch` only.
- Job environment: `production-readonly-diagnostics`.
- Requires `target_sha`, `operation_reason`, and operation-specific confirmation `AUTHORIZE-READONLY-DIAGNOSTICS-HOSTINGER`.
- Dispatch guard requires the event to target `main`, requires the target SHA to equal the immutable dispatch SHA and the latest main SHA, and rejects unsafe reasons or incorrect confirmation.
- Uses environment-scoped Hostinger SSH secret identifiers; their values were not accessed or exposed.
- Default project path: `/home/u537436044/domains/estudent.pk/public_html/institutes`.
- Approved PHP executable: `/opt/alt/php85/usr/bin/php`.
- Performs runtime/file-parity and Artisan inspection, route registration checks, schedule inspection, selected schema/migration-ledger checks, and read-only data reports.
- The diagnostic PHP script declares `mutation_performed=false`, `source_requests_performed=false`, and `qualification_command_executed=false`; it checks source hashes, Composer autoload, Laravel bootstrap, command registration, bootstrap cache and file parity.
- The workflow creates temporary files/directories and writes a private runtime JSON report with restrictive permissions. It is therefore a **limited production filesystem write**, not a completely read-only operation.
- The inspected workflow contains no production migration execution or SQL schema/data mutation command.

### Existing report collision — preserve

Historical Hostinger run [#41789553048](https://github.com/mattari20/estudent-listing/actions/runs/41789553048), on older SHA `92e748f7aeedb25d098775be6ff16abbbda23749`, failed with:

`phase_5b18_report=EXISTS_REFUSED_TO_OVERWRITE`

The fixed destination is:

`storage/app/private/diagnostics/phase-5b18-artisan-runtime.json`

This is evidence that the destination already existed when that run executed. The workflow explicitly refuses to overwrite it. This audit did not access, delete, replace or modify that report. Do not dispatch the workflow against this fixed destination until the owner has reviewed the collision and explicitly approved an appropriate non-overwriting report strategy.

### Production schema coverage gap

The existing Hostinger workflow performs read-only checks for selected older objects, including:
- `import_batches.duplicate_rows` and its migration ledger entry;
- `institute_discovery_run_outcomes`;
- `institution_field_approvals` and its migration/schema prerequisites;
- `official_source_refresh_events` and its migration/index readiness.

It does **not** contain checks for the newly integrated:
- migration `2026_11_04_000014_enforce_append_only_audit_retention`;
- migration `2026_11_04_000015_create_monitoring_policy_run_checkpoints`;
- the 24 named MySQL append-only/identity-decision triggers;
- `monitoring_policy_runs` checkpoint foreign keys and `heartbeat_at`;
- `governed_execution_run_events` parent-retention behavior;
- the production legacy NULL identity-key inventory.

Therefore, even an authorized run of the existing workflow would not by itself satisfy all production schema/trigger acceptance criteria for PRs #1011–#1013. Do not infer these objects are present from repository CI.

### Dispatch decision

No Hostinger dispatch was performed. The required owner authorization for creating a private report file was not supplied, and the fixed report destination has a recorded exists/refuse-to-overwrite collision. The actual GitHub environment reviewer settings and current secret presence could not be read through the available repository API; only workflow binding and secret-reference contracts were verified.

If the owner later authorizes a new report strategy, the Hostinger dispatch must target current main SHA `4cf97e8e82a7e4cd673f99287be9b224f260a929`; the workflow will derive application-code revision `acad19aaf62f12933a5af05db974fa7b9ebab0af`.

## 6. Security and historical-data safeguards

Current-main CI passed the repository's production workflow isolation tests. The environment-binding contract permits only these three names:
- `production-readonly-diagnostics`
- `offering-gap-readonly-audit`
- `production-reset`

The test rejects a `production-operations` environment declaration and unknown production environment names. Eight production operations remain explicitly blocked pending owner authorization. The retired administrator recovery workflow remains absent. Production workflow definitions require manual dispatch, exact-main revision binding, safe operation reason and unique operation-specific confirmation.

Limits:
- The repository tests verify workflow definitions and guard contracts; they do not prove actual environment reviewer configuration or secret presence.
- AI extraction and the additional 20 HEC source profiles are recorded as disabled in the project tracker. Their live production settings were not queried.
- GCU historical rows #348–353 and batches #19–23 were not queried or modified in this audit. Their current production contents are not independently verified.
- No dummy production data was created.
- No Hostinger/SSH/hPanel access, production database query, production migration, deployment, trigger change, discovery, staging, approval, import, canonical promotion or publishing was performed by this audit.

## 7. Consolidated acceptance matrix

| Area | Status | Evidence / limitation |
|---|---|---|
| GitHub baseline | PASS | Current main `4cf97e8`; PR #1009–#1015 and #1017 merged; #1016 open but superseded |
| Engineering CI | PASS, with MySQL gap | Current-main Test, Laravel CI/Pint, Project Controller, Command Orchestrator and frontend build pass |
| Isolated MySQL | NOT VERIFIED on current main | Workflow is PR/manual-dispatch triggered; no run on `4cf97e8` |
| Deployment parity | NOT VERIFIED | No current Hostinger run; latest application-code SHA is `acad19a` |
| Database schema | NOT VERIFIED | No current production schema query |
| Migration ledger | NOT VERIFIED for #1011/#1012 | Current Hostinger workflow does not check migrations 000014/000015 |
| Audit triggers | NOT VERIFIED in production | Workflow has no 24-trigger inspection |
| Monitoring checkpoint | NOT VERIFIED in production | Workflow has no checkpoint/heartbeat/FK inspection |
| Public website | Historical PASS only | Older monitor run HTTP 200; no current-main public run |
| Public API | Historical PASS only | Older browser run returned valid JSON, total 0 |
| Search | NOT VERIFIED currently | Older browser run returned 404; current repository route exists |
| Pagination | NOT TESTABLE with zero records | Historical API response had total 0 and last_page 1 |
| Detail page | NOT TESTABLE — NO VERIFIED PUBLISHED RECORD | Historical public monitor found an empty directory |
| Browser runtime | NOT VERIFIED currently | Older browser run failed search navigation; no current-main browser run |
| Security | Repository contract PASS | 17 read-only workflows, 8 blocked operations, three allowed environment names, no production-operations declaration |
| Historical production data | NOT VERIFIED | No production query; GCU rows #348–353 and batches #19–23 were not touched |

## 8. Exact remaining work

1. Run the isolated MySQL validation against the current integrated application tree and retain the exact run ID/SHA. It must cover migration order, append-only retention, 24-trigger lifecycle, monitoring checkpoint concurrency and legacy inventory safety.
2. Run the current-main public monitor and browser runtime workflows with the operation-specific confirmation values above. Confirm current directory/API/search behavior and preserve the zero-record empty state; do not invent test records.
3. Do not rerun the current Hostinger diagnostic to its existing report path. The owner must first review the existing report collision and explicitly approve a non-overwriting private report strategy.
4. Before claiming production schema acceptance, add or authorize a read-only diagnostic that checks migrations 000014/000015, the 24 expected MySQL triggers, monitoring checkpoint columns/foreign keys/heartbeat, and the relevant migration-ledger entries. The current Hostinger workflow does not cover these objects.
5. Keep the production legacy NULL identity inventory separate and unauthorized until an approved production-safe read-only path exists. The merged #1013 command is restricted to isolated testing connections.
6. Refresh this report only from actual run outputs and observed production evidence.

## 9. Final disposition

**PHASE 1C: CONDITIONAL — NOT READY TO CLOSE.**  
**ENGINEERING IMPLEMENTATION: MERGED.**  
**CURRENT-MAIN PRIMARY CI: PASS.**  
**CURRENT-MAIN ISOLATED MYSQL: NOT VERIFIED.**  
**PRODUCTION VERIFIED: NO.**  
**PHASE 2: ON HOLD.**

The next closure gate is a current integrated MySQL proof plus authorized, current production verification of deployment parity, schema/migration ledger, triggers and public browser behavior. The existing Hostinger report collision must be resolved without overwriting or deleting the existing report.
