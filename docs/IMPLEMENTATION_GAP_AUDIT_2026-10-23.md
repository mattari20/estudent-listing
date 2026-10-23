# Institutes Directory — implementation and gap audit

**Audit date:** 2026-10-23  
**Repository:** `mattari20/estudent-listing`  
**Audited main revision:** `130133a134dabc5a37c0db6d02db2f8083bca263`

## Executive finding

The directory's application and CI are substantially further along than the older proposed roadmap checklist suggests. Recent merged work covers import-failure triage, identity/conflict review reporting, geography coverage, public search/profile filtering, pagination, empty states, and deployment runbook documentation. This document remains an evidence-based gap register, not a claim that every roadmap phase is release-ready.

## Latest verification evidence

- Laravel CI: pass on current main revision `130133a134dabc5a37c0db6d02db2f8083bca263` (run [39881774129](https://github.com/mattari20/estudent-listing/actions/runs/39881774129)).
- Test workflow: pass on current main revision (run [39881774101](https://github.com/mattari20/estudent-listing/actions/runs/39881774101)).
- Frontend asset build: pass (run [39881774165](https://github.com/mattari20/estudent-listing/actions/runs/39881774165)).
- Public-site monitor: pass (run [39881774172](https://github.com/mattari20/estudent-listing/actions/runs/39881774172)).
- Hostinger read-only diagnostics: pass (run [39881774119](https://github.com/mattari20/estudent-listing/actions/runs/39881774119)). The diagnostics reported `deployment_revision_status=APPLICATION_CODE_MATCH`; the deployed checkout was `f75243b49d58ae13c856a72cb542f814eed33b6d` and the workflow verified the deployable application/runtime paths against the expected application-code revision. Documentation-only commits do not require deployment when those paths match.

## Current read-only production findings

The latest successful diagnostics run completed without changing production data. The run includes canonical identity export, profile-to-canonical trace, aggregate data-gap report, institution import-row trace, campus import-row trace and application-revision comparison.

- Required schema tables missing: **0** in the recorded diagnostic snapshot.
- Campuses: **22 total, all active** in the recorded snapshot.
- Campus geography: **22/22 linked; 0 without geography; 0 active campuses with missing/inactive geography** in the recorded snapshot.
- Import pipeline: the previous detailed audit identified failed promotion rows and institution import rows without approved identity decisions. Reconcile these by stable row IDs against the latest output before making decisions; do not assume historical counts are unchanged.
- Open import conflicts: **0** in the previous audit snapshot.
- The `institutes:data-gap-record-audit --summary` section was skipped by the deployed application because the deployed revision does not expose that command option. This is a remaining diagnostics capability gap, not a failed read-only diagnostics run.

These are read-only observations. They do not establish nationwide data completeness or external accuracy of every institution record. No production records were changed.

## Roadmap gap register

| Phase | Evidence-backed status | Missing work / exit gate |
|---|---|---|
| 0. Documentation and baseline | **Mostly implemented; current CI, public monitor and revision comparison pass** | Map each requirement to code, tests and live checks; keep evidence links current; investigate the skipped record-audit summary capability. |
| 1. Data quality and safe cleanup | **Partial** | Produce a complete row-level manifest with batch/row IDs, latest event, identity decision, evidence, canonical link, dependencies and proposed disposition. Reconcile failed promotion rows and rows lacking approved identity decisions. No cleanup writes until the manifest and before/after plan are reviewed. |
| 2. Import and publication lifecycle | **Partial / broad automated coverage exists** | Exercise a representative end-to-end source-to-publication flow, including existing-canonical matches, evidence requirements, conflict resolution, rollback/reversal and public visibility. Document remaining failure categories and retry policy. |
| 3. Master dataset and coverage | **Partial** | Coverage reporting includes geography distributions and relationship gaps, but the approved consolidated master dataset, source provenance, required fields by institution type and national coverage targets still need formalization. Do not infer national completeness from the current test dataset. |
| 4. Public discovery | **Core flows implemented; further acceptance testing needed** | Search/filter/alias, pagination, empty-state, inactive-relation and public-profile checks have automated coverage. Still verify mobile layout, accessibility, performance, representative province/district/program combinations and freshness/provenance of student-facing fields. |
| 5. Location and maps | **Partial** | Confirm map provider, license/cost, consent UX, coordinate accuracy/provenance, manual fallback, and outage/permission-denial behavior before calling map discovery complete. |
| 6. Ratings and comments | **Not verified as release-ready** | Confirm models and flows for rating scope, written comments, moderation, abuse reporting, anti-spam/rate limits, privacy and sample-size/date display. Keep public submissions disabled until moderation tests pass. |
| 7. Universal reputation | **Pending methodology sign-off** | Inventory source permissions; preserve scales, dates, identities and source URLs; define and test a transparent versioned methodology. Do not combine scores until methodology is reviewed. |
| 8. AI research/submission agent | **Components/tests exist; end-to-end governance not yet proven** | Audit source adapters and prove source → snapshot → extraction → duplicate/conflict detection → human review. Verify retry/alert controls and prove the agent cannot silently publish or overwrite canonical data. |
| 9. SEO, security and operations | **Partially implemented; current operational checks pass** | Finish a release-readiness audit covering roles, rate limits, secret handling, retention/privacy, recovery and alerting. The record-audit summary option remains unavailable in the deployed app. |
| 10. Release and governance | **Pending final sign-off** | Produce a dated evidence package, full CI and public-monitor results, current diagnostics, known-limitations list, data-freshness schedule, rollback/recovery plan and explicit release decision. |

## Immediate next actions

1. Implement or reconcile a safe summary mode for the record-level data-gap audit so production diagnostics can report aggregate counts without exposing unnecessary row-level details.
2. Generate the row-level import/promotion review manifest; classify remaining runtime exceptions without exposing raw exception messages or source payloads.
3. Reconcile import rows without approved identity decisions using row IDs and evidence—not bulk actions.
4. Complete the Phase 3 coverage target/source-data plan and Phase 4 mobile/accessibility/performance acceptance checks.
5. Keep ratings/reputation and AI publishing gated until moderation, provenance and human-approval criteria are demonstrably met.

## Safety boundary

This audit is descriptive and read-only. It does not authorize production writes, bulk retries, canonical merges, deletes or publication changes. Any such action requires a row-level proposed change set, expected before/after counts, explicit approval and a recovery plan.
