# Implementation Status and Gap Register

**Last reconciled:** 2026-10-22  
**Scope:** repository and prior available diagnostic evidence; not a fresh live database audit.

## Status definitions
- **Implemented in code:** source code exists.
- **Automated-test verified:** relevant CI/test evidence is recorded for the reviewed commit.
- **Live verified:** current deployed behaviour is confirmed by a dated monitor/report.
- **Planned:** future requirement not yet confirmed as implemented.
- **Needs investigation:** evidence is missing or historical only.

## Current register

| Area | Current assessment | Evidence / remaining work |
|---|---|---|
| Laravel directory under `/institutes/` | Implemented in code | Verify current deployed revision. |
| Public listing and institution detail | Implemented; previous HTTP checks passed | PR #239 adjusted default page size to 24; PR #240 added content-aware assertions. Confirm latest monitor result. |
| Search and filters | Routes and UI are present | Full filter correctness and edge cases require systematic tests. |
| Import batches and validation | Implemented in code | Test the complete import lifecycle and error clarity. |
| Explicit evidence-backed promotion/publication | Safeguards present in code | End-to-end positive/negative workflow verification remains. |
| Duplicate and incomplete-row audit | Implemented in code; promotion-failure priority test merged | PRs #244–#246 and #255. Failed promotion events now take priority over a missing-approval recommendation, while private failure details remain excluded. Refresh the live report and inspect exact rows before remediation. |
| Canonical identity collisions and aliases | Read-only audit implemented in code | PRs #248–#249. Review real collision groups before any canonical changes. |
| Cleanup/deletion | Not approved as bulk action | Need dry-run plan, dependency checks, retention policy and explicit scope. |
| Campus/geography/program/offering relationships | Relationship diagnostics exist | Refresh report; compare results with approved data-coverage requirements. |
| Source evidence freshness/coverage | Evidence workflow exists | Historical report showed low/zero evidence in a prior snapshot; refresh and test current source evidence. |
| Google/map-based discovery | Planned requirement | Specify provider, consent, coordinate provenance, nearby search and fallback behaviour. |
| User ratings for institutions/campuses | Planned requirement | Design schema, moderation, verification and anti-fraud controls. |
| Visitor comments/reviews | Planned requirement | Design comments UI, moderation, reporting, privacy and anti-spam. |
| Universal composite rating | Future requirement | Define transparent methodology and external-source normalization; weights are not decided. |
| AI research and data-submission agent | Planned/partially explored historically; current end-to-end status unverified | Audit current adapter/jobs and verify source → snapshot → evidence → proposal → human review flow. |
| Full education-level coverage | Product requirement; completeness unverified | Define taxonomy and import coverage by institution type/region. |
| SEO, schema and sitemap quality | Needs systematic verification | Run current audit; record indexability and canonical outcomes. |
| Security, roles and recovery | Partial implementation indicated | Verify roles, permissions, secrets, reset flows, backups/recovery and destructive-operation controls. |
| Canonical documentation set | Added by this documentation PR | Review for accuracy and merge after CI checks. |

## Dated diagnostic evidence

### 2026-10-22 — Hostinger read-only diagnostics
- Workflow: Hostinger Read-only Diagnostics, run [39758486228](https://github.com/mattari20/estudent-listing/actions/runs/39758486228), successful.
- Scope: read-only report; `mutation_performed: false`. This verifies the diagnostic command completed, not the correctness of every source fact or the entire live public experience.
- Institution import-row trace: 38 rows were reported. The safe review recommendations were 19 `retain_promoted_history`, 6 `review_rejection`, and 13 `review_promotion_decision`.
- Interpretation: rows can repeat across batches; these counts are import rows, not unique institutions. Recommendations do not authorize deletion, promotion or publication.
- Follow-up: review row IDs against batch, identity decision, source evidence, canonical dependencies and audit history before proposing any cleanup. Prefer reversible disposition over hard deletion.
- Public content monitor run [39758483404](https://github.com/mattari20/estudent-listing/actions/runs/39758483404) was still in progress at the time of this reconciliation; do not mark content-aware live verification complete until its final result is recorded.

### Historical data note
A report captured on 2026-10-16 is historical and must not be treated as the current database state. It reported warning conditions including 10 active institutions without active campuses in that snapshot. Refresh diagnostics before using those counts to decide cleanup or publication.

## Evidence required to close a row
Record requirement ID, relevant code paths, test name and result, PR/commit, live run/report URL when applicable, limitations and date. A green CI badge alone does not prove production correctness.

### 2026-10-22 — Promotion-failure audit priority
- PR [#255](https://github.com/mattari20/estudent-listing/pull/255) merged with two checks passed.
- The read-only import audit now prioritizes a recorded `promotion_failed` / `promotion_error` event over the fallback missing-approval disposition.
- A regression test confirms the recommendation is specific without exposing private failure text.
- Remaining action: refresh Hostinger diagnostics and use the current report to investigate each failed row. This change did not promote, publish, or delete any row.
