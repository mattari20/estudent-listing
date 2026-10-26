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
