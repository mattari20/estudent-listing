# Institutes Directory — Project Baseline and Inventory

**Audit date:** 2026-10-31 (PKT)  
**Repository:** https://github.com/mattari20/estudent-listing  
**Audited branch:** \`main\`  
**Verified main SHA:** \`b878416826bdf62c26ad27d040f254461d3cf126\`  
**Audit scope:** Step 1 of the five-step finalization process  
**Overall project position:** IMPLEMENTED FOUNDATION; PRODUCTION AND DATA-READINESS GATES OPEN  
**Step 1 status:** INVENTORY DOCUMENTED; DO NOT START STEP 2 UNTIL REVIEW/APPROVAL

> This is an evidence-qualified inventory, not a feature-completion declaration. Repository source, CI evidence, deployed code, live runtime evidence, and approved production data are treated as separate proof layers.

## 1. Executive summary

The Institutes Directory is a substantial Laravel 13 application, not an empty prototype. The current \`main\` branch contains public directory and API routes, institution detail routing, a broad operator/admin area, import and enrichment workflows, source/evidence lineage, duplicate detection, candidate staging, identity review, promotion/publication controls, monitoring, geography integrity, and multiple readiness gates. It also contains a significant GitHub Actions automation suite.

However, the project is **not verified as production-ready or nationwide-complete**.

The most important conclusions are:

1. **Repository baseline:** GitHub reports \`main\` at \`b878416826bdf62c26ad27d040f254461d3cf126\`, latest commit PR #952. The repository default branch is \`main\`.
2. **Open PRs:** Four open PRs were returned by the current PR listing: #953, #913, #911 and #899. The first concerns the latest failed runtime diagnostic delivery; the other three are older Hostinger parity/diagnostic or documentation follow-ups.
3. **Current production runtime is unverified:** the latest inspected one-shot runtime delivery run, #41178626990, failed before the PHP diagnostic invocation was confirmed. The log exposes a runner-shell error, \`line 63: d: unbound variable\`; report metadata was absent, no diagnostic report artifact was produced, and no retry occurred.
4. **Public site/API:** historical evidence records a successful directory HTTP 200 and API HTTP 200 + valid JSON paginator at \`/institutes/api/v1/institutions\`, with zero canonical records by design. Those results are dated 2026-10-29 and are not a fresh 2026-10-31 live check. A previous intermittent HTTP 500 and the user's browser JSON exception were not conclusively attributed.
5. **Production data:** canonical institution/campus/program/offerings tables were intentionally emptied for testing in earlier verified reports. No current live row-count query was run during this audit. No dummy data should be recreated.
6. **Official-source pipeline:** substantial deterministic retrieval, extraction, snapshots/evidence, candidate staging, duplicate classification and review code exists. The qualification command is explicitly read-only. Live source qualification remains unproven; the Phase 5B source report records zero direct application HTTP response tuples. Twenty additional HEC identity profiles remain disabled.
7. **AI claim:** the inspected top-level institution identity discovery service explicitly rejects \`monitoring.use_ai=true\` and describes itself as deterministic-only. Do not describe the current institution discovery path as a verified LLM-driven autonomous extraction system.
8. **Approval/canonical workflow:** routes and controllers exist for field approvals, identity decisions, promotion, publication, reversal and refresh. Code presence does not establish production schema readiness or successful end-to-end live operation.
9. **Automation:** Project Controller and Command Orchestrator are configured; scheduled discovery and public monitoring exist; controlled deployment, migration, GCU qualification and runtime diagnostic workflows have production side effects or external access and require their own explicit gates.
10. **Governance:** the master tracker is materially stale in its opening/current-position statements. It says last reviewed 2026-10-30 but its opening Step 1 checkpoint still cites October 27 SHAs and PR states. Later append-only updates cover October 30 and source qualification, but do not reconcile the newest October 31 PRs #950–#952 or the failed run #41178626990.

**Recommendation:** keep the existing Laravel architecture; do not rebuild or add features during this audit. Step 2 should begin with a tightly scoped reconciliation of the failed one-shot diagnostic, latest production parity/API/runtime evidence, and the remaining schema/data/source-qualification gates. Do not run a production write or source activation as part of that reconciliation.

## 2. Verified repository position

### 2.1 Repository and commit

| Item | Verified result |
|---|---|
| Repository | \`mattari20/estudent-listing\` |
| Visibility | Public |
| Default branch | \`main\` |
| Latest commit | \`b878416826bdf62c26ad27d040f254461d3cf126\` |
| Latest commit title | \`fix: isolate Phase 5B.18 runtime diagnostic cleanup (#952)\` |
| Commit timestamp | 2026-10-31 06:51:27 UTC |
| Latest preceding commits | #951 \`3b0b1bc...\`; #950 \`a70af719...\`; #949 \`1ef5a78...\`; #948 \`6834261...\` |
| Local branch / working tree | UNVERIFIED — this audit used GitHub remote APIs; no local checkout or filesystem status is exposed |
| Combined status for latest SHA | Connector returned an empty status list; this is not proof that all required checks passed |

The repository metadata confirms the default branch and that the repository is not archived. The latest commit details identify the files changed by PR #952:

- \`.github/workflows/hostinger-artisan-runtime-only.yml\`
- \`.github/workflows/project-controller.yml\`
- \`.github/workflows/test.yml\`
- \`docs/PHASE_5B28_RUNTIME_WORKFLOW.md\`
- \`scripts/ci/test-hostinger-runtime-only-delivery.rb\`

PR #952 is merged. Its merge does not prove the production runtime diagnostic completed.

### 2.2 Recent main commits

| PR / commit | Purpose | Current interpretation |
|---|---|---|
| #952 — \`b8784168...\` | Isolate Phase 5B.18 runtime diagnostic cleanup; preserve partial evidence; enforce run-specific cleanup | Merged; runtime execution still failed in the subsequent observed run |
| #951 — \`3b0b1bc4...\` | Native Artisan help evidence capture and report preservation | Merged; diagnostic source and tests added |
| #950 — \`a70af719...\` | Read-only production Artisan runtime and two-way parity diagnostic | Merged; production evidence collection implemented |
| #949 — \`1ef5a78f...\` | Correct Hostinger-to-runner GCU report retrieval | Merged; transfer workflow fix |
| #948 — \`68342614...\` | Governed one-time GCU qualification runner and secure transfer | Merged; execution remains separately gated |
| #946 — \`b93e88ac...\` | Single-source qualification filter | Merged; code supports bounded source selection |
| #930 — \`e003b2d6...\` | Identity duplicate changed-snapshot regression tests | Merged; historical CI evidence recorded |
| #929 — \`cee61fe0...\` | Serialize top-level identity staging and cross-batch duplicate classification | Merged; historical CI evidence recorded |
| #926–#928 | Identity idempotency, conflicting identity evidence and Phase 5A lineage reconciliation | Merged; Phase 5A still open |

The commit list is ordered by GitHub's returned commit history. Commit hashes abbreviated above are for readability; the full latest SHA is recorded in section 2.1.

### 2.3 Pull request position

Current open PR listing:

| PR | Title | Status | Head branch | Assessment |
|---|---|---|---|---|
| [#953](https://github.com/mattari20/estudent-listing/pull/953) | Recover Phase 5B.29 runtime failure evidence | OPEN | \`fix/phase-5b29a-runtime-evidence-and-parity-path\` | Directly relevant and newest; proposed correction to runtime delivery failure evidence |
| [#913](https://github.com/mattari20/estudent-listing/pull/913) | Record post-merge Hostinger diagnostic blocker | OPEN | \`docs/step1-root-cause-quality-gates-rebuild-assessment-20261029\` | Documentation-only; records later parity failure |
| [#911](https://github.com/mattari20/estudent-listing/pull/911) | Isolate Hostinger parity fetch from deployed Git metadata | OPEN | \`fix/hostinger-parity-isolated-git-20261029\` | Code follow-up; its original concern is still relevant to later parity failures and needs disposition |
| [#899](https://github.com/mattari20/estudent-listing/pull/899) | Record merged empty-directory diagnostics and live verification limits | OPEN | \`docs/record-empty-directory-diagnostics-merge-20261029\` | Documentation-only; likely stale relative to later tracker updates, review before closing |

The recent PR listing also contains closed, unmerged PRs (for example #908, #906, #882 and #870). They must not be treated as merged implementation. Review their intended content only if a later requirement depends on it.

Recent merged PRs #948–#952 show that the current work has concentrated on safely obtaining production runtime and GCU qualification evidence, not on completing nationwide data coverage.

### 2.4 Branch inventory and working tree limitations

The GitHub branch search returned four consecutive pages of 100 branch names each (at least 400 remote branches), with another continuation cursor after the fourth page. The branches include historical \`dev/*\`, \`batch/*\`, \`audit/*\`, \`automation/*\`, \`diagnostics/*\`, \`docs/*\`, \`data/*\`, and feature/fix branches.

This is a large historical branch inventory, not 400 confirmed active work items. Open PRs are the authoritative immediate review queue; branches without open PRs may be merged, abandoned, superseded, or still contain useful unmerged commits. A full branch-to-PR ancestry comparison was not available through the exposed GitHub connector in this audit.

The local current branch, uncommitted changes, staged changes and untracked files cannot be inspected from GitHub remote metadata. Do not infer a clean working tree.

### 2.5 Workflow-run evidence limitation

The available GitHub connector exposes workflow jobs, steps and logs for a known run ID, and PR-triggered workflow runs associated with a commit. It did not expose a repository-wide listing of the latest push/schedule/manual workflow runs. The combined status query for the latest main SHA returned no status entries.

Therefore:
- No complete latest-main run ledger (success/failure/cancelled/queued) is claimed.
- Historical run IDs cited in this report are individually evidenced by tracker entries, PR descriptions or fetched job logs.
- The failed run #41178626990 is directly inspected in detail in section 6.
- User/operator access to the Actions UI is still required for a fresh, exhaustive current run inventory.

## 3. Application inventory

### 3.1 Architecture and framework

**Status: COMPLETE at repository-source level; production deployment parity UNVERIFIED.**

Evidence:
- \`composer.json\`: package \`estudent/estudent-listing\`; PHP \`^8.5\`; Laravel \`^13.0\`; PHPUnit \`^12.5\`; Pint \`^1.24\`.
- \`bootstrap/app.php\`: Laravel application configured with web routes, API routes, console routes and health endpoint \`/up\`; API prefix explicitly set to \`institutes/api\`.
- \`bootstrap/app.php\`: middleware aliases \`institute.operator\` and \`institute.admin\`; guests are redirected to \`institutes.login\`.
- \`routes/web.php\`, \`routes/api.php\`: route definitions inspected.

The application is a Laravel subfolder deployment under \`/institutes\`, not a WordPress plugin. Frontend build is part of the Test workflow; exact frontend framework/library versions were not fully inventoried in this pass.

### 3.2 Public routes and pages

**Status: PARTIALLY COMPLETE — routes exist; fresh live acceptance and populated content are unverified.**

The inspected \`routes/web.php\` defines:

- Public listing: \`GET /institutes/\` → \`InstitutionController@index\`.
- Public search: \`GET /institutes/search\` → \`InstituteSearchController\`.
- Sitemap and robots: \`/institutes/sitemap.xml\`, \`/institutes/robots.txt\`.
- Detail page: \`GET /institutes/{institution:slug}\` → \`InstitutionController@show\`.
- Login, logout, setup, forgot-password and reset-password pages/actions.
- Throttling on diagnostics and password-related endpoints.

The detail route exists in source. A working detail page still requires a valid published canonical institution and successful production route/rendering evidence. Historic smoke tests returned 404 for a hard-coded institution slug because the canonical dataset was intentionally empty; this does not prove the generic route handler is defective.

### 3.3 Public API

**Status: PARTIALLY COMPLETE — route contract exists; fresh production response is UNVERIFIED.**

Evidence:
- \`routes/api.php\`: \`GET /v1/institutions\`, \`GET /v1/institutions/nearby\`, \`GET /v1/institutions/{institution:slug}\`.
- \`bootstrap/app.php\`: \`apiPrefix: 'institutes/api'\`, making the intended public base \`/institutes/api/v1/institutions\`.
- PR #902 corrected the prior subfolder prefix issue and added a valid empty-paginator regression.
- Historical Hostinger run #40835662204 returned HTTP 200, \`application/json\`, a valid Laravel paginator, \`data=[]\`, \`total=0\`.

No current live request was performed on 2026-10-31. The 2026-10-29 successful response must not be represented as a fresh current health check.

### 3.4 Models and data domain

**Status: PARTIALLY COMPLETE — core domain models verified; exhaustive model/table inventory not completed.**

Inspected models:
- \`Institution\`: name, official name, short name, slug, type, status, description, verified timestamp; relationships to names, campuses, programs, accreditations, rankings and recommendations.
- \`Source\`: source type, name, URL, authority scope, status; relationship to evidence.
- \`ImportBatch\`: batch key, entity/source types, source identifiers, status and row counts; relationship to import rows.
- \`ImportRow\`: batch, row number, status, match state, canonical/parent subject references, payload, normalized payload, validation errors and notes; relationship to promotion events.

The broader application references campuses, programs, admissions, accreditations, rankings, recommendations, evidence, assertions, source snapshots, monitoring policies, alerts, import decisions, promotion events, geography and approval records. Exact current migration count, complete schema, indexes and foreign-key inventory were not enumerable through the connected GitHub API actions used here.

### 3.5 Controllers, services and operational interfaces

**Status: PARTIALLY COMPLETE — extensive route/controller surface verified; all controller internals and live permissions not exhaustively audited.**

The route file imports and uses controllers for:
- Institution listing/detail and public search.
- Authentication, setup, password reset and user administration.
- Operator queue and row review.
- Import, enrichment import, templates and verification.
- Source evidence capture and batch evidence capture.
- Duplicate audit, data readiness, geography integrity and geography import.
- Promotion readiness, final readiness, deployment readiness, readiness contract, production signoff and end-to-end readiness.
- Monitoring dashboard, policies, history, alert acknowledgement/resolution and exports.
- Persisted institution field/description approval proposals, decisions and apply action.
- Discovery run history, discovery review-package download and agent discovery.

The operator routes are grouped under \`auth\` and \`institute.operator\`; admin-only actions add \`institute.admin\`. This is source-level evidence, not a penetration test or a production authorization test.

### 3.6 Import/export and search

**Status: PARTIALLY COMPLETE.**

Source evidence in \`routes/web.php\` includes CSV/template-oriented import routes, enrichment import routes, discovery review-package download routes (CSV/manifest), import verification, row diagnostics and monitoring exports. Public search has a dedicated controller and API endpoints. Exact supported filter semantics, pagination, relevance, export formats and complete edge-case behavior require focused test/source review in a later audit.

### 3.7 Official sources, extraction and evidence

**Status: PARTIALLY COMPLETE.**

Verified code:
- \`InstituteIdentityDiscoveryService\` retrieves configured source content using \`SafeSourceHttpRetriever\`, creates immutable source snapshots/evidence and stages top-level institution identity candidates. It explicitly rejects \`monitoring.use_ai=true\` and is deterministic-only.
- \`InstituteDiscoveryCandidateExtractor\` has deterministic extractors for program, campus and institution entities. Institution extraction requires configured identity terms to be corroborated by the official page; configuration values alone do not create a candidate.
- \`SafeSourceHttpRetriever\` delegates URL validation to \`SourceUrlGuard\`, disables automatic redirects and applies redirect/response-size controls.
- \`QualifyOfficialInstituteSources\` supports qualification stages \`hec-universities\`, \`hec-campuses\`, and \`provincial\`; supports a single \`--source\` filter; describes itself as read-only and does not persist discovery data.
- \`BootstrapInstituteAgent\` loads configured profiles and dispatches the discovery services.
- Source snapshots, evidence, assertions and source monitoring are represented in the model/service architecture.

Not proven:
- Live HTTP response tuples and extracted identity output for each source.
- National coverage and correctness of each profile.
- All source extractors' resilience to current official-site markup changes.
- Current production version of the command and its runtime registration.
- Successful recurring source monitoring on Hostinger.

### 3.8 Candidate staging, duplicate detection and identity resolution

**Status: PARTIALLY COMPLETE.**

Verified:
- \`ImportBatch\` and \`ImportRow\` provide staged import/candidate structures.
- \`ImportDuplicateDetectorService\` checks canonical campus/program matches and previous staged rows; identity service also invokes cross-batch identity duplicate classification after PR #929.
- \`InstituteIdentityDiscoveryService\` uses a source transaction/row-locking pattern to avoid duplicate top-level identity staging under concurrent runs (PR #929).
- PR #930 added changed-snapshot and conflicting-official-name regression coverage.
- Candidate review, row diagnostics, conflict resolution, identity decision and evidence capture routes exist.

Still incomplete:
- No claim that all existing staged records have been reviewed.
- No current live candidate/duplicate counts were read.
- Human decisions for the historical GCU candidate cluster remain required.
- A passing unit/integration test does not mean the production candidate queue is clean.

### 3.9 Approval and canonical promotion

**Status: PARTIALLY COMPLETE; production schema and end-to-end production acceptance BLOCKED/UNVERIFIED.**

Verified source routes:
- Persisted field proposal creation.
- Admin decision and apply.
- Candidate identity/row decisions.
- Resolve/reject/quick-approve/decision.
- Promotion, publication, enrichment promotion, bulk promotion and bulk decision.
- Reversal and reverification/refresh actions.

PRs #915–#917 implement parentless top-level institution staging and persisted approval safeguards. PR #923 is recorded as the explicitly authorized approval migration run. Later source qualification notes still carry a separate \`institution_field_approvals\` schema warning; reconcile that conflict with fresh read-only schema evidence before any production action.

Canonical promotion and publication are distinct workflow stages. No import, approval, promotion or publication was performed during this audit.

## 4. Project phase status

The project tracker and append-only phase reports establish the following high-level position:

| Phase / work area | Current status | Evidence and remaining gate |
|---|---|---|
| Phase 2 five-step foundation plan | OPEN / NOT CLOSED | Tracker explicitly requires sequential gates; no evidence supports closing the whole phase |
| Phase 3 discovery staging and approval foundation | IMPLEMENTED IN REPOSITORY; live acceptance partial | PRs #915–#917 merged; production approval schema/runtime must be freshly verified |
| Phase 4 official source profile preparation | PREPARED, NOT QUALIFIED | PR #918 added 20 HEC identity profiles; all 20 remain disabled |
| Phase 4B–4F production schema/deployment remediation | PARTIALLY RESOLVED / REQUIRES RECONCILIATION | PRs #919–#925 and read-only/controlled migration work exist; later tracker notes still report production parity/schema blockers |
| Phase 5A identity/source lineage review | OPEN | PRs #926–#930 merged; historical GCU candidate lineage and human identity disposition remain open |
| Phase 5B live official-source qualification | IN PROGRESS / BLOCKED | Qualification code and fixed GCU runner exist; no verified live qualification report was obtained in this audit |
| Phase 5B.18 production Artisan runtime diagnosis | IMPLEMENTED; execution evidence incomplete | PRs #950–#952 merged; latest inspected run #41178626990 failed before PHP diagnostic invocation was confirmed |
| Phase 5B.29 runtime delivery repair | OPEN | PR #953 open; runtime report artifact absent from observed run |
| Nationwide pilot / production data population | NOT AUTHORIZED / NOT COMPLETE | No approved pilot dataset or current nationwide completeness evidence |

The project should not advance to data import or a nationwide pilot solely because the implementation PRs have merged.

## 5. Documentation status

### 5.1 Key documents inspected

- \`docs/PROJECT_MASTER_TRACKER.md\`
- \`docs/PHASE_5B28_RUNTIME_WORKFLOW.md\`
- \`docs/09-operations/PHASE-5B18-ARTISAN-RUNTIME-DIAGNOSIS.md\`
- \`docs/09-operations/PHASE-5B-SOURCE-QUALIFICATION-AND-FIRST-PILOT-READINESS.md\`
- \`docs/audits/step1-root-cause-quality-gates-rebuild-assessment-2026-10-29.md\`
- \`docs/AUTONOMOUS_STEP_EXECUTION_PROTOCOL.md\` (listed by workflow path; current content not fully reconciled in this pass)

### 5.2 Findings

1. \`docs/PROJECT_MASTER_TRACKER.md\` has a last-reviewed date of 2026-10-30, but its opening Step 1 checkpoint still cites an October 27 SHA and old PR states. Later append-only updates contain newer October 29–30 evidence. The top-level current position is therefore stale and internally inconsistent.
2. The latest main commits #950–#952 and the failed runtime delivery run #41178626990 are not reconciled in the tracker as retrieved.
3. \`docs/PHASE_5B28_RUNTIME_WORKFLOW.md\` correctly documents manual-only execution, fixed approved source, one-shot semantics, no overwrite and evidence retention. It needs a follow-up after PR #953 and the failed run.
4. \`docs/09-operations/PHASE-5B18-ARTISAN-RUNTIME-DIAGNOSIS.md\` records the diagnostic as prepared and pending execution, while the actual workflow has since been dispatched and failed before confirmed PHP invocation. This is now outdated.
5. The Phase 5B source qualification report is based on a 2026-10-30 repository baseline and says live application retrieval tuples are absent. It also states twenty HEC profiles are disabled. It should remain a historical qualification record, not be overwritten with unverified assumptions.
6. Numerous phase, audit, architecture, requirements, module-governance and operator runbook documents exist. The branch namespace confirms a large historical documentation effort, but a complete file-level deduplication/architecture-document consistency audit was not possible without a repository tree/list-files endpoint.
7. Historical documents should be retained. Do not delete or silently rewrite prior audit evidence.

**No tracker update is made by this audit.** The baseline report should be reviewed first; update the tracker only in a separate reviewed documentation change so the original phase history remains auditable.

## 6. Production and deployment status

### 6.1 Latest inspected runtime diagnostic delivery

<badge color="danger">Failed — runtime evidence not obtained</badge>

Observed GitHub Actions run: [#41178626990](https://github.com/mattari20/estudent-listing/actions/runs/41178626990), job ID \`124471267093\`, workflow \`Hostinger Phase 5B.18 Runtime Diagnostic Delivery\`.

Inspected job steps:
- Checkout exact approved main revision — success.
- Verify approved revision and diagnostic source — success.
- Prepare pinned Hostinger SSH identity — success.
- Upload approved script to unique private temporary directory — success.
- Verify delivery, preflight and execute diagnostic exactly once — failure.
- Retrieve private report metadata — no report metadata was present.
- Download final private report — skipped/no report.
- Download preserved diagnostic stderr — skipped.
- Validate report and upload artifact — skipped.
- Cleanup only this run's temporary remote files — success.

The job log records:

\`\`\`text
line 63: d: unbound variable
No diagnostic report metadata was produced; no retry will occur.
No private report available; no retry.
Runtime diagnostic failed or required evidence is missing.
\`\`\`

This proves a runner-shell failure in the combined preflight/execution step. It does **not** prove that the PHP diagnostic was invoked, that Laravel bootstrapped, or that the production database/schema/API is healthy or unhealthy. No runtime report artifact was obtained. PR #953 proposes a correction; it is open and must be reviewed and validated before another one-time execution is considered.

### 6.2 Public directory and API

Historical verified evidence:
- On 2026-10-29, Hostinger diagnostic #40835662204 reported application-code parity and a public API response at \`/institutes/api/v1/institutions\`: HTTP 200, \`application/json\`, valid Laravel paginator, zero records.
- Directory page \`/institutes/\` was previously observed HTTP 200.
- Detail route smoke checks previously received 404 for hard-coded slugs when the canonical dataset was empty. The route itself is present in source.
- Browser run #40834020536 captured an intermittent initial HTTP 500; later browser run #40834804093 passed the tested navigation checks.
- The owner's \`SyntaxError: Unexpected end of JSON input\` was not reproduced by clean Chromium and its exact request/script initiator was not established.

**Current 2026-10-31 production HTTP status:** UNVERIFIED. The direct URL open attempt through the available web retrieval path was inaccessible, and no fresh curl/Hostinger public monitor run was available from the connector.

### 6.3 Deployment revision and schema

- Historical production checks repeatedly reported deployed application revision drift from expected \`main\` application code.
- The tracker records a parity mismatch in Phase 5B.2 and later parity diagnostic failures, including a Hostinger read-only run that failed on shallow Git metadata.
- The latest runtime-only run failed before producing parity/runtime JSON; it cannot settle current deployed revision.
- No fresh production schema inspection was executed during this audit.
- Earlier reports identified \`duplicate_rows\` column/ledger inconsistency and unresolved migration provenance/equivalence. Earlier reports also tracked \`institution_field_approvals\` migration/schema readiness. The latest production schema state is UNVERIFIED until a fresh read-only report is reviewed.
- Cron/scheduler presence has historically been inaccessible through the SSH user's \`crontab -l\`; a Laravel scheduler command was identified in historical logs, but hPanel schedule presence and actual successful execution were not independently verified.

### 6.4 Production usability assessment

**Assessment: NOT VERIFIED AS USABLE FOR POPULATED DIRECTORY OR OPERATIONAL IMPORT.**

The Laravel routes and workflows exist, but production usability requires current proof of:
- Deployed application code parity.
- Directory page and valid JSON API response.
- A legitimate published institution record and a successful detail page.
- Current schema and migration-ledger consistency.
- Runtime command registration and expected options.
- Scheduler execution.
- Secure operator/admin access and approval schema.
- Live official-source qualification and reviewed source evidence.

No production-impacting action was taken during this audit.

## 7. GitHub Actions and Laravel automation inventory

The following workflow files were fetched from \`main\`. Trigger and behavior below reflect their source configuration, not a claim that a recent run succeeded.

| Workflow | Trigger / purpose | Side effects and safety position |
|---|---|---|
| \`.github/workflows/test.yml\` — Test | PRs to main / push to main; PHP 8.5, Node 22, frontend build, SQLite migrations, workflow contract tests, Composer test suite | CI uses an isolated SQLite test DB and build runner; safe to retain |
| \`.github/workflows/laravel-ci.yml\` — Laravel CI | Push to main and PR to main; Composer install, SQLite migrations, Laravel tests and Pint formatting | Isolated CI DB; safe to retain |
| \`.github/workflows/project-controller.yml\` — Project Controller | Manual dispatch, daily schedule \`31 4 * * *\`, selected PR and push path filters | Runs controller tests and read-only repository inventory; uploads report; read-only permissions in source; safe to retain |
| \`.github/workflows/project-command-orchestrator.yml\` — Command Orchestrator | Manual dispatch, issue comments, push to main, selected PR paths | Runs command/state workflow; permissions include issue/PR writes for persistent state comments; retain with allow-list and review; no Hostinger production access shown in inspected file |
| \`.github/workflows/institutes-public-monitor.yml\` — Public Monitor | Main-push/public HTTP health checks (workflow source inspected) | Public GET requests only; artifact report; safe to retain; recent current run outcome unavailable |
| \`.github/workflows/hostinger-readonly-diagnostics.yml\` — Hostinger Read-only Diagnostics | Manual/scheduled/push triggers (from tracker and workflow references) | SSH read-only checks plus public HTTP probes; diagnostic reports; retain, but validate shell/runtime contract before production dispatch |
| \`.github/workflows/hostinger-artisan-runtime-only.yml\` — Phase 5B.18 Runtime Diagnostic Delivery | \`workflow_dispatch\` only | Uploads a PHP script to a unique private Hostinger temp path, validates and runs one diagnostic invocation, transfers private report/stderr, and cleans only that run's temp files. Production SSH access; explicit one-shot review required. Latest inspected run failed before invocation was confirmed |
| \`.github/workflows/hostinger-gcu-single-source-qualification.yml\` | \`workflow_dispatch\` only; explicit boolean confirmation defaults false | Production SSH and one fixed GCU source qualification HTTP request; private report transfer. High side-effect/external request risk; keep disabled/not dispatched until separate approval |
| \`.github/workflows/controlled-hostinger-code-deploy.yml\` | Manual controlled deployment (tracker documents \`confirm_deployment=false\` default) | Writes deployed application files; production-impacting; retain but do not dispatch without explicit deployment authorization |
| Controlled migration workflow (path referenced in tracker/runbooks) | Manual guarded workflow; explicit migration confirmation inputs default false in documented versions | Can mutate production schema; retain only with exact target, preflight, one-time authorization and rollback/recovery plan; never dispatch in Step 1 |
| Scheduled governed discovery workflow | Scheduled hourly at minute 15 according to Phase 5B tracker; dependency conditions were patched in PR #885 | Can make outbound official-source requests and persist source snapshots/evidence/candidates/monitoring state. It is not a read-only workflow; verify current profiles, enabled state, trigger history and production parity before relying on it |
| Official-source refresh / offering-gap audit workflows | Referenced by tracker and audit branches; exhaustive YAML list unavailable | May retrieve sources and write staging/monitoring records; exact current workflow list/triggers/side effects remain UNVERIFIED |

### Automation safety conclusions

- Keep CI, offline Project Controller and public GET monitor workflows enabled.
- Keep production deployment, migration, GCU qualification and runtime-only diagnostic workflows manual and separately authorized.
- Treat scheduled discovery and source monitoring as **data-writing/outbound-network automation**, not passive monitoring. Confirm exact enabled profiles and live execution evidence before relying on them.
- Do not disable scheduled workflows as part of this inventory.
- The available connector did not provide repository-wide workflow-run enumeration or workflow-dispatch capability; manual Actions UI inspection is required for the complete run ledger and production dispatch controls.

## 8. Workstream completion matrix

Percentages are omitted because the available evidence does not support a defensible denominator. Status describes implementation plus acceptance, not the number of commits/PRs.

| # | Workstream | Status | Verified implementation | Remaining work / dependency |
|---:|---|---|---|---|
| 1 | Application foundation and architecture | PARTIALLY COMPLETE | Laravel 13 / PHP 8.5 app, route bootstrap, middleware aliases, Composer setup | Full architecture/code inventory; fresh production parity; local worktree state |
| 2 | Institution directory and public interface | PARTIALLY COMPLETE | Listing, search, sitemap, robots, detail route and public monitor | Fresh live HTTP checks; populated verified data; intermittent HTTP 500 root cause |
| 3 | Institution details and related academic information | PARTIALLY COMPLETE | Institution relationships to campuses/programs/accreditations/rankings/recommendations; detail route | Verify controllers and public rendering with approved canonical record; data breadth |
| 4 | Database and data integrity | BLOCKED / UNVERIFIED | Eloquent models, migrations, schema diagnostics, duplicate/index/readiness checks | Fresh production schema + ledger report; resolve known migration provenance/index discrepancies |
| 5 | Official-source discovery | PARTIALLY COMPLETE | Configured source profiles, safe retriever, deterministic source discovery and scheduler code | Live source response tuples, source authority/coverage validation, production parity |
| 6 | Automated extraction and AI capabilities | PARTIALLY COMPLETE | Deterministic institution/campus/program extraction; source qualification; safe retrieval | LLM/AI extraction not verified; identity service rejects \`use_ai=true\`; prove robust extractor coverage |
| 7 | Candidate staging and evidence management | PARTIALLY COMPLETE | Batches/rows, snapshots, evidence, assertions, review packages and evidence capture routes | Current production candidate/evidence counts and review-package inspection; source lineage acceptance |
| 8 | Duplicate detection and identity resolution | PARTIALLY COMPLETE | Canonical/staged duplicate detector; cross-batch identity lock/classification; changed-snapshot tests | Resolve remaining human identity conflicts and audit all live staged candidates |
| 9 | Human approval and review workflow | PARTIALLY COMPLETE / SCHEMA GATE | Field proposals, admin decision/apply, candidate decisions and row review routes | Fresh approval-table schema/ledger verification; role/authorization tests in production |
| 10 | Canonical data management | PARTIALLY COMPLETE | Canonical institution model; promotion, publication, reversal, refresh and separate decisions | Current schema/data proof; controlled lifecycle end-to-end acceptance with approved records |
| 11 | Public search and directory functionality | PARTIALLY COMPLETE | Public search route and API listing/detail/nearby routes; pagination regression work | Fresh live JSON/pagination/filter/detail checks and search relevance acceptance |
| 12 | Admin and operational functionality | PARTIALLY COMPLETE | Authenticated operator/admin route groups, queue, readiness, import, monitoring, geography and users | Full route-by-route permission/CSRF/input validation audit and live operational smoke tests |
| 13 | GitHub and Laravel automation | PARTIALLY COMPLETE | Test, Laravel CI, Project Controller, Command Orchestrator, public monitor, Hostinger workflows | Full workflow tree/run ledger; verify current schedules and all main run outcomes |
| 14 | Security and access control | PARTIALLY COMPLETE | Operator/admin middleware aliases, route grouping, guest denial checks, throttling, guarded SSH workflows | Security review/penetration checks, current production role/schema and secret/permission review |
| 15 | Hosting and production deployment | BLOCKED | Controlled deploy workflow and parity diagnostics exist | Latest deployed revision unknown; fresh successful parity evidence and production smoke tests |
| 16 | Testing, monitoring and recovery | PARTIALLY COMPLETE | Extensive PHPUnit suite, workflow contracts, browser checks, diagnostics and artifact-preservation controls | Latest-main exact-head CI status; latest production diagnostic; monitoring/scheduler run evidence |
| 17 | Nationwide institution coverage | NOT COMPLETE | HEC source inventory and disabled profiles; provincial qualification stage configured | No verified nationwide source coverage, live qualified profile matrix or approved population pilot |
| 18 | Documentation and project governance | PARTIALLY COMPLETE | Master tracker, phase reports, architecture/requirements specs, runbooks and audit reports | Reconcile stale tracker header, latest runtime failure, conflicting schema statements and duplicate/stale docs |

## 9. Confirmed unfinished work and blockers

### 9.1 Runtime diagnostic delivery

- PR #953 remains open.
- Run #41178626990 failed in the runner shell with \`d: unbound variable\`.
- No private report metadata or diagnostic artifact was available.
- PHP diagnostic invocation is unconfirmed.
- Required next evidence: review/merge PR #953 only after its exact-head CI and code review; inspect the corrected workflow contract; then separately authorize one fresh run. Do not rerun the old workflow unchanged.

### 9.2 Production deployment parity and public checks

- Current deployed application revision is not known from the latest inspected run.
- Historical parity checks repeatedly failed after otherwise successful deployments/diagnostics due to shallow Git metadata or revision drift.
- Latest runtime-only failure did not produce parity JSON.
- Required next evidence: a fresh read-only Hostinger report showing expected application-code revision, deployed revision, two-way application-path parity, public page HTTP status, API status/content type/JSON contract, and route registration.

### 9.3 Database/schema consistency

- Earlier read-only reports flagged \`duplicate_rows\` as column-present but migration-ledger absent, with schema equivalence and migration origin unverified.
- Earlier work also tracked \`institution_field_approvals\` schema readiness. PR #923 is described as an explicitly authorized migration execution, but later tracker/source qualification notes still retain a warning. This conflict must be resolved from a current read-only schema and migration ledger report.
- Index readiness and official source refresh tables/indexes were previously audited; do not assume their state from old reports.
- No migrations or schema changes were run in this audit.

### 9.4 Source qualification and nationwide coverage

- Phase 5B source report says zero direct application HTTP response tuples were captured for the source inventory.
- The one-source GCU workflow exists, but actual GCU qualification is not proven complete by the evidence reviewed here.
- Twenty additional HEC identity profiles remain disabled.
- HEC campus parent mapping and provincial/ICT source qualification are still gates.
- No profile activation, source request, discovery, candidate import or canonical promotion occurred in this audit.

### 9.5 Candidate and human review backlog

- Historical tracker reports GCU candidate rows #348–353 / batches #19–23 with evidence lineage but no completed human identity decision. Their private report/package and underlying evidence require operator review.
- No current live queue counts or current row states were read.
- No identity decision, approval, rejection, promotion, publication or reversal was performed.

### 9.6 Scheduler and monitoring

- Laravel schedule in \`bootstrap/app.php\` registers \`institutes:bootstrap-agent\` hourly and \`institutes:monitor-changes\` hourly, both with \`withoutOverlapping(55)\`.
- Historical tracker describes a scheduled governed discovery workflow at minute 15.
- hPanel cron presence/execution and recent workflow runs are not verified by this audit.
- The actual scheduler command, Hostinger cron, GitHub scheduled workflow and profile-level monitoring configuration must be reconciled. Do not assume one implies the others.

### 9.7 Documentation and governance

- Tracker opening checkpoint is stale relative to main.
- Phase 5B.18 doc still describes execution as pending although run #41178626990 has since failed.
- PR #899 and #913 remain open documentation PRs; reconcile them with the new baseline rather than duplicating their history.
- Current exact-head CI status for latest main is not available from the returned combined-status result.
- Full branch-to-PR ancestry and full docs duplicate analysis remain incomplete.

## 10. Unverified areas requiring further investigation

1. Local checkout branch, staged/unstaged/untracked changes.
2. Complete remote branch list and branch-to-PR ancestry beyond the 400 names returned.
3. Complete current GitHub Actions workflow list and current queued/in-progress/completed/failed/cancelled runs.
4. Exact-head Test, Laravel CI, Project Controller and frontend build status on \`b8784168...\`.
5. Current Hostinger deployed revision and complete app-path parity.
6. Current public listing, detail page and API response status/body shape.
7. Current production database table/index/foreign-key inventory and migration ledger.
8. Current \`institution_field_approvals\` and \`duplicate_rows\` state.
9. Current live canonical record counts and candidate row dispositions.
10. Current enabled official-source profiles and live qualification report results.
11. Current production Artisan command registration, help output and PHP/Laravel runtime version.
12. Current hPanel cron configuration and actual Laravel scheduler execution.
13. Full AI/LLM integration status beyond the deterministic identity path inspected.
14. Exhaustive controller/service/job/model/test inventory and route-by-route authorization validation.
15. Nationwide institution coverage by authority, province/territory, institution type and parent/campus identity.

## 11. Risks and dependencies

| Risk | Consequence | Required control |
|---|---|---|
| Production revision drift | Main code and deployed app behavior differ | Read-only parity report before any production command |
| Runtime diagnostic delivery bug | No reliable PHP/Laravel/schema evidence | Fix and test runner quoting/variable expansion; one explicitly authorized run |
| Empty canonical dataset | Directory may render correctly but show no institutions/detail link | Preserve empty state; only import from qualified evidence and explicit approval |
| Migration-ledger/schema discrepancy | Unsafe migration retry or hidden partial schema | Read-only schema/ledger reconciliation; no blind migration retry |
| Disabled HEC profiles | No broad automated institution coverage | Keep disabled until source qualification and explicit activation approval |
| Unverified AI claims | Overstated autonomous discovery capability | Distinguish deterministic extraction from any future LLM integration |
| Scheduler uncertainty | Discovery/monitoring may not run, or may run with unexpected side effects | Verify hPanel cron, workflow schedules and Laravel schedule separately |
| Large stale branch inventory | Duplicate work, accidental merge or lost history | Use open PR list and ancestry evidence; do not mass-delete branches |
| Stale/conflicting documentation | Wrong execution decisions | Append evidence-qualified reconciliation; preserve historical reports |
| Incomplete current CI/run visibility | False confidence in latest main | Inspect Actions UI and exact-head checks before Step 2 gates |

Dependencies for any future production action:
- Verified main SHA and exact-head CI.
- Reviewed workflow source and exact target revision.
- Current production parity.
- Read-only schema/ledger evidence.
- Explicit user authorization for the particular production operation.
- Private report path and no-overwrite guard.
- Human source/identity review where canonical identity or publication is involved.

## 12. Recommended priorities for Step 2

Step 2 should be a focused evidence-recovery and reconciliation audit, not feature development. Recommended order:

1. **Reconcile PR #953 and run #41178626990.** Inspect the proposed shell quoting/variable-expansion fix, exact-head Test/Laravel CI/Project Controller results and the final workflow diff. Confirm no production PHP invocation occurred in the failed run.
2. **Obtain fresh read-only production runtime evidence.** Only after the corrected one-shot workflow is reviewed and explicitly authorized, capture PHP version, Laravel bootstrap, native Artisan list/help, command registration/options, source hashes and parity. Preserve private report/stderr; do not overwrite existing reports.
3. **Verify production app-code parity and public behavior.** Capture deployed and expected application revision, directory HTTP response, API HTTP status/content type/valid JSON paginator, pagination/search response and detail route only if a legitimate published record exists.
4. **Reconcile schema/ledger blockers.** Read-only inspect \`institution_field_approvals\`, \`duplicate_rows\`, official-source refresh tables/indexes and migration ledger. Do not run migrations in this audit.
5. **Reconcile automation execution.** Inspect recent runs for Test, Laravel CI, Project Controller, Command Orchestrator, Public Monitor, Hostinger Read-only Diagnostics and scheduled discovery. Confirm hPanel cron and Laravel scheduler independently.
6. **Reconcile source qualification.** Review the fixed GCU report if one exists; otherwise keep GCU qualification blocked. Verify source response tuples and extraction evidence before enabling any additional HEC/provincial profile.
7. **Reconcile candidate review backlog.** Retrieve private GCU review evidence through its approved path; inspect rows #348–353 and batches #19–23; prepare a human review/disposition package without deciding identity automatically.
8. **Reconcile the master tracker.** After the above evidence is available, add one dated, concise current-position section and cross-link this baseline. Preserve all historical phase reports and do not mark any phase complete prematurely.

**Step 2 entry gate:** user reviews this baseline and approves the next audit scope. Until then, remain at Step 1 and do not execute any of the above production actions.

## 13. Evidence and limitations

### Directly inspected in this audit

- GitHub repository metadata for \`mattari20/estudent-listing\`.
- GitHub commit history through \`b878416826bdf62c26ad27d040f254461d3cf126\`.
- Recent pull request listing and current open PR list.
- Four pages of branch search results (400 remote branch names).
- Combined status query for latest main SHA (empty list returned).
- Latest main source files: \`composer.json\`, \`bootstrap/app.php\`, \`routes/web.php\`, \`routes/api.php\`, selected models, discovery services, retriever, qualification command, selected workflows and phase documents.
- PR #952/#951/#950 commit changed-file lists.
- Workflow job steps and full available log excerpt for run #41178626990 / job \`124471267093\`.

### Evidence referenced from existing repository documents / PR descriptions

- \`docs/PROJECT_MASTER_TRACKER.md\` (last reviewed date 2026-10-30; append-only historical updates).
- \`docs/audits/step1-root-cause-quality-gates-rebuild-assessment-2026-10-29.md\`.
- \`docs/09-operations/PHASE-5B-SOURCE-QUALIFICATION-AND-FIRST-PILOT-READINESS.md\`.
- \`docs/09-operations/PHASE-5B18-ARTISAN-RUNTIME-DIAGNOSIS.md\`.
- \`docs/PHASE_5B28_RUNTIME_WORKFLOW.md\`.
- PRs #899, #911 and #913 descriptions.
- Historical run references #40835662204, #40834020536, #40834804093 and #41009230720 recorded in project documentation.

### Access limitations

- No local repository checkout or local shell was available; working-tree status is not claimed.
- The connected GitHub actions used here did not expose repository-wide workflow-run enumeration or workflow dispatch.
- Directory listing for repository folders (for example \`.github/workflows\`, \`database/migrations\`, \`app/Models\`) was not supported by the file-fetch action; therefore a complete file-tree/migration count cannot be claimed.
- No Hostinger shell, database, hPanel or private diagnostic report access was performed in this audit.
- Direct public-site retrieval was inaccessible through the available web retrieval path in this turn; no fresh live HTTP result is claimed.

## 14. Safety confirmation

During this Step 1 inventory:

- No production database query or mutation was run.
- No migration was run.
- No deployment or cache clear was run.
- No import, source qualification request, discovery run, profile activation, candidate approval, identity decision, promotion or publication was run.
- No production data was created or deleted.
- No workflow was dispatched, rerun, disabled or modified.
- No existing private diagnostic report was opened, overwritten or removed.
- No historical documentation was deleted.

This report is documentation-only and is proposed on a dedicated audit branch. Step 1 remains open for user review. **Step 2 has not started.**
