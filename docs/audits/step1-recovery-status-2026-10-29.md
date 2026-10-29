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


## Update — 2026-10-29 12:22 PKT: merged browser gate hardening and intermittent 500

### Current source and workflow state
- Current main: `55f3baa86cf4a2b0e53eea982633d38dbf981192` (`quality: enforce real HTTP status checks for browser navigations`). PR #905 merged as `ad86009823550281e07e2d62674db8582f85427b`; PR #907 merged as `55f3baa86cf4a2b0e53eea982633d38dbf981192`.
- On main SHA `55f3baa...`, these workflows passed: Test [#40834804056](https://github.com/mattari20/estudent-listing/actions/runs/40834804056), Laravel CI [#40834804191](https://github.com/mattari20/estudent-listing/actions/runs/40834804191), frontend build [#40834804087](https://github.com/mattari20/estudent-listing/actions/runs/40834804087), public monitor [#40834804051](https://github.com/mattari20/estudent-listing/actions/runs/40834804051), orchestrator [#40834804175](https://github.com/mattari20/estudent-listing/actions/runs/40834804175), and browser runtime [#40834804093](https://github.com/mattari20/estudent-listing/actions/runs/40834804093).
- Hostinger read-only diagnostics [#40834804010](https://github.com/mattari20/estudent-listing/actions/runs/40834804010) failed closed before finishing route/API/guest-denial checks. The live SSH script emitted the new `duplicate_rows` result, then its revision parity step hit Git history fetch errors `fatal: shallow file has changed since we read it`, `fatal: pack has 753 unresolved deltas`, and `fatal: fetch-pack: invalid index-pack output`. It classified `deployment_revision_status=APPLICATION_CODE_DRIFT_OR_UNVERIFIABLE` and stopped. This is a diagnostics-history/verification blocker; it is not proof the application changed or the API regressed. Do not bypass the parity gate.
- The most recent preceding Hostinger run [#40834020512](https://github.com/mattari20/estudent-listing/actions/runs/40834020512) succeeded and proved application-path parity, directory HTTP 200, API HTTP 200 `application/json`, valid paginator `total=0`, and guest denial. This new failed run means current parity must be freshly verified after fixing the shallow-history diagnostic; prior proof is useful but not a substitute for the latest run.

### Browser/API acceptance evidence
- PR #905's first clean Chromium run [#40833025109](https://github.com/mattari20/estudent-listing/actions/runs/40833025109) showed the directory and API contract valid, empty, and no syntax exception in that browser, but it did not reproduce the owner's reported browser exception and captured its own Playwright fetch as initiator.
- After PR #905 merge, browser run [#40834020536](https://github.com/mattari20/estudent-listing/actions/runs/40834020536) found an intermittent real failure: initial `GET /institutes/` returned HTTP 500 with title `500 Internal Server Error`, no empty-state text or controls; the API request in that same run remained HTTP 200 `application/json`, valid paginator `total=0`, and a later `GET /institutes/?per_page=12` returned HTTP 200 with correct empty state. The browser summary correctly failed on the initial HTTP 500. No precise exception matching that 500 was established.
- PR #907 made the existing browser workflow capture actual HTTP status from initial directory navigation, search navigation, directory reload, and page-size navigation rather than hard-coding 200. It also checks expected empty-state text and zero-card counts in search/page-size. It adds no application code, data, deploy, or database write.
- PR #907-head browser test [#40834605536](https://github.com/mattari20/estudent-listing/actions/runs/40834605536) passed. Post-merge browser run [#40834804093](https://github.com/mattari20/estudent-listing/actions/runs/40834804093) passed with all four navigation statuses 200, API HTTP 200 `application/json`, 609 bytes valid JSON, `data=[]`, `total=0`, expected empty state, and `pageErrorCount=0`, `targetSyntaxErrorCount=0`, `structuralFailures=[]`. This confirms a transient page 500 did not recur in that run; it does not erase the earlier observed 500 or prove the owner’s specific browser error is resolved.
- Repository `resources/js/app.js` only toggles password visibility; `resources/js/bootstrap.js` remains a placeholder. The directory is server-rendered. The API/route mismatch is fixed in source and the live subfolder endpoint works. Since clean Chromium hasn't reproduced the owner's JSON SyntaxError, the exact user-browser request/script remains `NOT VERIFIED`. If it continues, the remaining evidence needed is the exact failed Network request URL, status, content type/body, and initiator from that browser session.

### Migration, candidate rows and scheduler
- Latest Hostinger run #40834804010 still read-only observed `import_batches.duplicate_rows` present, column definition matches the intended migration, migration-ledger match count 0; `schema_equivalence=UNVERIFIED`, `migration_origin=UNKNOWN`, diagnosis `COLUMN_PRESENT_LEDGER_ABSENT`. This part was emitted before the unrelated parity/history-fetch failure. No schema/ledger mutation occurred.
- That run's canonical table counts were each zero: institutions, campuses, programs, and program_offerings all have `total_rows=0`, `active_rows=0`. This is intentional after the user removed dummy data. Do not recreate dummy rows or publish candidate imports to fill the page.
- Candidate rows #348–352 remain the Government College University Lahore duplicate-risk cluster across batches #19–23; each has active source evidence but no approved identity decision or promotion decision, and no canonical institution. The log suppresses candidate payloads and does not establish that their source payloads are identical. Next safe data action remains a source-evidence/identity/dependency review only.
- Hostinger scheduler command remains: `* * * * * cd /home/u537436044/domains/estudent.pk/public_html/institutes && /opt/alt/php85/usr/bin/php artisan schedule:run >> /dev/null 2>&1` with PHP 8.5.12. `crontab -l` remains unreadable; hPanel still needs owner confirmation that a job is or is not configured. No cron action occurred.

### Quality gates and Step 1 gate
- Main ruleset remains active for `refs/heads/main`, blocks deletion/non-fast-forward and requires only `test`; strict status checking false. The browser runtime is a separate CI check, not a required ruleset context. There are already extensive import/identity/readiness/promotion end-to-end tests, including `ImportLifecyclePublicationEndToEndTest.php`, and API empty-paginator coverage in `InstitutionApiTest.php`. Avoid duplicating these without identifying a coverage gap. Rule-set administration cannot be changed via this execution's available GitHub connector action; required-context governance remains a decision/manual administration blocker.
- Confirmed root cause repaired: API prefix mismatch under `/institutes/` (PR #902).
- Additional intermittent failure observed: root directory HTTP 500 on run #40834020536, not reproduced in runs #40834605536 or #40834804093; no request-level exception cause established. Preserve as intermittent production risk rather than “resolved.”
- The last Hostinger read-only job is blocked by failed shallow Git history fetch and must be made reliable before parity is certified again.
- Step 1 remains OPEN. Step 2 and later feature phases remain on hold until latest Hostinger route/API and source-parity gate passes, intermittent 500 is either explained or repeatedly cleared with status evidence, migration reconciliation is completed as a reviewed plan, candidate official-source provenance/duplicate risks are resolved, and owner browser exception is either reproduced and fixed or no longer reported with request-level evidence.

No production data/schema/migration-ledger/approval/promotion/publication/deletion/cron mutation was performed.
