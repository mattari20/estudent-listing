# Institutes Directory — Step 1 recovery status

**Updated:** 2026-10-29  
**Repository:** `mattari20/estudent-listing`  
**Current main target:** `4b95e2d945583f36ff07376ed51f2d2727c0b78d`  
**Status:** Step 1 remains open. Step 2 has not started.

## Confirmed PRs and CI

- PR #900 merged as `fb82aa6043b342819be84f71e2ddf448cfbceac5`; Test [#40827745368](https://github.com/mattari20/estudent-listing/actions/runs/40827745368) and Laravel CI [#40827745303](https://github.com/mattari20/estudent-listing/actions/runs/40827745303) passed.
- PR #901 merged as `c83f0162731a7efccda9cf1e8c6878697659d017`; Test [#40829679792](https://github.com/mattari20/estudent-listing/actions/runs/40829679792) and Laravel CI [#40829679788](https://github.com/mattari20/estudent-listing/actions/runs/40829679788) passed.
- PR #902 merged as `4b95e2d945583f36ff07376ed51f2d2727c0b78d`; post-merge Test [#40830834468](https://github.com/mattari20/estudent-listing/actions/runs/40830834468), Laravel CI [#40830834622](https://github.com/mattari20/estudent-listing/actions/runs/40830834622), and frontend build [#40830834508](https://github.com/mattari20/estudent-listing/actions/runs/40830834508) passed. Public Site Monitor [#40830834637](https://github.com/mattari20/estudent-listing/actions/runs/40830834637) and Command Orchestrator [#40830834514](https://github.com/mattari20/estudent-listing/actions/runs/40830834514) also passed.
- PR #902 sets `apiPrefix: 'institutes/api'` in `bootstrap/app.php`, aligns API feature tests to `/institutes/api/v1/institutions`, and tests a valid empty JSON paginator. This corrects the API namespace for the deployed `/institutes/` subfolder in source code.

## Live routing evidence and deployment blocker

