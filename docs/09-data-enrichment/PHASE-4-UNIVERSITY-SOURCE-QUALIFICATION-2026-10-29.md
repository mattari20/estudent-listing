# Phase 4 — University identity source qualification and discovery dry run

**Inspection date:** 2026-10-29  
**Repository base:** `a52c319fca085c9048bdadae4291366c66ebe6dd`  
**Scope:** Top-level institution identity only. No production imports, canonical writes, approvals, promotion, publication, deployment, migration execution, or cron changes.

## Readiness gate

The current `main` Test and Laravel CI workflows pass. Live Hostinger readiness is **blocked**:

- Hostinger read-only diagnostics report deployed checkout `abfc67e0b65c91974a16b82a380bb2d344204758` while current `main` is `a52c319fca085c9048bdadae4291366c66ebe6dd`. The application-code path comparison reported `APPLICATION_CODE_MATCH` for the diagnostic's resolved expected revision, but the expected revision itself is newer than the deployed checkout.
- The live diagnostic reports `schema_equivalence=UNVERIFIED` and `migration_origin=UNKNOWN` for the unrelated import-batch schema investigation. Do not repair that migration-ledger incident here.
- The current read-only diagnostics do not explicitly verify `institution_field_approvals`; therefore its production migration status is **unknown**.
- The public directory route returned HTTP 200, but `/institutes/api/v1/institutions?per_page=2` returned HTTP 404 with an HTML body, not the expected JSON paginator. The automated live integrity audit also stopped at application-code mismatch before running database checks.

