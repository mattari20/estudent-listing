# Row-by-row audit: unverified live institution, programme, and campus records

Audit date: 2026-10-26  
Repository baseline: main at c04ddbb75ce7989df19efec8de954515bd3e5fe0  
Related issue: https://github.com/mattari20/estudent-listing/issues/585  
Evidence register: https://github.com/mattari20/estudent-listing/blob/main/docs/step3-offering-gap-evidence-register.csv

## Safety status and limitation

This is a read-only evidence audit and proposed disposition, not a database deletion list. The 36 rows below are programme-offering gap rows. Their program_id, institution_id and candidate_campus_id are IDs in the evidence register; they are not proof that the live deployment has those same rows/IDs, and the CSV is not canonical database data.

The latest known live checks were blocked because Hostinger ran f44ab753bebcbb296809796540f4d7cb78b36a05 while the expected application revision was c04ddbb75ce7989df19efec8de954515bd3e5fe0. The integrity audit explicitly did not query the live database under that mismatch. Source-refresh readiness also found official_source_refresh_events missing because migration 2026_10_25_000002_create_official_source_refresh_events_table was not applied. Current live existence, foreign-key dependencies, duplicate relationships and usage counts are therefore unknown, not zero. No production mutation or migration was run.

## Row-by-row review (36 rows)

“Source found” is not proof of current availability or campus correctness. All URLs below are recorded in the repository evidence register, not independently re-verified by this report.

