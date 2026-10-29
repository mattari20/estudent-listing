# Step 1 root-cause, quality gates and repair-vs-rebuild assessment
**Date:** 2026-10-29  
**Repository:** `mattari20/estudent-listing`  
**Audited main at assessment start:** `c5838e3d87574ca9761f07e0ca9755727f9aa4d7`  
**Status:** Step 1 remains OPEN; Step 2 and feature phases remain on hold.

## Executive finding

**Recommendation: repair the existing foundation in focused, evidence-driven steps; do not rebuild yet.**

The investigation has proven and fixed one localized integration defect: Laravel API routes were registered at the domain root, but the application is deployed under `/institutes/`. The correct route prefix is now `institutes/api` and the live endpoint at `https://estudent.pk/institutes/api/v1/institutions` has returned HTTP 200, `application/json`, a valid Laravel paginator, `data=[]`, `total=0`. The empty canonical dataset is intentional after dummy/test data removal.

The original browser error `SyntaxError: Unexpected end of JSON input` is **not yet attributed to a specific script/request in the owner's browser**. Clean Chromium did not reproduce it. However, a separate clean-browser run captured one initial HTTP 500 from `/institutes/`; subsequent navigation and read-only diagnostics passed. The 500's exact application exception remains unverified. This is a reliability risk, not evidence that valid empty API JSON is malformed.

The remaining blockers are not a reason to immediately replace the architecture: live migration provenance/schema equivalence is unresolved; the current candidate cluster lacks approved identity/promotion decisions; hPanel cron presence is unverified; and the main ruleset requires only one test context. These are important operational/governance gaps that can be tightened within the current Laravel system.

## Current repository and PR state

