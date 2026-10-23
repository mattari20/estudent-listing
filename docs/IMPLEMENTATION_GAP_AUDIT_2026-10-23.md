# Institutes Directory — implementation and gap audit

**Audit date:** 2026-10-23  
**Repository:** `mattari20/estudent-listing`  
**Audited main revision:** `1809b7473ca9d5c8da11096e945c9a47fae26d2d`

## Executive finding

The directory's application and CI are substantially further along than the older proposed roadmap checklist suggests. Recent merged work covers import-failure triage, identity/conflict review reporting, geography coverage, public search/profile filtering, pagination, empty states, and deployment runbook documentation. The checklist below remains a gap register, not a claim that every roadmap phase is release-ready.

## Latest verification evidence

- Laravel CI: pass on main revision `1809b7473ca9d5c8da11096e945c9a47fae26d2d`.
- Test workflow: pass on the same revision.
- Frontend asset build: pass.
- Public-site monitor: pass, including the directory/profile smoke checks.
- Hostinger read-only diagnostics: failed because the run was started against expected revision `96b7f0a9129b57db1aef12cd8f34548eb84fbae9`, while the live deployed application revision reported by that run was `1809b7473ca9d5c8da11096e945c9a47fae26d2d`. This is a stale-run revision comparison, not evidence of a current live-site failure. Trigger a fresh diagnostics run against the current main revision before treating deployment verification as green.

Evidence links:
- Test: https://github.com/mattari20/estudent-listing/actions/runs/39880295919
- Laravel CI: https://github.com/mattari20/estudent-listing/actions/runs/39880295998
- Asset build: https://github.com/mattari20/estudent-listing/actions/runs/39880295899
- Public monitor: https://github.com/mattari20/estudent-listing/actions/runs/39880295879
- Stale-revision diagnostics: https://github.com/mattari20/estudent-listing/actions/runs/39880295853

## Current read-only production findings

The diagnostics run reported:

- Required schema tables missing: **0**.
- Campuses: **22 total, all active**.
- Campus geography: **22/22 linked; 0 without geography; 0 active campuses with missing/inactive geography**.
- Import pipeline: **3 failed promotion rows** in the latest report snapshot; one categorized as missing active source evidence and three as other runtime exceptions in the promotion-event audit. These counts are from different audit views and should not be added together as unique rows without reconciling row IDs.
- Open import conflicts: **0**.
- Institution import rows without an approved identity decision: **9**.
- The remaining runtime exceptions must be investigated individually; they are not permission to auto-retry, delete, or promote records.

These are read-only observations. No production records were changed by this audit.

## Roadmap gap register

| Phase | Evidence-backed status | Missing work / exit gate |
|---|---|---|
| 0. Documentation and baseline | **Mostly implemented; verification refresh needed** | Refresh the diagnostics run against the current main SHA; map each requirement to a test, code path, and live check; replace older proposed-only status statements with evidence links. |
| 1. Data quality and safe cleanup | **Partial** | Produce a complete row-level manifest with batch/row IDs, latest event, identity decision, evidence, canonical link, dependencies, and proposed disposition. Reconcile the 3 failed promotion rows and 9 rows lacking approved identity decisions. No cleanup writes until the manifest and before/after plan are reviewed. |
| 2. Import and publication lifecycle | **Partial / broad automated coverage exists** | Exercise a representative end-to-end source-to-publication flow, including existing-canonical matches, evidence requirements, conflict resolution, rollback/reversal, and public visibility. Document remaining failure categories and retry policy. |
| 3. Master dataset and national coverage | **Partial** | Coverage reporting now includes geography distributions and relationship gaps, but the approved consolidated master dataset, source provenance, required fields by institution type, and national coverage targets still need formalization and review. Do not infer national completeness from the current 19-institution test dataset. |
| 4. Public discovery | **Core flows implemented; further acceptance testing needed** | Search/filter/alias, pagination, empty-state, inactive relation and public-profile checks have automated coverage. Still verify mobile layout, accessibility, performance, representative province/district/program combinations, and freshness/provenance of student-facing fields. |
| 5. Location and maps | **Partial** | Geography relations and nearby-search tests exist. Confirm map provider, license/cost, consent UX, coordinate accuracy/provenance, manual fallback, and outage/permission-denial behavior before calling map discovery complete. |
| 6. Ratings and comments | **Not verified as release-ready** | Confirm models and flows for rating scope, written comments, moderation, abuse reporting, anti-spam/rate limits, privacy, and sample-size/date display. Keep public submissions disabled until moderation tests pass. |
| 7. Universal reputation | **Pending methodology sign-off** | Inventory source permissions; preserve scales, dates, identities and source URLs; define and test a transparent versioned methodology. Do not combine scores until methodology is reviewed. |
| 8. AI research/submission agent | **Components/tests exist; end-to-end governance not yet proven** | Audit source adapters and prove source → snapshot → extraction → duplicate/conflict detection → human review. Verify retry/alert controls and prove the agent cannot silently publish or overwrite canonical data. |
| 9. SEO, security and operations | **Partially implemented** | SEO, sitemap/robots, access-control, deployment and monitoring tests/runbook exist. Finish a release-readiness audit covering roles, rate limits, secret handling, retention/privacy, recovery and alerting. Re-run deployment diagnostics on current main. |
| 10. Release and governance | **Pending final sign-off** | Produce a dated evidence package, full CI and public-monitor results, current diagnostics, known-limitations list, data-freshness schedule, rollback/recovery plan, and explicit release decision. |

## Immediate next actions

1. Run Hostinger read-only diagnostics against the current main revision so the revision gate can report a fresh, meaningful result.
2. Generate the row-level import/promotion review manifest; classify the remaining runtime exceptions without exposing raw exception messages or source payloads.
3. Reconcile the nine import rows without approved identity decisions and zero open conflicts, using row IDs and evidence—not bulk actions.
4. Complete the Phase 3 coverage target/source-data plan and Phase 4 mobile/accessibility/performance acceptance checks.
5. Keep ratings/reputation and AI publishing gated until their moderation, provenance, and human-approval criteria are demonstrably met.

## Safety boundary

This audit is descriptive and read-only. It does not authorize production writes, bulk retries, canonical merges, deletes, or publication changes. Any such action requires a row-level proposed change set, expected before/after counts, explicit approval, and a recovery plan.
