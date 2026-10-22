# Roadmap and Acceptance Criteria

**Roadmap status:** proposed control plan based on current code review and recorded requirements. Adjust status only with evidence.

## Phase 0 — Documentation and baseline reconciliation
- [ ] Merge and maintain the canonical documentation set.
- [ ] Map each requirement to actual code, tests and live evidence.
- [ ] Refresh read-only diagnostics and confirm the current deployed revision.
- [ ] Record open CI failures and fix them before dependent changes.
**Exit:** documented baseline, no ambiguous status claims, reproducible test/deployment evidence.

## Phase 1 — Data quality and safe cleanup
- [ ] Export/read-only inventory of all import batches and rows.
- [ ] Include validation errors, identity decision, source evidence, duplicate/collision links and canonical dependencies.
- [ ] Produce per-row proposed disposition: retain candidate, correct/retry, map to canonical, reject/archive, or approved test-data removal.
- [ ] Run dry-run counts and dependency checks; review before any writes.
- [ ] Re-run diagnostics after approved cleanup.
**Exit:** no indiscriminate deletion; every changed row has a reason and audit trail.

## Phase 2 — Reliable import and publication
- [ ] Test source → snapshot/evidence → batch/row → validation → identity decision → promotion → publication.
- [ ] Improve actionable error messages and safe retries.
- [ ] Test both new canonical records and matches to existing records.
- [ ] Verify public pages only show approved, valid records and relationships.
**Exit:** repeatable end-to-end tests, evidence-backed approval and no duplicate publication.

## Phase 3 — Master dataset and coverage
- [ ] Define institution types/education levels and required fields per type.
- [ ] Prepare one consolidated master import file from approved source data.
- [ ] Validate names, aliases, slugs, regions, campuses, programs, official URLs and duplicates.
- [ ] Report coverage gaps by level, province/territory, district and institution type.
- [ ] Import in controlled batches only after preflight passes.
**Exit:** auditable data coverage report and no unexplained canonical duplicates.

## Phase 4 — Public discovery and student information
- [ ] Test name/alias/program/location search and all filters.
- [ ] Complete useful institution/campus/program detail pages and empty states.
- [ ] Verify pagination, mobile layout, accessibility and performance.
- [ ] Add student-relevant fields incrementally with provenance and freshness.
**Exit:** automated functional tests and representative live checks pass.

## Phase 5 — Location and map discovery
- [ ] Select and document provider/licensing/cost constraints.
- [ ] Add coordinate storage, provenance, accuracy and correction workflow.
- [ ] Implement map view, location search and nearby-institution discovery.
- [ ] Request browser location only with clear user consent; provide manual city/address fallback.
- [ ] Test missing coordinates, denied permission and provider outages.
**Exit:** results are geographically meaningful and graceful fallback works.

## Phase 6 — Community ratings and comments
- [ ] Finalize institution/campus/program rating scope.
- [ ] Design rating submission and written review/comment data models.
- [ ] Add moderation queue, abuse reporting, anti-spam/rate limits and privacy controls.
- [ ] Display ratings with sample size, date and clear unverified/verified status.
- [ ] Add comments section at the end of institution detail pages.
**Exit:** moderation and abuse tests pass before public submissions are enabled.

## Phase 7 — Universal rating and reputation
- [ ] Inventory eligible external/international rating sources and usage permissions.
- [ ] Preserve original scales, dates, target identity and source URLs.
- [ ] Define a transparent, versioned methodology for combining comparable signals.
- [ ] Account for sample size, recency, coverage, confidence, manipulation and missing data.
- [ ] Show explanations and source components; never imply false precision.
**Exit:** methodology reviewed and test cases demonstrate reproducible results. Do not invent weights in advance.

## Phase 8 — AI research and submission agent
- [ ] Audit existing source/AI adapter implementation first.
- [ ] Define allowed sources, retrieval schedule, snapshots, extraction schema and confidence rules.
- [ ] Detect duplicates, changes and conflicts against canonical entities.
- [ ] Submit proposed changes to human approval with evidence and provenance.
- [ ] Add retry, failure alerts, audit events and operator controls.
**Exit:** end-to-end source-to-review workflow passes; agent cannot silently publish or overwrite canonical data.

## Phase 9 — SEO, security and operations
- [ ] Audit canonical URLs, metadata, structured data, sitemap and robots.
- [ ] Test roles, authentication, rate limits, secrets and user permissions.
- [ ] Verify monitoring, alerts, deployment verification and recovery procedures.
- [ ] Confirm retention/privacy rules for accounts, comments, snapshots and audit records.
**Exit:** release-readiness report has no unresolved critical issues.

## Phase 10 — Release and ongoing governance
- [ ] Run the full test suite and public monitor.
- [ ] Refresh production diagnostics and compare with the approved baseline.
- [ ] Document known limitations, operational procedures and ownership.
- [ ] Sign off release criteria and define recurring data freshness checks.
**Exit:** dated evidence package and explicit release decision.

## Change-control rule
No phase is complete because a PR was merged alone. Require tests, deployment evidence when relevant, updated docs and a clear statement of any deferred work.
