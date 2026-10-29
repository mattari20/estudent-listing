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


## Update — 2026-10-29 12:30 PKT: post-merge parity and browser results

### Current source and merged fixes

- Current main: fae32944e6880632fa96a82fb972afd362933338.
- PR #905 merged as ad86009823550281e07e2d62674db8582f85427b: clean Chromium runtime diagnostic for page/API/network/JS errors.
- PR #907 merged as 55f3baa86cf4a2b0e53eea982633d38dbf981192: browser gate now captures actual initial, search, reload and page-size navigation statuses and fails closed on non-200/incorrect empty-state output.
- PR #909 merged as fae32944e6880632fa96a82fb972afd362933338: Hostinger parity check no longer runs git fetch --unshallow on the live shallow checkout; bounded fetches + the same fail-closed application-path comparison remain.

### Latest main checks

- Test #40835662178 PASS: https://github.com/mattari20/estudent-listing/actions/runs/40835662178
- Laravel CI #40835662186 PASS: https://github.com/mattari20/estudent-listing/actions/runs/40835662186
- Frontend build #40835662183 PASS: https://github.com/mattari20/estudent-listing/actions/runs/40835662183
- Public monitor #40835662211 PASS: https://github.com/mattari20/estudent-listing/actions/runs/40835662211
- Command Orchestrator #40835662274 PASS: https://github.com/mattari20/estudent-listing/actions/runs/40835662274
- Hostinger Read-only Diagnostics #40835662204 PASS: https://github.com/mattari20/estudent-listing/actions/runs/40835662204

The latest Hostinger job reports deployed Git revision 55f3baa86cf4a2b0e53eea982633d38dbf981192 and expected app-path revision 4b95e2d945583f36ff07376ed51f2d2727c0b78d, with APPLICATION_CODE_MATCH. This is correct because the app code paths match; the intervening commits only changed workflows/quality gates. The earlier shallow-history error did not recur.

The same latest Hostinger run confirms GET /institutes/ returns HTTP 200. The subfolder API /institutes/api/v1/institutions?per_page=2 returns HTTP 200, application/json, valid paginator, data=[], total=0, current_page=1, last_page=1. Canonical institutions, campuses, programs and offerings each have zero rows and zero active rows; this is intentional after dummy-data removal.

### Intermittent 500 and browser JSON error

- Browser run #40834020536 captured an initial GET /institutes/ HTTP 500, title “500 Internal Server Error”, no rendered controls/empty state. The API in that same run still returned valid empty JSON; a later page-size navigation succeeded with HTTP 200. This was a genuine intermittent failure, not the empty-data state itself.
- Later browser run #40834804093 passed: initial directory/search/reload/page-size statuses all 200, API valid JSON with total=0, correct empty-state headings, no syntax errors and no structural failures.
- The latest Hostinger run also passed page/API checks. Thus the 500 has not recurred in subsequent sampled runs, but its exact Laravel exception has not been established; retain it as an intermittent risk rather than claim it can never recur.
- The user-reported SyntaxError: Unexpected end of JSON input still was not reproduced by clean Chromium. The captured API fetch was initiated by the diagnostic itself (Playwright evaluate frames, blank script URLs). Authored resources/js/app.js only toggles password visibility; the directory is server-rendered. The exact request/script in the owner’s browser remains NOT VERIFIED. If it persists, the evidence needed is the exact Network URL, status, content type/body and initiator from that browser session.
- Confirmed and fixed API route cause: API was registered at root /api/v1 while the app is served in /institutes. PR #902 set apiPrefix to institutes/api and added subfolder API/valid-empty-paginator tests. Current live endpoint is now verified.

### Migration, candidates, scheduler and permanent quality gates

- duplicate_rows remains COLUMN_PRESENT_LEDGER_ABSENT: expected column exists and its basic definition matches the migration, target migration ledger count is zero, schema_equivalence remains UNVERIFIED and migration_origin UNKNOWN. No schema/ledger write was performed.
- Candidate import rows #348–352, batches #19–23, all show Government College University Lahore and same shown slug; active source evidence exists, but no approved identity or promotion decisions and no canonical row. The sanitized report does not prove payloads are identical. Only source-provenance, duplicate and dependency review is appropriate; no auto-approval/promotion/publication/deletion.
- Existing ImportLifecyclePublicationEndToEndTest.php already covers evidence-backed candidate staging, explicit identity approval, promotion while private, separate explicit publication, listing and detail route in the test database. Do not duplicate it or use synthetic production records.
- Main ruleset remains active on refs/heads/main, blocks deletion and non-fast-forward, but requires only the test status. Laravel CI, build and Browser Runtime are separate green checks but are not required ruleset contexts. No ruleset-edit tool was available; adding independent required contexts requires privileged repository administration.
- Expected Hostinger cron: * * * * * cd /home/u537436044/domains/estudent.pk/public_html/institutes && /opt/alt/php85/usr/bin/php artisan schedule:run >> /dev/null 2>&1 (PHP 8.5.12). crontab remains unreadable. Owner must confirm hPanel jobs and recent executions; no cron was created/changed.

### Acceptance status and recommendation

| Criterion | Status | Evidence/blocker |
| --- | --- | --- |
| Directory HTML | PASS on latest runs; prior intermittent 500 remains a risk | Hostinger #40835662204, browser #40834804093; prior failure #40834020536 |
| Exact owner-browser JSON exception identified/resolved | NOT VERIFIED | Clean Chromium did not reproduce it; actual initiator remains unknown |
| Empty state without test data | PASS | Latest browser shows correct headings, zero cards, zero JSON exceptions |
| Subfolder API and paginator | PASS | Latest Hostinger API check: HTTP 200, application/json, total=0 |
| Search/page-size with empty data | PASS for exercised requests | Browser #40834804093, all actual statuses 200 |
| Import → approval → promotion → publication | PASS in existing test-database integration; NOT VERIFIED using a fresh real official-source record | Existing end-to-end test; production remains intentionally empty |
| Production program/campus relationships/detail data | NOT VERIFIED | No canonical published rows |
| Candidate provenance/duplicates | PARTIAL | Rows 348–352 need evidence/dependency review |
| duplicate_rows reconciliation | PARTIAL/BLOCKED | Ledger entry absent, schema equivalence and live origin unknown |
| Hostinger parity/API | PASS | Latest diagnostics #40835662204 |
| hPanel scheduler | PARTIAL/BLOCKED on owner confirmation | Correct command/path/PHP identified, hPanel status unresolved |
| Required quality governance | PARTIAL | Ruleset requires only test |
| Production acceptance | PARTIAL | Current endpoint is good; owner-browser error unlocated and prior 500 must stay tracked |

Repairing the existing foundation remains the best-supported decision; no evidence yet warrants a rebuild. The API path defect was localized and is fixed. Existing import/identity/publication boundaries are tested. Reassess rebuild only if clean test-DB lifecycle tests continue failing after targeted repairs or complete schema/history review shows irreconcilable drift. No rebuild started or authorized.

No production DB/data/schema/migration-ledger write, import, approval, promotion, publication, deletion, deployment or cron change was performed. Step 1 remains OPEN; Step 2 and feature phases remain HOLD.