| # | Institution ID | Programme ID | Programme | Candidate campus ID / label | Recorded programme evidence | Recorded campus evidence | Proposed disposition |
|---:|---:|---:|---|---|---|---|---|
| 1 | 1 | 33 | BS Biotechnology | 11 / Main Campus | https://gcu.edu.pk/IIBT.php?pg=acdProg | https://gcu.edu.pk/about.php?pg=GCUOrdinance02 | HOLD; verify current programme and campus association. |
| 2 | 1 | 34 | BS English | 11 / Main Campus | https://gcu.edu.pk/admissions.php?pg=bs-program | https://gcu.edu.pk/about.php?pg=GCUOrdinance02 | HOLD; verify current programme and campus association. |
| 3 | 2 | 16 | BSc. Applied Computing | 12 / Main Campus | https://www.uet.edu.pk/academics/academics/undergraduate-academic-programs/ | same programme page | VALIDATE ONLY; register says ready-for-validation, not write-approved. |
| 4 | 3 | 3 | BS Computer Science | 13 / Quaid-e-Azam Campus | https://pu.edu.pk/home/admission_notice/698 | same admission notice | HOLD; verify current campus name and active offering. |
| 5 | 3 | 35 | BS Data Science | 13 / Quaid-e-Azam Campus | https://pu.edu.pk/home/admission_notice/698 | same admission notice | HOLD; verify current campus name and active offering. |
| 6 | 3 | 36 | BS Software Engineering | 13 / Quaid-e-Azam Campus | https://pu.edu.pk/home/admission_notice/698 | same admission notice | HOLD; verify current campus name and active offering. |
| 7 | 3 | 37 | BS Information Technology | 13 / Quaid-e-Azam Campus | https://pu.edu.pk/home/admission_notice/698 | same admission notice | HOLD; verify current campus name and active offering. |
| 8 | 4 | 21 | BS English Language & Literature | 14 / Main Campus | https://ucp.edu.pk/faculty-of-languages-literature/programmes/undergraduate/bs-english-2/ | https://ucp.edu.pk/faculty-of-languages-literature/ | HOLD; verify campus association. |
| 9 | 5 | 77 | BS Biochemistry | 15 / Main Campus | https://www.uvas.edu.pk/Admissions/undergraduate/index.htm | https://uvas.edu.pk/news-events/2026/orientation.php | HOLD; campus evidence is indirect; confirm exact offering. |
| 10 | 5 | 78 | BS Environmental Science | 15 / Main Campus | https://www.uvas.edu.pk/Admissions/undergraduate/index.htm | https://uvas.edu.pk/news-events/2026/orientation.php | HOLD; confirm exact offering and campus. |
| 11 | 5 | 79 | BS Human Nutrition and Dietetics | 15 / Main Campus | https://www.uvas.edu.pk/Admissions/undergraduate/index.htm | https://uvas.edu.pk/news-events/2026/orientation.php | HOLD; confirm exact offering and campus. |
| 12 | 5 | 85 | Bachelor of Business Administration | 15 / Main Campus | https://www.uvas.edu.pk/Admissions/undergraduate/index.htm | https://uvas.edu.pk/news-events/2026/orientation.php | HOLD; confirm exact offering and campus. |
| 13 | 6 | 60 | BS Computer Science | 16 / Township Main Campus | https://ue.edu.pk/degreeprograms.php | https://ue.edu.pk/meritlist.html | HOLD / wrong-campus signal. Target association only if confirmed; do not delete programme or institution based on this. |
| 14 | 6 | 61 | BS Information Technology | 16 / Township Main Campus | https://ue.edu.pk/degreeprograms.php | https://ue.edu.pk/meritlist.html | HOLD / wrong-campus signal. Find correct campus before any change. |
| 15 | 6 | 62 | BS English | 16 / Township Main Campus | https://ue.edu.pk/degreeprograms.php | https://www.ue.edu.pk/campus.php?id=40 | HOLD; recheck current offering and dependencies. |
| 16 | 6 | 63 | BS Business Analytics | 16 / Township Main Campus | https://ue.edu.pk/programs.php?id=200 | https://ue.edu.pk/meritlist.html | HOLD; verify Township campus and current programme. |
| 17 | 8 | 39 | BSc Management Science | 2 / LUMS Main Campus | https://admission.lums.edu.pk/undergraduate-programmes | https://www.lums.edu.pk/contact-us | HOLD; contact page is not programme-specific campus proof. |
| 18 | 8 | 40 | BSc Economics | 2 / LUMS Main Campus | https://admission.lums.edu.pk/undergraduate-programmes | https://www.lums.edu.pk/contact-us | HOLD; verify exact title and campus. |
| 19 | 9 | 1 | BS (Computer Sciences) | 1 / COMSATS Lahore | https://lahore.comsats.edu.pk/cs/programs.aspx | https://lahore.comsats.edu.pk/cs/bscs.aspx | HOLD; confirm active status and canonical identity. |
| 20 | 10 | 69 | BS Game Design | 18 / Main Campus | https://soca.uol.edu.pk/course/bs-in-game-design/ | https://uol.edu.pk/contact-us/ | HOLD; verify current programme and campus; contact page alone is weak evidence. |
| 21 | 11 | 71 | BS Accounting & Finance | 19 / Islamabad Main Campus | NONE RECORDED | NONE RECORDED | NOT VERIFIED; highest-priority quarantine candidate, pending exact live ID/dependency/duplicate checks. |
| 22 | 12 | 11 | BS English | 20 / Islamabad Main Campus | https://www.numl.edu.pk/programs/undergraduate | same programmes page | HOLD; verify current offering and campus. |
| 23 | 12 | 43 | BS Computer Science | 20 / Islamabad Main Campus | https://www.numl.edu.pk/programs/undergraduate | same programmes page | HOLD; verify current offering and campus. |
| 24 | 12 | 44 | BS Artificial Intelligence | 20 / Islamabad Main Campus | https://www.numl.edu.pk/programs/undergraduate | same programmes page | HOLD; verify current offering and campus. |
| 25 | 12 | 45 | BS Software Engineering | 20 / Islamabad Main Campus | https://www.numl.edu.pk/programs/undergraduate | same programmes page | HOLD; verify current offering and campus. |
| 26 | 13 | 74 | Bachelor of Design | 3 / Lahore Main Campus | https://www.nca.edu.pk/prospectus/bfa-propspectus-25-8-24rv.pdf (register status: not-verified) | NONE RECORDED | NOT VERIFIED; source not validated as current evidence for this exact record; campus evidence missing. |
| 27 | 14 | 6 | BS Computer Science | 4 / Islamabad Main Campus | https://www.bahria.edu.pk/Home/AdmissionRoadmap?campus=islamabad&programType=UnderGraduate | same admission roadmap | HOLD; verify current campus name and offering. |
| 28 | 15 | 49 | BS Artificial Intelligence | 5 / Islamabad Main Campus | https://webdata.au.edu.pk/Pages/Admission/eligibility_criteria.aspx | https://webdata.au.edu.pk/Pages/Admission/newpages/Bachelor_programs.aspx | HOLD; confirm programme and Islamabad campus together. |
| 29 | 15 | 50 | BS Cyber Security | 5 / Islamabad Main Campus | https://webdata.au.edu.pk/Pages/Admission/eligibility_criteria.aspx | https://webdata.au.edu.pk/Pages/Admission/newpages/Bachelor_programs.aspx | HOLD; confirm programme and campus. |
| 30 | 15 | 51 | BS Information Technology | 5 / Islamabad Main Campus | https://webdata.au.edu.pk/Pages/Admission/eligibility_criteria.aspx | https://webdata.au.edu.pk/Pages/Admission/newpages/Bachelor_programs.aspx | HOLD; confirm programme and campus. |
| 31 | 16 | 7 | BS Computer Science | 6 / Lahore Main Campus; delivery marked online | https://vu.edu.pk/AboutUs/ProgramDetails?StudyProgramID=4 | https://vu.edu.pk/PVCs/Default?p=punjab | HOLD; verify virtual delivery model and whether campus association is appropriate. |
| 32 | 16 | 55 | BS Biotechnology | 6 / Lahore Main Campus; delivery marked online | https://vu.edu.pk/AboutUs/ProgramsOffered?Degree=BS | https://vu.edu.pk/PVCs/Default?p=punjab | HOLD; verify programme and delivery/campus model; avoid deleting valid virtual programme due to a link issue. |
| 33 | 17 | 8 | BS Computer Science | 7 / Gilgit Main Campus | https://admissions.kiu.edu.pk/p/bs-fall-admissions-2025-ZSARUH | same 2025 admission page | HOLD; current-cycle status needs confirmation. |
| 34 | 18 | 30 | BS Polymer Engineering | 8 / Faisalabad Main Campus | https://www.ntu.edu.pk/admissions | same admissions page | HOLD; verify current offering and campus. |
| 35 | 18 | 31 | Bachelor of Fashion Design | 8 / Faisalabad Main Campus | https://www.ntu.edu.pk/admissions | same admissions page | HOLD; verify current offering and campus. |
| 36 | 19 | 10 | BS Computer Science | 9 / Islamabad Main Campus | https://nust.edu.pk/admissions/undergraduates/list-of-ug-programmes-and-institutions | same programme list | HOLD; verify current NUST campus association and active offering. |