- Previous pre-fix Hostinger run [#40829824507](https://github.com/mattari20/estudent-listing/actions/runs/40829824507) verified Laravel registered `api/v1/institutions`, `api/v1/institutions/nearby`, and `api/v1/institutions/{institution:slug}`, while candidate public URLs returned HTML 404. The root URL `/api/v1/institutions` fell through to the WordPress site.
- Immediately after PR #902, Hostinger diagnostic [#40830834506](https://github.com/mattari20/estudent-listing/actions/runs/40830834506) stopped at the deployment parity gate: deployed revision was still `c83f0162731a7efccda9cf1e8c6878697659d017`; expected application revision was `4b95e2d945583f36ff07376ed51f2d2727c0b78d`. This run did not test the new API prefix and must not be described as a failed deployed fix.
- Automated Live Data Integrity Audit [#40830834606](https://github.com/mattari20/estudent-listing/actions/runs/40830834606) likewise stopped because the target revision could not be reconciled with Hostinger's deployed Git metadata.
- The repository's Controlled Hostinger Code Deployment workflow is manual and defaults `confirm_deployment=false`. No production deployment action was performed in this execution. Therefore, live success of `/institutes/api/v1/institutions` is NOT VERIFIED until the approved deployment workflow has run and a fresh read-only probe confirms HTTP 200, `application/json`, and the JSON paginator schema.

## Empty data and browser exception

- Pre-fix live Hostinger observations [#40829824507](https://github.com/mattari20/estudent-listing/actions/runs/40829824507) and [#40830834506](https://github.com/mattari20/estudent-listing/actions/runs/40830834506) show `institutions`, `campuses`, `programs`, and `program_offerings` each have zero total and zero active rows. The owner confirmed dummy records were intentionally removed. This is the expected current database state, not a data deletion failure to repair.
- The root directory page is rendered server-side by `InstitutionController@index` and returns HTTP 200 with the empty-state markup. Its query only selects `institutions.status=active`.
- Repository `resources/js/app.js` handles password visibility only; `resources/js/bootstrap.js` is a placeholder. No directory API fetch/JSON parse is present in those app scripts. The browser `SyntaxError: Unexpected end of JSON input` remains NOT VERIFIED as resolved and has not been causally attributed to a particular browser request. The earlier root/subfolder API failures are proven separate symptoms; after deployment, repeat live API diagnostics and, if the browser exception persists, capture the exact Network request and initiator from DevTools.
- The two `unload` permissions-policy warnings are not currently linked to the empty dataset or JSON exception; low priority pending evidence.

## Candidate import audit evidence

The last row-level read-only trace in Hostinger diagnostic [#40829824507](https://github.com/mattari20/estudent-listing/actions/runs/40829824507) showed five candidate institution import rows:

| Import row ID | Batch ID | Name / slug | Status | Evidence / decisions |
| --- | ---: | --- | --- | --- |
| 348 | 19 | Government College University Lahore / `government-college-university-lahore` | candidate | Active source evidence present; no approved identity or promotion decision |
| 349 | 20 | Same name / slug | candidate | Active source evidence present; no approved identity or promotion decision |
| 350 | 21 | Same name / slug | candidate | Active source evidence present; no approved identity or promotion decision |
| 351 | 22 | Same name / slug | candidate | Active source evidence present; no approved identity or promotion decision |
| 352 | 23 | Same name / slug | candidate | Active source evidence present; no approved identity or promotion decision |

All five rows reported validated batches, active source ID 27, `match_state=new_candidate`, no validation-error fields, no identity decision, no approved promotion decision, and no canonical institution status. The campus import trace was empty. Since the canonical tables are empty, there is no current canonical record to point these rows at. The identical identity/slug across five batches is a duplicate-risk cluster requiring the existing cross-batch audit and source provenance review. This is not a claim that the five source contents are identical; payloads were deliberately excluded from the diagnostic. No candidate row has been approved, promoted, published, rejected or deleted during this execution.

The system's row-level audit and readiness services enforce these gates: batch validated, source active, explicit `approved_new` or `approved_match` identity decision, active source evidence, no open conflict, and canonical match handling. Source freshness/retrieval lineage is a further production-readiness gate. The safe workflow is official-source discovery/import → row-level verification and duplicate audit → reviewer decision → evidence and identity resolution → batch/readiness gates → controlled promotion/publication. Do not bypass or bulk approve this cluster.

## duplicate_rows schema/ledger incident

Pre-fix Hostinger diagnostics [#40829824507](https://github.com/mattari20/estudent-listing/actions/runs/40829824507) verified:
- `import_batches.duplicate_rows` exists as `int(10) unsigned`, NOT NULL, default `0`, position 11 after `candidate_rows` and before `received_at`.
- Column definition/position matches migration `2026_10_23_000001_add_duplicate_rows_to_import_batches_table`.
- Target migration file exists but exact migration-ledger match count is `0`; existing creation migration `2026_09_24_000001_create_import_batches_table` is in the ledger.
- Diagnosis: `COLUMN_PRESENT_LEDGER_ABSENT`; `schema_equivalence=UNVERIFIED`; `migration_origin=UNKNOWN`; no write requested or performed.
- Source-code history points to commit `556bfce3d9c381bfd50d38017ee0982356b0b2af` (PR #518, 2026-10-23) as the likely introduction of the column migration/code, but this does not establish how the live column was physically added.

Safe reconciliation plan remains read-only: wait until the deployed app-code revision is current, rerun the upgraded schema diagnostic, compare the complete live table definition (all columns/order/defaults/nullability/unsigned flags/indexes/FKs/engine/collation) against the original creation migration and all later migrations, inspect exact and related ledger entries and Git history, then present a narrow repair proposal with impact and rollback/verification. No ledger row insertion, migration rerun, column alteration, or other database mutation is authorized by this report.

## Scheduler

- Laravel `bootstrap/app.php` schedules `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly.
- Hostinger PHP executable/version recorded: `/opt/alt/php85/usr/bin/php`, PHP `8.5.12`.
- Expected every-minute scheduler command: `* * * * * cd /home/u537436044/domains/estudent.pk/public_html/institutes && /opt/alt/php85/usr/bin/php artisan schedule:run >> /dev/null 2>&1`.
- `crontab -l` returned exit 127 / unreadable. That is inconclusive for hPanel. User will check hPanel's existing job list; do not create/modify/delete a cron job until confirmed absent and exact command/path validated.

## Step 1 gate checklist

| Criterion | Status | Evidence / remaining gate |
| --- | --- | --- |
| 1. Directory page renders | PASS for HTML render; overall browser error unresolved | Public Site Monitor #40830834637 passed; user still sees JSON SyntaxError. |
| 2. Root cause of JSON error found and resolved | BLOCKED | Confirmed API route prefix mismatch in code; fix merged, but deployment not verified and exact browser initiator not captured. |
| 3. Verified published institutions render | BLOCKED / expected empty state | Canonical tables deliberately empty; no real records should be inserted until official verification and approval. |
| 4. Search and empty-state behavior | PARTIAL | Server-side query and empty-state tests exist; public search/API path smoke after deployment not verified. |
| 5. Pagination on empty dataset | PARTIAL | Unit test now asserts valid empty paginator; live API response after deployment pending. Public listing correctly has no pagination for zero records. |
| 6. Detail pages show correct records | BLOCKED | No canonical active institution exists for real public detail smoke. |
| 7. Campus/program links and relationships | NOT VERIFIED | Table counts are zero; previous integrity checks have no rows to assess. |
| 8. Candidate data risks documented | PARTIAL | Rows 348–352, batches 19–23 documented; official source content and duplicate relationships require a fresh full row-level evidence bundle. |
| 9. Migration/schema reconciliation plan | PARTIAL | Evidence-based safe plan documented; full schema equivalence and migration origin remain unknown. |
| 10. Scheduler/deployment checks | BLOCKED | Expected cron command known; hPanel state not yet verified. Production code revision mismatch is proven. |
| 11. Required CI | PASS | Post-merge Test #40830834468, Laravel CI #40830834622, frontend build #40830834508 all passed. |
| 12. Production acceptance recorded | PARTIAL | HTML smoke passed, but API acceptance and deployed-code parity for new route fix are unverified. |

## Safety boundary / next actions

1. Have the owner perform/authorize the controlled deployment via the existing workflow when ready; until that action is explicitly confirmed, do not deploy.
2. After deployment, run the existing read-only diagnostic and verify `/institutes/api/v1/institutions` returns valid empty JSON pagination.
3. If JSON parse error persists in the browser after the API path succeeds, capture the exact Network request and initiator; use that evidence for a focused code fix.
4. Run existing private row-level duplicate and source-readiness diagnostics after deployment and produce a provenance report for rows 348–352 without changing them.
5. Rerun live migration metadata after code alignment and prepare the reconciliation proposal, still without database writes.
6. Confirm hPanel cron list and recent execution history manually; the rest can remain automated.

No Step 2 work has begun.


## Update — 2026-10-29: browser runtime investigation and current quality gates

### Current repository state

- Current `main` is `7ef402d14338ff0b51c0f395a1fd3ad9d4c5b0ed` (`fix: remove duplicate commands from Hostinger read-only diagnostics`). PR #905 is open against that exact base, head `2f7868ef7f2d00a87e49d5fdb5d0644e8bf562bd`.
- PR #905 contains only two added files: `.github/workflows/public-browser-runtime-diagnostics.yml` and `tests/Feature/PublicBrowserRuntimeDiagnosticsContractTest.php`. No application/runtime code is changed by PR #905 itself.
- Required main ruleset is active on `refs/heads/main`: deletion and non-fast-forward blocked; required status context `test`; strict status checking disabled. PR #905 has no submitted review, no inline threads and no requested reviewers at this snapshot.
- On PR #905 head, Test [#40833025055](https://github.com/mattari20/estudent-listing/actions/runs/40833025055), Laravel CI [#40833025051](https://github.com/mattari20/estudent-listing/actions/runs/40833025051), and Public Browser Runtime Diagnostics [#40833025109](https://github.com/mattari20/estudent-listing/actions/runs/40833025109) all completed successfully.

### Browser runtime output (actual job log)

Public Browser Runtime Diagnostics job [#123311323890](https://github.com/mattari20/estudent-listing/actions/runs/40833025109/job/123311323890) produced:

- `directory_initial`: URL `https://estudent.pk/institutes/`, HTTP 200, title “Institutes Directory | eStudent.pk”, summary “No institutions to display”, heading “No institutions available”, 0 institution cards, search and page-size controls present.
- Browser-originated API GET: `https://estudent.pk/institutes/api/v1/institutions?per_page=2`. Response HTTP 200, `application/json`, 609 bytes, valid JSON with paginator keys (`data`, `total`, `current_page`, `last_page`, and links); `dataCount=0`, `total=0`, page 1 of 1. This is a valid empty collection, not an empty response body, malformed JSON or an HTML error.
- Its `Network.requestWillBeSent` record shows initiator type `script`, but the captured frames are the diagnostic itself (`eval`/Playwright `evaluate`) with blank script URLs. Thus it proves a browser-originated API request works, but does not capture the user's failing request/initiator.
- `search_get`: `https://estudent.pk/institutes/search?q=COMSATS`, HTTP 200, expected empty heading “No matching institutes found”, 0 cards.
- `page_size_12`: `https://estudent.pk/institutes/?per_page=12`, HTTP 200, correct empty-state text, 0 cards, search/page-size controls present.
- Final diagnostic summary: `pageErrorCount=0`, `targetSyntaxErrorCount=0`, `targetErrors=[]`, `structuralFailures=[]`, `conclusion=PASS`.
- Interpretation: this clean Chromium run did not reproduce `Unexpected end of JSON input`. It must not be reported as resolution in the owner's browser. The precise script/request causing the owner-reported error remains **NOT VERIFIED**.

### API prefix root cause and deployment distinction

- Confirmed source mismatch was that Laravel had registered API routes under `api/v1/...` while the Laravel application is served under the `/institutes/` subfolder. The root API probe returned WordPress HTML 404, and the subfolder API path also returned HTML 404 before the source fix.
- PR #902 merged as `4b95e2d945583f36ff07376ed51f2d2727c0b78d`; it sets `apiPrefix: 'institutes/api'` in `bootstrap/app.php` and aligns API tests to `/institutes/api/v1/institutions`, including an empty JSON paginator regression test. Test [#40830834468](https://github.com/mattari20/estudent-listing/actions/runs/40830834468), Laravel CI [#40830834622](https://github.com/mattari20/estudent-listing/actions/runs/40830834622), and frontend build [#40830834508](https://github.com/mattari20/estudent-listing/actions/runs/40830834508) passed.
- The post-merge Hostinger diagnostic [#40830834506](https://github.com/mattari20/estudent-listing/actions/runs/40830834506) stopped at revision parity: Hostinger still reported `c83f0162731a7efccda9cf1e8c6878697659d017`, whereas expected code revision was `4b95e2d945583f36ff07376ed51f2d2727c0b78d`. It did not test the new deployed API route.
- A later public-browser run independently confirms that the public subfolder API endpoint currently responds as valid empty JSON, but the deployment workflow's application-code parity still must be explicitly rechecked before claiming the entire app revision is aligned. The controlled deployment workflow remains opt-in (`confirm_deployment=false`) and no production deployment was executed in this investigation.

### Quality-gate assessment

Already present and useful:
- Route and API feature tests cover public subfolder routes and a valid empty paginator.
- A clean-browser runtime workflow checks the page's empty state, makes a guarded GET to the public API, exercises search and page-size navigation, logs request/response metadata, and fails on uncaught target syntax errors and invalid API contracts.
- Hostinger read-only workflow checks public API status/content-type/paginator shape, canonical row/status counts, relationship integrity, route registration, migration diagnostics and scheduler evidence.
- Controlled deployment workflow computes the application-code revision, refuses dirty tracked/untracked work trees, compares application-path content rather than full commit hash, provides a dry-run by default, verifies `php artisan --version`, and performs a read-only offering-gap preflight. The deployment is intentionally manual and no deploy occurred here.
- Existing import/readiness/source/identity/promotion audit mechanisms exist, but current candidate cluster still requires exact official-source row-level provenance and duplicate review; do not auto-approve or promote it.

Missing or incomplete:
- Permanent main-branch protection currently requires only `test`; Laravel CI, frontend build, and public-browser runtime checks are not required merge gates. This is a systemic process gap. Do not change repository rules automatically without separately checking GitHub permissions and agreeing the required contexts.
- Browser diagnostic did not observe a user-originated exception; ask for a sanitized DevTools request/initiator capture only if it continues after deployment parity is verified.
- The clean browser run's request initiator is the diagnostic fetch (blank URL frames), not a discovered production script. Do not implement generic JS changes without the exact failing request.
- Live detail/campus/program functional acceptance remains impossible with zero canonical published rows; test fixtures can and should cover these paths in test DB, but never seed fake production rows.

### Step 1 statuses after the browser runtime result

| Criterion | Status | Latest evidence |
| --- | --- | --- |
| Page renders correctly | PASS for HTML and intended empty-state rendering | Public browser run #40833025109 |
| JSON exception is resolved and covered by regression tests | BLOCKED / NOT VERIFIED for user's browser | Clean Chromium saw 0 syntax errors, but owner-reported initiator remains unknown |
| Empty state works without dummy data | PASS in clean Chromium | Search and page-size navigation displayed expected empty states |
| API routing and response contract | PASS for current live endpoint response; deployment code parity still separately needs verification | Browser API response: HTTP 200, application/json, valid paginator, total 0 |
| Search/pagination with empty data | PASS for exercised GET search and page-size; pagination has no page links when total=0 | Browser summaries #40833025109 |
| Verified real records pass import/review/promotion | NOT VERIFIED end-to-end | Current canonical records intentionally empty; candidate rows #348–352 remain unapproved |
| Institution/campus/program relationships | NOT VERIFIED in production; no rows to exercise | Read-only canonical counts zero |
| Migration/schema inconsistency plan | PARTIAL | Column present, definition/position match; ledger row absent; full schema equivalence and origin unknown |
| Required workflows and scheduler | PARTIAL | CI passes; hPanel cron presence unresolved; controlled deployment is opt-in; prior post-fix parity check failed |
| Production acceptance and source parity | PARTIAL | Browser endpoint responds; most recent Hostinger parity check still requires a fresh run |

### Recommendation — repair versus rebuild

Evidence currently favors **repairing the existing foundation in focused PRs**, not a rebuild:
- The confirmed API mismatch was localized to Laravel API prefix configuration and has a focused, tested source correction (PR #902).
- The public empty state behaves correctly under a zero-record database, which is the intended testing state.
- The candidate/import system already has source evidence, identity decision, review, promotion event and readiness gates; the immediate risk is data provenance/repeat-import governance, not proven need to replace the entire system.
- There are real cross-cutting quality concerns: production revision parity, critical checks not required by the main ruleset, the unverified browser exception from the owner's browser, and the missing migration-ledger entry. These require stronger gates and explicit operator decisions, but no evidence yet proves core architecture is irreparable.
- Reassess rebuild only if a targeted import-to-publication test in a clean test database repeatedly fails after focused fixes, or if schema integrity review shows incompatible migration history that cannot be reconciled safely. No rebuild is authorized or started.

No production database/data/schema or cron mutation was performed. Step 1 remains open; Step 2 feature work and later feature phases remain on hold.