Evidence: [Hostinger read-only diagnostics run](https://github.com/mattari20/estudent-listing/actions/runs/40849574701), [Automated Live Data Integrity Audit](https://github.com/mattari20/estudent-listing/actions/runs/40849574848), [Test](https://github.com/mattari20/estudent-listing/actions/runs/40849574784), [Laravel CI](https://github.com/mattari20/estudent-listing/actions/runs/40849574832).

## Qualification method and limits

The official HEC university profile pages below were inspected using the web research browser on 2026-10-29. Successful page renders exposed `text/html` and visible institution names; the browser does not expose the origin HTTP status code or raw response bytes. Three page-open requests timed out, although HEC search-index excerpts for those pages contained the institution identity. No browser-rendered or search-index content is represented as an application HTTP retrieval, and no SHA-256 fingerprint is invented.

- **18** HEC profile pages rendered as HTML in the research browser.
- **3** HEC profile page opens timed out (LUMS, University of the Punjab, University of Balochistan); identity text was present in official HEC search-index results but direct page retrieval remains unconfirmed.
- **0** live application HTTP status/content-type/fingerprint tuples were captured for these URLs in this phase.
- Candidate type is restricted to `public university` or `private university` where the HEC page/search result explicitly reported the sector. The HEC category/title is not used to infer campus/programme details.
- Every added identity profile is disabled (`monitoring.enabled=false`, `auto_discovery=false`, `use_ai=false`). The pre-existing GCU Lahore profile remains unchanged. Do not activate these profiles until direct source retrieval and live raw-page extractor results have been captured.
- HEC visible identity text is a strong source lead, not a claim that the deployed retriever has successfully fetched the source.

## Preliminary identity profile manifest

Status labels:
- `Rendered`: HEC page rendered and the institution name was visible in the returned page text; origin HTTP status and raw-body fingerprint were not exposed.
- `Search excerpt only`: the direct open timed out; the official HEC search-index result showed the institution identity, but direct reachability is not established.

| Profile key | Proposed official name | Alias | Normalized slug | Type supported by HEC sector | Official HEC source | Retrieval/evidence result |
|---|---|---|---|---|---|---|
| `gcu-lahore-institution-identity` | Government College University, Lahore | GCU | `government-college-university-lahore` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Punjab/Government-College-University%2C-Lahore.aspx) | Rendered; name visible |
| `hec-lums-institution-identity` | Lahore University of Management Sciences | LUMS | `lahore-university-of-management-sciences` | Private university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Punjab/Lahore-University-of-Management-Sciences.aspx) | Search excerpt only; name, private sector, Lahore visible |
| `hec-punjab-institution-identity` | University of the Punjab | PU | `university-of-the-punjab` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Punjab/University-of-the-Punjab.aspx) | Search excerpt only; name and campuses visible |
| `hec-uet-lahore-institution-identity` | University of Engineering & Technology, Lahore | UET Lahore | `university-of-engineering-and-technology-lahore` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Punjab/University-of-Engineering-and-Technology.aspx) | Rendered; name, public sector and Lahore visible |
| `hec-peshawar-institution-identity` | University of Peshawar | UoP | `university-of-peshawar` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/KPK/University-of-Peshawar.aspx) | Rendered; name, public sector and Peshawar visible |
| `hec-ned-institution-identity` | NED University of Engineering & Technology | NED | `ned-university-of-engineering-and-technology` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Sindh/NED-University-of-Engineering--Technology.aspx) | Rendered; name, public sector and Karachi visible |
| `hec-qau-institution-identity` | Quaid-i-Azam University | QAU | `quaid-i-azam-university` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Islamabad/Quaid-i-Azam-University.aspx) | Rendered; name, public sector and Islamabad visible |
| `hec-nust-institution-identity` | National University of Sciences & Technology | NUST | `national-university-of-sciences-and-technology` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Islamabad/National-University-of-Sciences--Technology.aspx) | Rendered; name and Islamabad visible |
| `hec-ucp-institution-identity` | University of Central Punjab | UCP | `university-of-central-punjab` | Private university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Punjab/University-of-Central-Punjab.aspx) | Rendered; name, private sector and Lahore visible |
| `hec-gcuf-institution-identity` | Government College University, Faisalabad | GCUF | `government-college-university-faisalabad` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Punjab/Government-College-University.aspx) | Rendered; name and introduction visible |
| `hec-air-institution-identity` | Air University, Islamabad | Air University | `air-university-islamabad` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Islamabad/Air-University.aspx) | Rendered; name, public sector and Islamabad visible |
| `hec-lcwu-institution-identity` | Lahore College for Women University | LCWU | `lahore-college-for-women-university` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Punjab/Lahore-College-for-Women-University.aspx) | Rendered; name and Lahore visible |
| `hec-iiui-institution-identity` | International Islamic University | IIUI | `international-islamic-university-islamabad` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Islamabad/International-Islamic-University.aspx) | Rendered; name, public sector and Islamabad visible |
| `hec-awkum-institution-identity` | Abdul Wali Khan University | AWKUM | `abdul-wali-khan-university` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/KPK/Abdul-Wali-Khan-University.aspx) | Rendered; name, public sector and Mardan visible |
| `hec-gom-institution-identity` | Gomal University | GU | `gomal-university` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/KPK/Gomal-University.aspx) | Rendered; name and Dera Ismail Khan visible |
| `hec-bzu-institution-identity` | Bahauddin Zakariya University | BZU | `bahauddin-zakariya-university` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Punjab/Bahauddin-Zakariya-University.aspx) | Rendered; name and Multan visible |
| `hec-sindh-institution-identity` | University of Sindh | SU | `university-of-sindh` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Sindh/University-of-Sindh.aspx) | Rendered; name and Sindh location visible |
| `hec-balochistan-institution-identity` | University of Balochistan | UOB | `university-of-balochistan` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Balochistan/University-of-Balochistan.aspx) | Search excerpt only; name, public sector and Quetta visible |
| `hec-buitems-institution-identity` | Balochistan University of Information Technology, Engineering & Management Sciences | BUITEMS | `balochistan-university-of-information-technology-engineering-management-sciences` | Public university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Balochistan/Balochistan-University-of-Information-Technology--Management-Sciences.aspx) | Rendered; name, category and Quetta visible |
| `hec-uol-institution-identity` | University of Lahore | UOL | `university-of-lahore` | Private university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Punjab/University-of-Lahore.aspx) | Rendered; name and Lahore visible |
| `hec-lgu-institution-identity` | Lahore Garrison University | LGU | `lahore-garrison-university` | Private university | [HEC profile](https://www.hec.gov.pk/english/universities/Pages/Punjab/Lahore-Garrison-University.aspx) | Rendered; name and Lahore visible |

### Evidence fingerprint and observation metadata

No raw HEC response body was available from the research browser, so source `snapshot_id`, SHA-256 content fingerprint, app-observed timestamp, and raw HTTP headers are intentionally **not fabricated**. The isolated tests create synthetic snapshots with reproducible SHA-256 fingerprints and synthetic observation timestamps; these are test artefacts, not live-source evidence.

## Deterministic pipeline coverage

The PR adds a feature test that runs all 21 configured identity profiles through the existing `InstituteDiscoveryAgentService` with synthetic HTML and an isolated test database. It checks that:
- the configured identity term is corroborated across title/body, application-name metadata, and main-heading layouts;
- one candidate row is staged per synthetic profile with source, snapshot, evidence, content hash, and extractor provenance;
- normalized slugs and source URLs are unique;
- review-package manifests declare no canonical mutations or automatic approval;
- no canonical Institution, Campus, Program, or ProgramOffering is created or published;
- a failed HTTP retrieval is recorded in `source_retrieval_runs` with HTTP status/content type and creates no snapshot or candidate data.

Existing tests continue to cover missing identity terms, invalid identity configuration, same-slug duplicates, unchanged-content batch reuse, and changed-content history. These fixtures do not prove that the real HEC pages will pass the production retriever and extractor. That remains a prerequisite before enabling the profiles.

## Dry-run accounting

| Metric | Result |
|---|---:|
| Identity profiles configured for the isolated test | 21 |
| Unique normalized slugs | 21 |
| HEC pages rendered in research browser | 18 |
| HEC page opens timed out (search-index text only) | 3 |
| Official HEC page identity text corroborated in browser/search output | 21 |
| Direct application HTTP status/content-type/fingerprint tuples captured | 0 |
| Synthetic candidate rows staged in isolated test (CI verified) | 21 |
| Canonical Institutions created in isolated test | 0 |
| Production candidates imported/staged by this phase | 0 |
| Production approvals/promotions/publications | 0 |
| Full source qualifications meeting all live-retrieval requirements | 0 |

**Disposition:** All 21 are preliminary source-backed profiles for isolated fixture validation only. Eighteen have a browser-rendered HEC page; three remain direct-retrieval-blocked. No profile is newly activated by this change.
