# Institutes Directory — Deep System Audit

**Audit started:** 2026-10-26  
**Repository:** `mattari20/estudent-listing`  
**Audit mode:** Read-only repository review; no production writes or migrations performed.  
**Status:** In progress — preliminary evidence register, not a final system-wide sign-off.

## Executive summary

The repository contains dedicated workflows for imports, discovery, promotion, source verification, geography integrity, search, monitoring, readiness, deployment diagnostics, and reset inventory. Reviewing one import validator cannot establish that the entire system is healthy.

The default branch contains 1,236 tracked tree entries at the time of review. The live database inventory/reset baseline is documented in `docs/data-rebuild/reset-plan-2026-10-26.md`.

### Verified so far

1. **CSV import header handling needs hardening.** Institution, enrichment, and geography import controllers validate headers and then map values by header name. A UTF-8 BOM on the first header can prevent expected field recognition. Duplicate headers were silently deduplicated by the enrichment and geography schema validators, concealing ambiguous CSV structure. PR #821 now covers BOM normalization in all three controllers and duplicate-header rejection in both validators.
2. **Import and promotion have multiple layers.** The repository includes preflight, duplicate audit, verification, provenance readiness, promotion guards, reconciliation, and reversal services. These need end-to-end contract review.
3. **Geography integrity has explicit failure modes.** Existing service code checks missing geography, inactive ancestors, missing parents, and cycles. We still need to establish whether every write/import/promotion path invokes these checks and whether live records currently violate them.
4. **Production readiness is composed from multiple checks.** Promotion readiness checks import verification, source verification, and provenance readiness. We need to verify consistent enforcement at every canonical write path.
5. **CI for PR #821 is green.** Laravel CI run 1615 and Test run 1851 completed successfully on the CSV-fix branch. This validates the suites that ran, not live data or the entire application.
6. **Import integrity command overstates health.** `institutes:import-integrity-audit` returned `status=healthy` whenever the two import tables existed, without evaluating the grouped row states. The report therefore described successful table access as healthy data. A focused fix is proposed in PR #823: use `status=completed` and explicitly state that row states still require review.

## Preliminary findings register

