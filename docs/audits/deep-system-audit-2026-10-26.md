# Institutes Directory — Deep System Audit

**Audit started:** 2026-10-26  
**Repository:** `mattari20/estudent-listing`  
**Audit mode:** Read-only repository review; no production writes or migrations performed.  
**Status:** In progress — preliminary evidence register, not a final system-wide sign-off.

## Executive summary

The repository contains dedicated workflows for imports, discovery, promotion, source verification, geography integrity, search, monitoring, readiness, deployment diagnostics, and reset inventory. Reviewing one import validator cannot establish that the entire system is healthy.

The default branch contains 1,236 tracked tree entries at the time of review. The live database inventory/reset baseline is documented in `docs/data-rebuild/reset-plan-2026-10-26.md`.

### Verified so far

1. **CSV import header handling needs hardening.** Enrichment and geography controllers validate headers and then map values by header name. A UTF-8 BOM on the first header can prevent expected field recognition. Duplicate headers are silently deduplicated by schema validators, concealing ambiguous CSV structure.
2. **Import and promotion have multiple layers.** The repository includes preflight, duplicate audit, verification, provenance readiness, promotion guards, reconciliation, and reversal services. These need end-to-end contract review.
3. **Geography integrity has explicit failure modes.** Existing service code checks missing geography, inactive ancestors, missing parents, and cycles. We still need to establish whether every write/import/promotion path invokes these checks and whether live records currently violate them.
4. **Production readiness is composed from multiple checks.** Promotion readiness checks import verification, source verification, and provenance readiness. We need to verify consistent enforcement at every canonical write path.
5. **CI for PR #821 is green.** Laravel CI run 1615 and Test run 1851 completed successfully on the audit branch. This validates the suites that ran, not live data or the entire application.

## Preliminary findings register

| ID | Area | Finding / risk | Evidence | Severity | Confidence | Status |
|---|---|---|---|---|---|---|
| CSV-01 | Enrichment CSV import | First-column UTF-8 BOM may prevent header recognition | `InstituteEnrichmentImportController`, `EnrichmentImportSchemaService` | Medium | Confirmed by source review | Fix proposed in PR #821 |
| CSV-02 | Geography CSV import | First-column UTF-8 BOM may prevent header recognition | `GeographyImportController`, `GeographySchemaProfileService` | Medium | Confirmed by source review | Fix proposed in PR #821 |
| CSV-03 | CSV schema validation | Duplicate headers are silently deduplicated instead of rejected | Both schema validators | Medium | Confirmed by source review | Fix proposed in PR #821 |
| DATA-01 | Live data quality | Current duplicate, unverified, orphan, and stale-record counts are not established by this repository-only review | Read-only live audit exists; fresh output not inspected in this review | High | Unknown until report evidence | Pending |
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
