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
| Duplicate and incomplete-row audit | Implemented in code | PRs #244–#246. Review report coverage and use the audit to create a row-by-row disposition. |
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

## Historical data note
A report captured on 2026-10-16 is historical and must not be treated as the current database state. It reported warning conditions including 10 active institutions without active campuses in that snapshot. Refresh diagnostics before using those counts to decide cleanup or publication.

## Evidence required to close a row
Record requirement ID, relevant code paths, test name and result, PR/commit, live run/report URL when applicable, limitations and date. A green CI badge alone does not prove production correctness.