- `main` at review start: `c5838e3d87574ca9761f07e0ca9755727f9aa4d7`, [commit](https://github.com/mattari20/estudent-listing/commit/c5838e3d87574ca9761f07e0ca9755727f9aa4d7).
- PR #900 merged: `fb82aa6043b342819be84f71e2ddf448cfbceac5`; root/subfolder API probing.
- PR #901 merged: `c83f0162731a7efccda9cf1e8c6878697659d017`; read-only Laravel route registration evidence.
- PR #902 merged: `4b95e2d945583f36ff07376ed51f2d2727c0b78d`; configured `apiPrefix: 'institutes/api'` and tests public subfolder API routes.
- PR #905 merged: `ad86009823550281e07e2d62674db8582f85427b`; Chromium runtime/network initiator diagnostic.
- PR #907 merged: `55f3baa86cf4a2b0e53eea982633d38dbf981192`; captures actual navigation statuses and fails closed on wrong HTML status/empty-state.
- PR #909 merged: `fae32944e6880632fa96a82fb972afd362933338`; replaced unsafe in-place git unshallowing with bounded read-only revision fetch/parity.
- PR #910 merged as docs update commit `c5838e3d87574ca9761f07e0ca9755727f9aa4d7`; records the preceding evidence.
- PR #899 remains open but is stale/superseded and should not be merged: it documents the older API-path state. PR #906 and #908 are closed without merge because later reports superseded them.
- At audit time the following main-head checks on `c5838e3d87574ca9761f07e0ca9755727f9aa4d7` passed: Test [#40836190822](https://github.com/mattari20/estudent-listing/actions/runs/40836190822), Laravel CI [#40836190817](https://github.com/mattari20/estudent-listing/actions/runs/40836190817), frontend build [#40836190809](https://github.com/mattari20/estudent-listing/actions/runs/40836190809), Public Site Monitor [#40836190848](https://github.com/mattari20/estudent-listing/actions/runs/40836190848), Project Controller [#40836190806](https://github.com/mattari20/estudent-listing/actions/runs/40836190806), and Command Orchestrator [#40836190870](https://github.com/mattari20/estudent-listing/actions/runs/40836190870). Hostinger diagnostics [#40836190864](https://github.com/mattari20/estudent-listing/actions/runs/40836190864) failed closed at the revision parity check because live Git shallow metadata changed/was not comparable. The preceding Hostinger diagnostic [#40835662204](https://github.com/mattari20/estudent-listing/actions/runs/40835662204) passed and verified app-path parity, route registration and the valid empty paginator.
- Active main ruleset [#25936221](https://github.com/mattari20/estudent-listing/rules/25936221) protects `refs/heads/main`, prevents deletion and non-fast-forward updates, but requires only the `test` context. Laravel CI, frontend build, and Public Browser Runtime Diagnostics are not mandatory ruleset contexts. This review cannot change privileged ruleset settings; repository administration is needed to make critical checks required.

## Confirmed API root cause

### Evidence
- Before the fix, `routes/api.php` declared versioned endpoints while `bootstrap/app.php` relied on Laravel's default API prefix. The app's public routes and directory live under the `/institutes` subfolder.
- Hostinger route-list diagnostics showed Laravel registered `api/v1/institutions`, `api/v1/institutions/nearby`, and `api/v1/institutions/{institution:slug}`, not the required subfolder paths.
- Live root and subfolder candidate URLs returned HTML 404 before the route prefix correction.
- PR #902 changed the Laravel routing configuration to `apiPrefix: 'institutes/api'`, updated API tests to the real public path, and added an empty-paginator test.
- Post-fix Hostinger read-only diagnostics run [#40835662204](https://github.com/mattari20/estudent-listing/actions/runs/40835662204) returned `/institutes/api/v1/institutions?per_page=2` as HTTP 200 with JSON paginator metadata; `data=[]`, `total=0`, `current_page=1`, `last_page=1`. The route list included the `institutes/api/v1/institutions` endpoint family.

### Conclusion
The **API path mismatch is confirmed and the code correction is merged and live-verified** in the successful Hostinger run. This does not prove that it caused the specific SyntaxError seen in the owner's browser.

## Browser JSON exception and intermittent HTTP 500

- Public Browser Runtime Diagnostics run [#40833025109](https://github.com/mattari20/estudent-listing/actions/runs/40833025109) passed. It observed the public directory HTTP 200, correct empty-state headings, zero cards, and a browser-originated fetch to `/institutes/api/v1/institutions?per_page=2` with HTTP 200, `application/json`, 609-byte valid JSON, `dataCount=0`, `total=0`. No uncaught SyntaxError was recorded.
- Browser run [#40834020536](https://github.com/mattari20/estudent-listing/actions/runs/40834020536) failed because the initial navigation to `/institutes/` returned HTTP 500 with title `500 Internal Server Error`; no normal directory controls/empty state were present. In that same diagnostic, the API still returned valid empty JSON, and a later navigation to `/institutes/?per_page=12` returned HTTP 200 with the expected empty state. This is an actual intermittent server failure, but no exception class/stack has yet tied it to a specific application cause.
- Browser run [#40834804093](https://github.com/mattari20/estudent-listing/actions/runs/40834804093) later passed with real statuses captured for initial page, search, reload and page-size navigation (all 200), expected empty-state headings, zero cards and no target syntax error.
- `resources/js/app.js` only handles password-visibility toggles; `resources/js/bootstrap.js` is a placeholder, and the directory view is server-rendered. The runtime request frames captured by clean Chromium were from the diagnostic's own page evaluation, not a production app script. Therefore the exact browser error initiator remains UNKNOWN.
- Do not add a JSON parsing workaround to frontend code without locating the actual request/script. The current canonical empty API response is valid JSON and must remain a valid empty paginator.

## Data-loading and publication state

- The root listing uses `InstitutionController@index` and selects only `institutions.status='active'`. `resources/views/institutes/index.blade.php` renders the count and empty-state server-side. The normal empty state is expected and tested when no active canonical institutions exist.
- Latest successful Hostinger counts reported total/active rows = 0 for institutions, campuses, programs and program_offerings. This agrees with the owner's intentional dummy-data removal. No production rows were fabricated or reinserted.
- Search has a separate GET route/controller and applies the shared query/filter services. Browser tests have exercised the zero-result search state and empty page-size navigation; tests cannot establish real institution detail/program/campus behavior while the canonical tables remain empty.
- Existing `tests/Feature/ImportLifecyclePublicationEndToEndTest.php` covers staging an evidence-backed candidate, explicit identity decision, promotion while still private, separate publication, and public listing/detail rendering in the test database. It uses synthetic fixture names and `example.test` evidence by design; it is not source verification for production records.
- Live read-only import trace found institution candidate rows IDs **348–352**, batches **19–23**, each showing Government College University Lahore and slug `government-college-university-lahore`. Each was `candidate`, batch `validated`, source ID 27/status active, active source evidence present, zero validation-error fields, no approved identity decision, no approved promotion decision, and no canonical institution status. No campus import rows were found in that trace. Payload details were intentionally not logged, so identical display name/slug demonstrates a duplicate-risk cluster, not proof of identical source payloads.
- Required safe handling is official-source provenance review per row and batch, comparison of payload fingerprints/fields, identity decision review, dependency mapping, then a human-approved row-level disposition. Do not bulk approve, promote, publish, reject, or delete these rows without that report.

## Migration/schema-ledger incident

- Live `import_batches.duplicate_rows` exists with the target basic contract: unsigned integer, not null, default zero, in the expected position immediately after `candidate_rows`. The target migration file exists.
- The original table-creation migration `2026_09_24_000001_create_import_batches_table` does not define `duplicate_rows`. The later migration is `2026_10_23_000001_add_duplicate_rows_to_import_batches_table`. Git history points to commit `556bfce3d9c381bfd50d38017ee0982356b0b2af` as a likely repository introduction of the column migration/code; that is not proof of the historical live write.
- The exact target migration's live ledger count is zero. Latest read-only diagnosis remains `COLUMN_PRESENT_LEDGER_ABSENT`, `column_definition_equivalence=MATCH`, `schema_equivalence=UNVERIFIED`, `migration_origin=UNKNOWN`.
- Do not insert a ledger row or rerun migration. Reconciliation requires full live metadata comparison (all columns/order/types/defaults/nullability/unsigned flags/indexes/FKs/table engine/collation), complete relevant migration and Git history, ledger inventory, and a narrowly scoped proposal with impact, rollback and independent verification. No schema or ledger write is authorized.

## Deployment and scheduler gates

- Successful run [#40835662204](https://github.com/mattari20/estudent-listing/actions/runs/40835662204) confirmed the deployed application's code paths aligned with revision `4b95e2d945583f36ff07376ed51f2d2727c0b78d` and validated the API endpoint. Later docs-only `main` commits do not require production deploy.
- Run [#40836190864](https://github.com/mattari20/estudent-listing/actions/runs/40836190864) failed at revision parity with `fatal: shallow file has changed since we read it`. This is an infrastructure/deployment metadata check limitation and correctly fails closed; it is not evidence that the API fix regressed.
- Hostinger PHP executable/version: `/opt/alt/php85/usr/bin/php`, PHP 8.5.12. Expected Laravel cron command, if hPanel confirms no existing scheduler job: `* * * * * cd /home/u537436044/domains/estudent.pk/public_html/institutes && /opt/alt/php85/usr/bin/php artisan schedule:run >> /dev/null 2>&1`. Laravel schedules `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly inside `bootstrap/app.php`. SSH `crontab -l` returned 127/unreadable; this does not prove hPanel has no configured job. Owner will verify the hPanel job list and recent execution evidence. Do not add or alter jobs without confirmation.

## Permanent quality gates: current vs recommended

| Gate | Existing evidence | Gap / next safeguard |
| --- | --- | --- |
| Route/API contract | Laravel API feature tests; subfolder URL regression in PR #902 | Add explicit route-list contract covering all public API routes, and CI checks that production URLs match `apiPrefix`. |
| Empty/invalid/error response | API empty paginator test; clean Chromium API JSON summary | Keep production JSON response strict; any frontend consumer must read text/status/content-type safely and fail visibly on invalid JSON, not silently coerce errors to empty data. Add tests once a real app-owned JSON consumer is identified. |
| Browser runtime/HTTP | Public Browser Runtime Diagnostics, actual statuses for initial/search/reload/page-size, network initiator/body summary | Make this check a required branch/ruleset check after privileged admin adjustment; monitor intermittent 500 with bounded retry evidence but retain the first error. |
| Import → review → promotion → publication | `ImportLifecyclePublicationEndToEndTest` covers the main lifecycle | Keep and extend focused tests for duplicate-cluster conflicts, missing/stale source evidence, explicit identity decisions, failed promotion, and publish denial when prerequisites fail; do not duplicate the happy-path test. |
| Migration/schema | Guarded duplicate_rows workflows and read-only schema checks | Add a CI test proving migration contract + a scheduled read-only live diagnostic; continue to fail closed on ledger/schema ambiguity. |
| Deployment | Controlled manual deploy; parity check; post-merge HTTP/page/API checks | Keep the public subfolder URL in post-deployment smoke; require API status, content type, paginator keys and revision parity before declaring deployment accepted. Do not auto-deploy from this documentation work. |
| Quality governance | Main ruleset currently requires only `test` | Request repo-admin to require `test`, Laravel CI, build and browser-runtime check contexts. No ruleset mutation tool is available in this workflow. |

**Avoid creating a competing monitoring stack.** Improve `.github/workflows/hostinger-readonly-diagnostics.yml`, the existing `public-browser-runtime-diagnostics.yml`, and current route/import/schema tests. A dedicated deployment alert is needed only if the existing Project Controller/Hostinger workflow cannot own the gate.

## Repair vs rebuild assessment

| Area | Evidence | Classification | Implication |
| --- | --- | --- | --- |
| Application architecture/module boundaries | Laravel routing, controllers, Eloquent models, query/filter services, explicit import/identity/promotion/publication services and existing feature workflows/tests | Coherent enough to repair | No evidence yet of a fundamental architecture failure. |
| API/subfolder integration | Specific missing `apiPrefix` for deployed subfolder; localized change + regression test + live route/API verification | Localized, fixed | Strong case for repair rather than replace. |
| Empty directory | Canonical tables intentionally contain zero rows; listing filters to active institutions | Expected data state | Do not treat as broken database or repopulate synthetic data. |
| Browser JSON SyntaxError | Not reproduced in clean Chromium; exact initiating script/request not captured | Unresolved incident | Requires owner-browser Network/initiator evidence if it persists. Do not make speculative frontend changes. |
| Intermittent HTTP 500 | One real initial navigation 500 followed by successful same-run page-size navigation and later full pass; exact exception missing | Reliability defect not localized | Continue error-fingerprint correlation / narrow repeat smoke in existing diagnostics. |
| Import/duplicate/approval/publication | Strong gating and happy-path lifecycle integration test; five repeated-name candidates awaiting per-row source/duplicate review | Governance/evidence gap | Repair through row-level report + negative tests; no automatic promotion. |
| DB migration ledger | Live column matches target basic definition/position, but ledger entry absent and full equivalence/origin unverified | Operational integrity blocker | Complete a full read-only schema/ledger comparison; do not write ledger yet. |
| Deployment checks | Application parity/read-only API check passed once; later run failed because Git shallow metadata changed | Diagnostics resilience risk | Keep bounded/fail-closed metadata handling; never equate a tooling error with a proven code regression. |
| CI quality gates | Multiple workflows pass, but branch ruleset only requires `test` | Governance gap | Require critical checks through repository-admin ruleset changes. |

### Decision
Repair is more proportionate than rebuild. The API defect was clearly localized and has been fixed. Existing lifecycle and route/test boundaries support continued focused repair. Rebuild becomes defensible only if future testable evidence shows unrecoverable schema drift, inability to guarantee migration correctness after full metadata review, repeated failures across independent core modules despite targeted fixes, or an architecture that cannot enforce import→approval→publication invariants. None of those conditions has been established.

## Step 1 status at this assessment

- **PASS:** page renders on latest successful sampled runs; expected empty state is rendered with zero records; public subfolder API returns a valid empty JSON paginator in the successful Hostinger run; API-prefix fix has regression tests; core import/publication happy-path integration test passes; main Test, Laravel CI, frontend build and public monitor have passed.
- **PARTIAL / NOT VERIFIED:** exact owner's browser SyntaxError initiator; cause of one intermittent directory HTTP 500; row-level official-source/duplicate/dependency verification for candidate rows 348–352; full schema equivalence and migration origin.
- **BLOCKED:** current Hostinger parity workflow run on main `c5838e3d87574ca9761f07e0ca9755727f9aa4d7` failed closed due to shallow metadata; hPanel scheduler job presence/execution needs owner confirmation; production real-record search/detail/relationship acceptance is impossible with intentional zero canonical rows.
- **Step 2:** remains on hold until Step 1 acceptance gates and blockers are reconciled or explicitly accepted by owner.

## No-mutation declaration

This assessment itself makes no production database/schema/migration-ledger writes, data changes, imports, approvals, promotions, publication, deletion, deployment or cron change. Candidate records remain untouched. No dummy production records were created.


## Post-merge check update — 2026-10-29 12:40 PKT

The report was merged through PR #912 as `1ce7830881adc82b055d4e388fffc10c178c69a8`. Post-merge main checks Test #40836856952, Laravel CI #40836856933, frontend build #40836856938, public monitor #40836856947, Project Controller #40836856927, and Command Orchestrator #40836856924 completed successfully. Hostinger Read-only Diagnostics #40836856923 failed closed during the revision check: logs report `fatal: Unable to create .../.git/shallow.lock: No such file or directory` followed by `not a git repository`. The same run nevertheless showed Laravel route registration at `institutes/api/v1/institutions` and the known `duplicate_rows` diagnosis, but its API acceptance should not be treated as complete because the workflow stopped at its revision gate.

The immediately preceding successful Hostinger run #40835662204 remains the last clean live acceptance of revision parity and the public API contract. The later failure is a diagnostics Git-metadata/path issue on Hostinger, not evidence of a changed API behavior. Keep the diagnostic fail-closed and treat the current parity result as BLOCKED until its Git reference handling is corrected through a focused PR. Do not rewrite the live repository's .git metadata or run deploy/migration/data mutations to work around it.


### Follow-up documentation PR #913

PR [#913](https://github.com/mattari20/estudent-listing/pull/913) adds no application code. It records Hostinger run #40836856923's fail-closed Git metadata error and preserves run #40835662204 as the last clean live acceptance. The PR's base is now behind main due to concurrent workflow/docs commits, so it must not be merged unchanged; reconcile it to the latest main or create a fresh documentation-only follow-up. Do not force-update main or use the failed parity check as evidence of an application regression.
