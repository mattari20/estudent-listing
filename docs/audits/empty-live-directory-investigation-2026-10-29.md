# Empty live Institutes Directory investigation — 2026-10-29

## Status

**Step 1 remains open.** The root cause of the empty visitor-facing directory is strongly narrowed to missing eligible canonical rows at the latest read-only live observation; the browser JSON parsing exception is not yet causally attributed. No production writes were performed.

## Starting state

- Repository: `mattari20/estudent-listing`
- Starting `main` commit: `a460847e364b82cf209bf04c6c6d86c62541c1ba`
- Existing audit/tracker read before changes: `docs/PROJECT_MASTER_TRACKER.md`, `docs/audits/deep-system-audit-2026-10-26.md`, and `docs/audits/guarded-migration-workflow-failure-2026-10-29.md`.
- Existing Hostinger read-only diagnostics run [#40821967441](https://github.com/mattari20/estudent-listing/actions/runs/40821967441) succeeded as a workflow, but that result is not a fresh post-PR #896 observation. Its record reports zero active institutions, campuses, programs, and program offerings.
- Existing Public Site Monitor run [#40821967396](https://github.com/mattari20/estudent-listing/actions/runs/40821967396) completed with `EMPTY_DATA`. This is a data-availability finding, not proof of a frontend exception cause.
- Historical owner-reviewed production evidence in [issue #168](https://github.com/mattari20/estudent-listing/issues/168) recorded 0 campuses, 0 program offerings, and 1 active program attached to institution ID 9; this is older evidence and must not be treated as a current row count.

## Repository evidence: page, API, query and relationships

- `routes/web.php` maps `GET /institutes/` to `InstitutionController@index`; `GET /institutes/search` maps to `InstituteSearchController`; public detail pages use `/{institution:slug}`.
- `resources/views/institutes/index.blade.php` is server-rendered. It renders the paginator's `total()`, cards, empty-state message, and pagination links directly from the controller response.
- `app/Http/Controllers/InstitutionController.php::index()` queries `institutions` with `status = active`, sorts by name, and paginates at 9/12/24/48 rows (default 24). The `Institution` model does not use Laravel's `SoftDeletes` trait. No tenant scope is visible in this controller/model.
- `resources/js/app.js` only implements password-visibility toggles; `resources/js/bootstrap.js` is a placeholder. The root directory view has no client-side directory fetch. These repository files do not call `response.json()` or `JSON.parse()`; therefore the supplied browser exception is **not proven to originate from the directory page's own data-rendering code**.
- `routes/api.php` exposes `GET /api/v1/institutions`; `InstitutionApiController@index()` returns Laravel's JSON paginator from the shared `InstituteSearchQueryService` and `InstituteSearchFilterService`. A valid empty collection should still be JSON with a `data` array and paginator metadata; an empty response, HTML error/login page, malformed JSON, or unexpected object shape would be a different failure and must be observed live.
- `InstituteSearchQueryService` applies `institutions.status = active`; when a query is supplied it matches institution names/aliases, active programs, active campuses, and active campus geography. Additional filters in `InstituteSearchFilterService` can exclude institutions that have no active campus/geography, program, offering, or intake meeting the selected filter.
- Public detail views load active campuses and programs; the API detail endpoint also requires active campuses to have active geography and active offerings to have an active campus. Missing campus/offering relationships can therefore affect detail/API output even if an institution row exists.
- No production API request/body was independently retrievable from this execution environment: the direct production URL was not accessible through the web fetcher. The new PR adds a live GET-only check to capture exact request URL, status, content type, response bytes, sanitized JSON/body summary, JSON paginator contract, search behavior, and page-2 ID disjointness when enough rows exist.

## Initial diagnosis (evidence-qualified)

1. **Directory empty-state:** latest available live evidence reports zero active canonical institutions. Since the root listing only queries active institutions, zero eligible active rows fully explains why it renders “No institutions to display.” A fresh count is still required to confirm the current state.
2. **JSON parsing exception:** cause remains unverified. The first-page HTML view and the repository's own JavaScript do not parse directory JSON. The error may come from another script, an extension/injected script, or a separate request; this remains a hypothesis until DevTools identifies the initiator/request and the live response is inspected.
3. **Permissions-policy `unload` messages:** the inspected layout/app scripts do not register `unload` handlers. Relevance to the empty data has not been established; they are not prioritized over the data-path checks.
4. **Pagination/search:** both web and API use the active-institution query base; API search and filters add relation conditions. Existing source inspection does not prove live pagination or search results are correct; the new checks capture the API contract and compare page IDs only when `total > 2`.
5. **Publication/approval:** canonical listing eligibility is driven by `institutions.status = active`. Import approval state is stored separately and does not itself populate the public canonical query. Read-only diagnostics must compare canonical table counts/statuses with import candidate/approval state before proposing any correction.

## Changes on this branch

Branch: `diagnostics/empty-directory-api-and-live-counts-20261029`

- Extended `.github/workflows/hostinger-readonly-diagnostics.yml` to report read-only counts for institutions, campuses, programs and program offerings by status, soft-delete columns/counts where supported, and dangling parent/foreign-key relationships.
- Added a public-site GET-only probe for `/institutes/`, `/api/v1/institutions?per_page=2`, a COMSATS search query, and page 2. It records status, content type, bytes, sanitized response summary, JSON shape and pagination ID overlap. It fails rather than treating malformed JSON/HTML as an empty collection.
- Added the runtime PHP executable/version and the expected Hostinger cron command, based on the resolved project path. It does not create or modify a cron job.
- Added bounded Git history and a migration-source inventory for `import_batches` / `duplicate_rows`.
- Added `tests/Feature/HostingerDirectoryVisibilityDiagnosticsContractTest.php` to protect the read-only diagnostic contract.
- PR #898 is open and must pass required CI before any merge: [PR #898](https://github.com/mattari20/estudent-listing/pull/898).

## duplicate_rows migration incident

- Migration `2026_10_23_000001_add_duplicate_rows_to_import_batches_table` defines an unsigned integer, non-null, default-zero column after `candidate_rows`.
- Git commit [`556bfce3d9c381bfd50d38017ee0982356b0b2af`](https://github.com/mattari20/estudent-listing/commit/556bfce3d9c381bfd50d38017ee0982356b0b2af), associated with PR #518 on 2026-10-23, introduced that migration together with `duplicate_rows` model/import-count usage. This is the likely code origin, **not proof of the live column's historical origin**.
- Previously reported live evidence: column exists, definition and position match the target migration contract, exact migration-ledger entry absent; diagnosis `COLUMN_PRESENT_LEDGER_ABSENT`; `schema_equivalence=UNVERIFIED`; `migration_origin=UNKNOWN`; no mutation.
- PR #896 merged as `395549e4cc09f8a6d8fafffcdd4d3c2b0ad5a213` and expanded diagnostics, but fresh post-merge live output is not yet confirmed.
- Reconciliation plan: collect the new live schema/position/index/FK/table metadata; inspect the complete `import_batches` creation migration and all later migrations; reconcile Git history and live ledger rows; compare all columns, indexes, defaults, nullability, engine/collation and constraints; identify whether the column was applied by the target migration, added separately, or has a missing ledger row for another reason. Only then prepare a narrowly scoped proposal with exact effects, rollback/verification, and an explicit approval gate. Do not insert a ledger row, rerun the migration, or alter schema during diagnosis.

## Hostinger scheduler

- `bootstrap/app.php` registers `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly with `withoutOverlapping(55)`.
- Laravel's scheduler entrypoint should be invoked each minute; the diagnostic now emits the actual PHP executable/version and an expected command in the form `* * * * * cd <project_path> && <php_executable> artisan schedule:run >> /dev/null 2>&1`.
- `crontab -l` previously failed or was unavailable. That is **unverified**, not proof that hPanel has no job. The hPanel screenshot showed the create form but does not establish whether any existing job exists. Owner must inspect existing hPanel jobs and recent execution history; do not create/modify/delete until matched to the diagnostic's exact path and PHP executable.
- The repository also contains a GitHub Actions scheduled governed discovery workflow on `15 * * * *` (see [commit #576](https://github.com/mattari20/estudent-listing/commit/3282b1cd385926b7023caf32886be37dcce64ed2)). This is separate from Hostinger's Laravel scheduler and does not prove the hPanel cron exists.

## Safety and next gate

No production database write, migration, import, approval, publication, deletion or cron change was performed. Wait for PR #898 required CI, then merge only if green. After merge, collect the fresh Hostinger diagnostic and public API probe. If the probe reports malformed/empty API output, use the exact URL/status/content type/body evidence to fix the request/response path in a focused PR. If it reports valid `total=0`, continue row-level provenance/approval audit and prepare an official-source rebuild plan; do not fabricate data or promote candidates automatically.


## Execution update — PR #900 / #901 and fresh live API evidence (2026-10-29)

- PR #900 merged as `fb82aa6043b342819be84f71e2ddf448cfbceac5`. Its Test [#40827745368](https://github.com/mattari20/estudent-listing/actions/runs/40827745368) and Laravel CI [#40827745303](https://github.com/mattari20/estudent-listing/actions/runs/40827745303) passed.
- The PR #900 post-merge Hostinger diagnostic [#40828720588](https://github.com/mattari20/estudent-listing/actions/runs/40828720588) failed at the API probe as intended. Root `/api/v1/institutions?per_page=2` and subfolder `/institutes/api/v1/institutions?per_page=2` returned HTTP 404 HTML; the latter used UTF-8 HTML while the root path returned WordPress HTML.
- PR #901 merged as `c83f0162731a7efccda9cf1e8c6878697659d017`. Test [#40829679792](https://github.com/mattari20/estudent-listing/actions/runs/40829679792) and Laravel CI [#40829679788](https://github.com/mattari20/estudent-listing/actions/runs/40829679788) passed on its corrected head. Post-merge main Test [#40829824631](https://github.com/mattari20/estudent-listing/actions/runs/40829824631) and Laravel CI [#40829824588](https://github.com/mattari20/estudent-listing/actions/runs/40829824588) passed.
- The fresh Hostinger run [#40829824507](https://github.com/mattari20/estudent-listing/actions/runs/40829824507) confirms the current deployed application-code paths match the expected main revision (`deployment_revision_status=APPLICATION_CODE_MATCH`). The public HTML check remains HTTP 200.
- New deployed route check in #901 executed `php artisan route:list --json --no-ansi` on Hostinger and found three institution API routes under Laravel's route registry:
  - `GET|HEAD api/v1/institutions` → `App\\Http\\Controllers\\Api\\InstitutionApiController@index`, middleware `api`
  - `GET|HEAD api/v1/institutions/nearby` → `App\\Http\\Controllers\\Api\\InstitutionApiController@nearby`, middleware `api`
  - `GET|HEAD api/v1/institutions/{institution:slug}` → `App\\Http\\Controllers\\Api\\InstitutionApiController@show`, middleware `api`
- The same fresh live probe tested, with redirects disabled, `/institutes/api/v1/institutions`, `/institutes/v1/institutions`, and `/api/v1/institutions`; all returned HTTP 404 and `text/html`. No candidate returned HTTP 200 + `application/json` + the Laravel paginator schema.
- Combined evidence now narrows the API failure to public request dispatch/rewrite/subfolder deployment path rather than absent Laravel route definitions. This is not yet a proven single rewrite rule cause. The repo root `.htaccess` rewrites to `public/$1`; `public/.htaccess` routes non-existing files/directories to `index.php`. Because the app is installed in a subfolder and current root API path appears to fall through to WordPress, do not apply an unvalidated rewrite change. Next step is a focused diagnostic/fix comparing original request URI, rewrite target/document root and Hostinger subfolder path, then one smallest safe routing change with regression tests.
- The failed Hostinger diagnostics run is expected to remain red until a valid public API endpoint exists; it is not a CI failure. No production writes occurred.

## Empty-data and browser error distinction

- The latest fresh live read-only report in [#40829824507](https://github.com/mattari20/estudent-listing/actions/runs/40829824507) again confirms `institutions`, `campuses`, `programs`, and `program_offerings` each have `total_rows=0` and `active_rows=0`; this is expected because dummy/test data was deliberately removed. There are no dangling campus/institution, program/institution, offering/program or offering/campus foreign-reference counts in the checked relations because there are no rows.
- The empty-state is therefore consistent with the intended empty database, but the browser's `SyntaxError: Unexpected end of JSON input` is not resolved: the tested API paths return HTML 404 rather than JSON. The page's own `resources/js/app.js` only handles password toggling, and the server-rendered directory view does not parse API JSON. Need identify the exact browser initiator/request after the real public API path is fixed or another script is found.