## Institution and campus-level disposition

The institution IDs above are parent IDs in the evidence register, not independently verified live IDs. No row proves an entire institution is invalid. Do not delete institution records based on this audit. Do not delete programme master records merely because one campus offering is unverified or linked to a wrong campus. First check other active offerings and prefer correcting or deactivating only the incorrect offering association.

## Duplicate and dependency impact — required preflight

Exact live duplicate counts and dependency counts are not currently available. Before any write, the revision-matched read-only preflight must report for every proposed target:
1. Exact live table, primary-key ID, status flags and updated timestamp.
2. Foreign-key/application references from offerings, campuses, admissions, fees, eligibility, resources, reviews/ratings, evidence/source links, imports, discovery candidates, promotion events and audit logs.
3. Duplicate identity candidates using normalized name, institution, campus, level and stable slug/identity key, with exact IDs and confidence.
4. Whether a programme has another active offering at a verified campus.
5. Official-source URL, fetch timestamp/status, relevant programme/campus wording and review disposition.
6. Per-row proposed action and rationale: retain, refresh evidence, correct association, quarantine/deactivate, or delete.

No SQL mutation, migration, bulk delete, candidate purge, or history cleanup is authorized by this report.

## Proposed deletion/quarantine plan

### Stage A — read-only preflight
1. Deploy the approved application revision through the existing controlled Hostinger workflow and verify the deployed revision matches main before database checks.
2. Do not run migrations during code deployment. Resolve the missing official_source_refresh_events migration only through a separately approved migration process.
3. Re-run read-only preflight and export restricted evidence with exact live IDs, duplicate matches and dependency counts. Stop on revision/schema mismatch or query failure.
4. Re-fetch official sources and classify all 36 rows. Prioritize programme 71 / institution 11 and programme 74 / institution 13, but do not assume register IDs equal live IDs.

### Stage B — reviewable action list
5. Generate one action-CSV row per exact live record with action, reason, source URL, duplicate IDs, dependency counts and impact.
6. Prefer reversible quarantine/deactivation or association correction over hard deletion. Preserve source snapshots, import/discovery batches, promotion-failure events and audit history.
7. Exclude rows with unresolved references from deletion and mark them for a specific disposition.

### Stage C — execution gate
8. Present exact record IDs and actions for review; no production write until the list is reviewed and an approved execution path is available.
9. If approved, execute the smallest idempotent batch with precondition checks, transaction boundaries where supported, and an audit record. Abort if preconditions changed.
10. Re-run live integrity checks and verify listing/detail pages and related-record counts; record before/after counts and workflow links.

## Summary

- Evidence-register rows reviewed: 36/36.
- Explicit not-verified rows: 2 — programme 71 / institution 11 (no programme/campus URL recorded); programme 74 / institution 13 (source status not verified and no campus URL recorded).
- Explicit wrong-campus signals: 2 — programme 60 / institution 6 and programme 61 / institution 6.
- Ready-for-validation only: 1 — programme 16 / institution 2; not approved for canonical write.
- Remaining rows: 31 need current programme/campus evidence validation.
- Confirmed live canonical deletions: 0.
- Confirmed duplicate counts and live dependency counts: unavailable until a revision-matched read-only query succeeds.
- Production mutations/migrations performed by this audit: none.

## References
- Tracking issue and acceptance criteria: https://github.com/mattari20/estudent-listing/issues/585
- Evidence register: https://github.com/mattari20/estudent-listing/blob/main/docs/step3-offering-gap-evidence-register.csv
- Expected application revision: c04ddbb75ce7989df19efec8de954515bd3e5fe0
- Last known deployed revision: f44ab753bebcbb296809796540f4d7cb78b36a05