| ID | Area | Finding / risk | Evidence | Severity | Confidence | Status |
|---|---|---|---|---|---|---|
| CSV-01 | Enrichment CSV import | First-column UTF-8 BOM may prevent header recognition | `InstituteEnrichmentImportController`, `EnrichmentImportSchemaService` | Medium | Confirmed by source review | Fix proposed in PR #821 |
| CSV-02 | Geography CSV import | First-column UTF-8 BOM may prevent header recognition | `GeographyImportController`, `GeographySchemaProfileService` | Medium | Confirmed by source review | Fix proposed in PR #821 |
| CSV-03 | CSV schema validation | Duplicate headers are silently deduplicated instead of rejected | Enrichment and geography schema validators | Medium | Confirmed by source review | Fix proposed in PR #821 |
| CSV-04 | Institution CSV import | First-column UTF-8 BOM was not removed before header validation | `app/Http/Controllers/InstituteImportController.php` | Medium | Confirmed by source review | Fix added to PR #821; fresh CI pending |
| AUDIT-01 | Import integrity reporting | Audit labels data `healthy` solely because required tables exist; row-state distribution is not part of that health decision | `app/Console/Commands/ImportIntegrityAudit.php` and `tests/Feature/ImportIntegrityAuditTest.php` | Medium | Confirmed by source review | Fix proposed in PR #823 |
| DATA-01 | Live data quality | Live snapshot identifies 36 active programs without active offerings; import review queue has 126 candidate rows, 192 duplicate rows requiring link verification, 7 rejected rows, and 4 promotion failures | Hostinger read-only diagnostics run [40309531178](https://github.com/mattari20/estudent-listing/actions/runs/40309531178), 2026-10-26 10:43 UTC; record-level audit output | High | Confirmed counts; record disposition still needs review | Remediation plan pending; no writes |
| PROMO-01 | Canonical promotion | Verify all canonical writes enforce review, source, provenance, parent integrity, and stale-review guards | Promotion/readiness services and controllers | High | Audit gap, not confirmed defect | Pending |
| GEO-01 | Geography hierarchy | Verify import, seed, and promotion paths enforce active parent, cycle, and hierarchy constraints | Geography integrity/import/seed services | High | Audit gap, not confirmed defect | Pending |
| DUP-01 | Duplicate resolution | Verify cross-batch duplicate audit, aliases, slug collisions, and canonical promotion are consistent | Duplicate audit and identity-resolution services | High | Audit gap, not confirmed defect | Pending |
| DISC-01 | Discovery agent | Verify parent selection, source evidence quality, idempotency, candidate status, and review-package lifecycle | Discovery agent, extractor, identity resolver, review policy, promotion guard | High | Audit gap, not confirmed defect | Pending |
| SEARCH-01 | Public directory/search | Verify filters, pagination, detail relationships, nearby search, empty-result handling, and query safety | Search services/controllers/routes/tests | Medium | Audit gap, not confirmed defect | Pending |
| SEC-01 | Admin/operator security | Verify route middleware, role boundaries, CSRF, upload limits, authorization and audit events | Routes, middleware, import/admin controllers | High | Audit gap, not confirmed defect | Pending |
| OPS-01 | Scheduled workflows | Verify manual/scheduled workflows are appropriately gated, fail closed on revision mismatch, and cannot bypass review | `.github/workflows/*.yml` | High | Audit gap, not confirmed defect | Pending |
| DB-01 | Schema and migrations | Check migration ordering, duplicate table-creation migrations, schema drift, foreign keys and indexes | `database/migrations`, reset inventory | High | Audit gap, not confirmed defect | Pending |
| TEST-01 | Test coverage | Map critical paths to tests, identify missing failure cases, and verify tests run in CI | `tests/Feature`, `tests/Unit`, CI workflows | Medium | Audit gap, not confirmed defect | Pending |
| DEPLOY-01 | Production deployment | Compare deployed code revision to repository and inspect latest read-only diagnostics | Hostinger read-only diagnostic workflows | High | Not established by source review alone | Pending |

## Live read-only baseline — 2026-10-26

The latest inspected Hostinger diagnostics run was successful, confirmed `deployment_revision_status=APPLICATION_CODE_MATCH`, and reported no production data mutations. The public site monitor returned HTTP 200 for the listing page and University of the Punjab detail page. Guest access to operator-only discovery history and review-package download was denied by redirect to the Institutes login.

### Canonical data snapshot

| Metric | Live value | Interpretation |
|---|---:|---|
| Active institutions | 19 | All 19 canonical institutions are active in this snapshot |
| Active campuses | 22 | No active institution is missing an active campus |
| Active programs | 85 | 36 active programs have no active offering |
| Active program offerings | 55 | All 55 offerings were active |
| Admission intakes | 5 | No intake-without-active-offering gap reported |
| Active campuses without active geography | 0 | Geography relation check passes for active campuses |
| Active offerings without active program | 0 | No relationship gap reported |
| Active offerings without active campus | 0 | No relationship gap reported |
| Import batches | 18 total: 15 validated, 3 failed | Failed batch IDs and failure reasons need record-level review |
| Import rows | 347 | 22 promoted, 126 candidate, 192 duplicate, 7 rejected |
| Rows with open conflicts | 0 | No open import conflicts were counted |
| Duplicate institution-name groups after trim/case-fold | 0 | This narrow check does not rule out aliases, near-duplicates, or slug collisions |
| Active campus district coverage | no district-level assignments reported | Review geography granularity and intended coverage before classifying as defect |
| Institution rows without approved identity decision | 9 | Review before any attempted promotion |
| Promotion failures in summarized audit | 4 | 1 missing active source evidence; 3 other runtime exceptions |

**Operational conclusion:** schema and application revision checks passed, and the public listing/detail smoke checks passed. Data completeness is not yet production-ready: 36 active programs lack active offerings, 126 candidate rows still need review, 192 duplicate rows need canonical-link verification, 9 institution import rows lack an approved identity decision, and 4 promotion failures require diagnosis. The same snapshot has 3 failed import batches, which must be tied to batch IDs and their safe-to-share failure categories before retrying. These are review queues, not authorization to bulk promote, delete, or rewrite records. The user has previously identified the current data as test/dummy data; any reset or replacement still requires a reviewed, exact-scope workflow and must preserve users and migration history.

Live evidence: [Hostinger read-only diagnostics run 40309531178](https://github.com/mattari20/estudent-listing/actions/runs/40309531178); [public site monitor run 40309531038](https://github.com/mattari20/estudent-listing/actions/runs/40309531038). Exact program IDs, names, institution names, and slugs are recorded in [the 36-row offering-gap register](active-programs-without-offerings-2026-10-26.md).

## Remediation validation update — 2026-10-26

- PR #821 current-head validation passed: [Laravel CI run 1621](https://github.com/mattari20/estudent-listing/actions/runs/40316350361) and [Test run 1857](https://github.com/mattari20/estudent-listing/actions/runs/40316350693). These cover code/tests in CI, not production writes.
- PR #823 current-head validation passed: [Laravel CI run 1620](https://github.com/mattari20/estudent-listing/actions/runs/40316298460) and [Test run 1856](https://github.com/mattari20/estudent-listing/actions/runs/40316298478).
- PRs remain open and unmerged; no production writes were performed.

## Full-system audit plan

### Phase A — Repository and architecture inventory
- Enumerate routes, controllers, services, models, migrations, scheduled commands, workflows, and tests.
- Trace public request paths through controllers/services/queries/views.
- Trace every canonical write path and identify mandatory guards.
- Check duplicate or conflicting workflow/migration definitions.

### Phase B — Data integrity and evidence
- Review latest read-only live reports: data integrity, record-level gaps, import integrity, duplicate audit, geography integrity, source/provenance readiness.
- Reconcile counts between reports; record timestamps and deployed revision.
- Build record-level findings with IDs, source evidence, duplicate candidates, dependencies, and proposed disposition.
- Do not delete, promote, approve, or mutate production records during this phase.

### Phase C — Functional and security review
- Review import validation, duplicate detection, approval/rejection, promotion, reversal, discovery, search, pagination, details, nearby search, and monitoring.
- Verify middleware enforcement for every operations route.
- Inspect source URL safety, file upload handling, output escaping, query construction, rate limits, and secrets handling.
- Confirm scheduled workflows cannot unexpectedly publish or alter canonical data.

### Phase D — Schema, deployment, and test assurance
- Audit migration history for duplicate create-table migrations and incompatible ordering.
- Compare schema fingerprint, foreign keys, indexes, and row counts using read-only diagnostics.
- Map critical requirements to tests and CI jobs.
- Verify deployed application code and report output before any fix is promoted.

### Phase E — Prioritized remediation
- Rank findings by severity, likelihood, blast radius, and dependencies.
- Create focused PRs for cohesive fixes.
- Require tests and CI evidence before merge; deploy only through the controlled workflow.
- Re-run the corresponding read-only audit after deployment.
- Keep production data mutations disabled until a separate record-level plan is reviewed.

## Evidence and limitations

- Live reset inventory and boundary: `docs/data-rebuild/reset-inventory-2026-10-26.csv` and `docs/data-rebuild/reset-plan-2026-10-26.md`.
- No production database writes, migrations, imports, or discovery runs were performed for this report.
- Only the three CSV findings currently have source-level confirmation. All other entries are audit work items, not claims that defects are confirmed.
- Final report must include counts by severity, exact file/line references, test evidence, live-report timestamps, and remediation order.

### Record-level promotion failure trace — read-only evidence

The Hostinger diagnostic job output exposes four institution import rows with the latest event `promotion_failed`. These are still candidate rows in validated batches; no retry or canonical write is authorized from this evidence alone.

| Import row ID | Institution name | Batch ID | CSV row | Latest error class | Evidence status |
|---:|---|---:|---:|---|---|
| 15 | Lahore College for Women University | 2 | 6 | `RuntimeException` | Candidate; inspect event metadata and active source evidence before retry |
| 16 | University of Education Lahore | 2 | 7 | `RuntimeException` | Candidate; inspect event metadata and active source evidence before retry |
| 17 | University of Veterinary and Animal Sciences Lahore | 2 | 8 | `RuntimeException` | Candidate; inspect event metadata and active source evidence before retry |
| 29 | Federal Urdu University of Arts, Sciences & Technology | 10 | 2 | `RuntimeException` | Candidate; inspect event metadata and identity decisions before retry |

**Important limitation:** the read-only row trace provides the exception class but not the underlying exception message, and does not provide enough evidence to safely assign a root cause. The reported four failures should therefore be treated as four *runtime-exception events*, not as four proven instances of the same defect. The earlier summary's “1 missing active source evidence” classification is not attributable to these four rows from the exposed fields alone; reconcile the event-level and source-evidence summaries before using that classification. No error payloads, credentials, or private source notes are reproduced here.

The same trace shows that rows 15–17 belong to batch 2 and row 29 to batch 10. This is **not** a list of the three failed import batches: those batch-level failures are a separate count and still need their own exact IDs/status/error categories. Do not retry entire batches based on row-level promotion events.

## Final checkpoint report for this work session — 2026-10-26

### Code and documentation validation

- **PR #821 — CSV header hardening:** latest reported Laravel CI and Test runs passed. This change is still open/unmerged; do not treat it as deployed.
- **PR #823 — import-integrity status wording:** latest reported Laravel CI and Test runs passed. This change is still open/unmerged; do not treat it as deployed.
- **This audit-report commit** (`16e8a19a9dfc4c0eff8b20d3148047942e6154a2)): both current-head checks completed successfully:
  - [Laravel CI run 40319173991](https://github.com/mattari20/estudent-listing/actions/runs/40319173991) — success
  - [Test run 40319173978](https://github.com/mattari20/estudent-listing/actions/runs/40319173978) — success
- Passing CI confirms the tests executed by those workflows pass on the relevant commit. It does not certify live data, prove all untested paths, or mean open PRs have been merged/deployed.

### Work items closed vs. still open

| Workstream | Evidence/result | Status |
|---|---|---|
| CSV BOM and duplicate-header fix | Code/tests in PR #821; CI green | Ready for review; open/unmerged |
| Import-integrity status semantics | Code/tests in PR #823; CI green | Ready for review; open/unmerged |
| 36 active programs lacking offerings | Exact record register exists; official-source verification still required | Evidence register complete; disposition open |
| Promotion failures | Row IDs 15, 16, 17, 29 identified; each latest event is `promotion_failed` with `RuntimeException` | Row trace recorded; root cause unproven |
| Three failed import batches | Live summary confirms count = 3, but inspected diagnostic output does not expose exact batch-level IDs/reasons | Open; must retrieve a batch-level diagnostic |
| 192 duplicate import rows | Count confirmed; exact row-to-canonical mapping/dependency impact not yet independently audited | Open; no deletion |
| Nine institution import rows without approved identity decision | Count confirmed; row-level identity review remains | Open |
| Public listing and institution detail smoke checks | HTTP 200 on the inspected run; operator-only routes redirect guests to login | Snapshot passed; broader security audit open |
| Geography coverage | 22 active campuses have geography; no district-level assignments reported | Coverage review open; not yet classified as defect |
| System-wide Phase 2 sign-off | Full route/schema/security/search/discovery/migration coverage not yet complete | Not complete |

### Data-safety statement

This session used repository and previously captured read-only diagnostics. No production database writes, imports, promotions, deletions, migrations, or discovery jobs were executed. Do not retry batches or promotion rows until the underlying batch/event metadata and current source evidence are available. Do not delete the 192 duplicate rows based only on a duplicate status or similar name.

### Overall result

The scoped code/documentation work has passing CI evidence, and the audit now records exact IDs for four promotion-failure rows. **The full system audit and Phase 2 closure are not complete** because batch-level failure reasons, duplicate canonical links, official evidence for missing offerings, and the remaining route/security/schema/workflow audits are outstanding. This is the honest completion boundary for this checkpoint.



## Master audit execution update — 2026-10-29

### Current blocker: guarded migration preflight execution

- [ ] **OPS/DB incident:** guarded duplicate_rows migration workflow #6 failed after `php_preflight_stage=starting` with remote status 255. The run was dry-run (`confirm_migration=false`) and did not reach the migration command.
- [x] **General main checks:** Test, Laravel CI, frontend build, command orchestrator, Hostinger read-only diagnostics, and public-site monitor passed on commit `c0f7bb9aa15e9b994fa3a4787c29399e570567bf`.
- [x] **Independent SSH diagnostics:** Hostinger read-only diagnostics #40819347203 passed and ran PHP/Laravel diagnostics. This is useful contrast evidence, not proof the migration preflight works.
- [ ] **Root-cause isolation:** remove the legacy SSH action `script_stop: true` setting in favor of explicit shell-level `set -eu`, and add PHP CLI version/inline-execution probes before Laravel bootstrap. Upstream documents this option as legacy and reports compound-command problems; treat it as a hypothesis until a new live run proves the outcome.
- [ ] **Live preflight:** rerun on `main` with `confirm_migration=false`, capture the actual table/column/migration-ledger state, and only then decide whether schema reconciliation is needed.
- [ ] **Step 1 gate:** keep Step 1 open until the live diagnostic path and trigger limitations are evidenced and the required acceptance criteria are satisfied.

### Audit discipline

Continue the existing full-system audit in dependency order. For every finding, record exact file/workflow/run evidence, severity, confidence, owner/status, correction, regression tests, and live verification. Distinguish a confirmed defect from an unverified audit gap. Do not delete, promote, mutate canonical data, write migration-ledger rows, or apply production migrations without an exact-scope reviewed plan and separate authorization. The current migration incident does not replace the outstanding audit areas already listed above.


### Additional workflow inventory finding — legacy SSH option

A targeted read of the eight highest-risk migration/deployment/diagnostic workflows found `.github/workflows/controlled-official-source-refresh-migration.yml` also still sets `script_stop: true` while using shell compound commands. This is an **audit item, not yet a confirmed defect**: its own PHP diagnostic path has different behavior and must be checked against its live evidence before changing it. Add it to the workflow-hardening review and do not bulk-remove the option from unrelated workflows without reviewing each contract test and run history.


## Master audit execution checkpoint — 2026-10-29 (main `a6239509d1183ad54e3f062d572876e377e5743d`)

### OPS/DB incident status
- PR [#893](https://github.com/mattari20/estudent-listing/pull/893) merged at `a6239509d1183ad54e3f062d572876e377e5743d`; PR-head Test [#40819995295](https://github.com/mattari20/estudent-listing/actions/runs/40819995295) and Laravel CI [#40819995286](https://github.com/mattari20/estudent-listing/actions/runs/40819995286) passed.
- The live guarded workflow still requires a new manual read-only dispatch with `confirm_migration=false`. The available GitHub connector cannot dispatch workflows; no run is claimed. Existing failed run #40819601765 used the old workflow source and remains non-diagnostic for the merged change.
- Confirmed code-review finding: the duplicate_rows preflight did not reject the partial state where the column exists but its migration ledger row is absent. A focused fail-closed fix and regression test were merged in [PR #894](https://github.com/mattari20/estudent-listing/pull/894) as `2f4be296cc908ecd8da4d445ee3d724f8dd42da8` after CI passed. Fresh read-only diagnostics confirm the live column is `int(10) unsigned`, non-null, default `0`; column position and all table constraints remain unverified. The target migration ledger row is absent.

### Current audit boundary
- **Verified:** main SHA, PR #893 merge, PR CI results, prior failed-run stage evidence, and source-level preflight state-handling gap.
- **Audit gaps:** full schema equivalence/column ordering and migration-ledger reconciliation; end-to-end live detail-route acceptance with a real published institution; importer row-level behavior; source-provenance/duplicate disposition; complete authorization matrix; SEO checks against a populated directory; and actual Hostinger scheduler execution.
- **Safety:** no production migration, schema repair, migration-ledger write, data import, canonical promotion, deletion, or scheduled command execution was performed. Keep the incident and Step 1 open; do not begin Step 2.


## Fresh production read-only audit — 2026-10-29 10:01 +05

Source: Hostinger Read-only Diagnostics [run #40820996249](https://github.com/mattari20/estudent-listing/actions/runs/40820996249), application-path parity reported `APPLICATION_CODE_MATCH`, all diagnostic checks marked `mutation_performed: false`.

| Area | Latest evidence | Classification / next step |
|---|---|---|
| Duplicate-row migration | `import_batches` exists; `duplicate_rows` exists; migration file and migrations table exist; target migration ledger row absent; diagnosis `COLUMN_PRESENT_MIGRATION_NOT_RECORDED` | **Confirmed schema/ledger inconsistency.** PR #894's fail-closed guard is merged. Fresh diagnostics confirm `int(10) unsigned`, non-null, default `0`; position after `candidate_rows` and complete constraints remain unverified. Do not run migration or write the ledger without a separately reviewed and authorized reconciliation. |
| Official-source refresh migration | `official_source_refresh_events` exists; required columns and indexes present; matching migration ledger row exists; `schema_ready=true` | **Verified for the fields the diagnostic checks.** This is narrower than full schema fingerprint proof. |
| Canonical directory data | Live diagnostic reports zero active institutions, campuses, programs, and offerings; related integrity gap counts are zero because the active canonical population is empty | **Confirmed empty canonical dataset at this snapshot**, not proof the national source universe is empty. Public directory has no genuine institution detail URL to smoke-test. |
| Public listing | HTTP 200, expected listing/empty-state content; public monitor recorded `EMPTY_DATA` | **HTTP/empty-state pass; data-coverage gate blocked.** Do not create fake records to satisfy the smoke test. |
| Operator access | Guest access to discovery run history and review-package download returned 302 to `/institutes/login` | **Verified for these two endpoints only**, not a complete authorization matrix. |
| Hostinger scheduler | Laravel schedule list showed both hourly commands; `crontab -l` unavailable, exit 127 | **External verification gap**, not evidence that a cron job is missing. Confirm actual hPanel Cron Jobs and recent execution history. |
| Application deployment | `deployment_revision_status=APPLICATION_CODE_MATCH` | **Application-path parity pass**, not exact overall Git SHA equality or proof every deployment asset/config matches. |

### Workflow infrastructure snapshot
- Active repository ruleset `main` applies to `refs/heads/main`, blocks deletion and non-fast-forward updates, and requires the `test` status context. Its strict status-check policy is false. Direct legacy branch-protection endpoint access returned 403 to the integration; that separate endpoint's configuration could not be inspected.
- The five controlled-write workflows reviewed include duplicate_rows migration, official-source refresh migration, discovery-outcome migration, Pakistan geography seed, and Hostinger code deployment. Their confirmation controls are manual-dispatch inputs; duplicate_rows, discovery-outcome, and code-deploy defaults are false. The official-source refresh workflow still has `script_stop: true`; this remains an audit item, not a confirmed defect, pending workflow-specific live testing.
- Main push checks after PR #893 merge passed: Test, Laravel CI, frontend build, command orchestrator, public monitor, and Hostinger read-only diagnostics. These checks do not run the manual duplicate_rows workflow.
- Historical migration filenames with repeated table names were sampled; at least the duplicate institution_names, ranking_methodologies, and campuses files inspected were intentionally empty superseded migrations plus later active migrations. No duplicate-create defect is inferred from filename repetition alone.


## Static route/security audit checkpoint — 2026-10-29

### Verified by source inspection
- Public institution listing/search/detail, sitemap, robots, login/setup/password-reset, and token-protected diagnostics routes are declared under the `institutes` prefix.
- All observed `/institutes/operations/*` routes are nested inside `auth` plus `institute.operator`; user-management routes are further nested under `institute.admin`.
- The automated diagnostics endpoint requires a configured token of at least 32 characters, compares the bearer token with `hash_equals`, has `throttle:5,1`, returns `Cache-Control: no-store, private`, and fails closed if report shape or read-only assertions are invalid.
- The User model uses Laravel's `hashed` password cast. Password changes require the current password and rotate the remember token; feature tests cover these behaviors.
- The user-management creation path is admin-gated and validates role/email/password. Whether `master_admin` should be assignable by ordinary admins is not established by the inspected policy; keep this as an authorization-policy audit gap, not a confirmed exploit.
- Public smoke run [#40821636641](https://github.com/mattari20/estudent-listing/actions/runs/40821636641) passed HTTP checks, but classified the listing as `EMPTY_DATA`. This is not a route failure; there is no published institution detail URL to test.
- Guest requests to discovery history and review-package download were redirected to the Institutes login during diagnostics [#40821636664](https://github.com/mattari20/estudent-listing/actions/runs/40821636664). This is evidence for those two endpoints only, not a complete route authorization matrix.

### Remaining audit gaps
- No full live route-by-route authorization matrix or authenticated browser test was executed in this pass.
- Public API rate-limit behavior, setup-route race behavior, admin/master-admin role policy, CSRF/session settings, upload-size and malformed-CSV cases, and all sensitive mutation controllers still require dedicated tests or reviewed evidence.
- Do not classify these as confirmed vulnerabilities without reproducing the specific failure.


## Execution update — 2026-10-29: duplicate_rows schema/ledger incident

- Latest live evidence is guarded migration run [#40822324545](https://github.com/mattari20/estudent-listing/actions/runs/40822324545): PHP/Laravel preflight works and confirms the target column exists while its exact migration ledger row is absent. The run requested no write and performed no mutation.
- [PR #896](https://github.com/mattari20/estudent-listing/pull/896) merged as `395549e4cc09f8a6d8fafffcdd4d3c2b0ad5a213`. It extends the guarded preflight and Hostinger read-only diagnostics with column definition/position, index, foreign-key, table metadata, related migration ledger and migration-file inventory, bounded migration Git history, and PHP syntax/contract checks. Test [#40824917315](https://github.com/mattari20/estudent-listing/actions/runs/40824917315) and Laravel CI [#40824917311](https://github.com/mattari20/estudent-listing/actions/runs/40824917311) passed. Fresh live diagnostic output remains unverified.
- Full schema equivalence and the historical origin of the column are still unknown. No reconciliation action is authorized. Continue the remaining independent audit work; this incident does not justify pausing public-route, data-integrity, security, scheduler, and deployment audits.


## Execution update — 2026-10-29: public empty-directory investigation

- Starting main SHA: `a460847e364b82cf209bf04c6c6d86c62541c1ba`.
- Prior live diagnostics [#40821967441](https://github.com/mattari20/estudent-listing/actions/runs/40821967441) reported zero active canonical institutions, campuses, programs and program offerings; public monitor [#40821967396](https://github.com/mattari20/estudent-listing/actions/runs/40821967396) reported `EMPTY_DATA`. Fresh confirmation is still needed.
- Source inspection: root listing uses `InstitutionController@index`, filters only `status=active`, and renders server-side pagination. API `/api/v1/institutions` returns a JSON paginator. Search filters add relation-based exclusions. Repository JavaScript does not fetch or parse directory JSON, so the browser exception's cause is not established.
- PR [#898](https://github.com/mattari20/estudent-listing/pull/898) merged as `a54f728f31ce8ca92854d2adf4ee777150e0a710`. Test [#40827000086](https://github.com/mattari20/estudent-listing/actions/runs/40827000086), Laravel CI [#40827000025](https://github.com/mattari20/estudent-listing/actions/runs/40827000025), and Project Controller [#40827000002](https://github.com/mattari20/estudent-listing/actions/runs/40827000002) passed. It adds read-only production table/status/relationship counts and public GET probes that report HTTP status, content type, sanitized body summary, paginator structure and page-ID overlap. Fresh post-merge live output is not confirmed because this integration cannot list push/scheduled runs and direct outbound DNS is unavailable.
- The `duplicate_rows` migration was introduced in repository commit [`556bfce3d9c381bfd50d38017ee0982356b0b2af`](https://github.com/mattari20/estudent-listing/commit/556bfce3d9c381bfd50d38017ee0982356b0b2af), but the live column's origin remains unverified because its exact ledger row was absent. Do not reconcile ledger or schema without fresh evidence and explicit authorization.
- Scheduler source registers two hourly Laravel commands; hPanel cron presence remains unverified. PR #898 reports PHP executable/version and the expected every-minute scheduler command. No production write or scheduler change was performed.
