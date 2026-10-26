# Phase 2 Step 3 — official-source review notes (2026-10-26)

## Scope and safety

This is a research note for the 36-row register in `docs/step3-offering-gap-evidence-register.csv`. It does not modify production data. URLs below are official institution pages discovered during the first two evidence-review batches. A page confirming a programme's existence does **not** by itself prove that a specific programme-campus-delivery relationship is correct.

The successful read-only audit is Run #11:
- Workflow: https://github.com/mattari20/estudent-listing/actions/runs/40283314304
- Artifact: https://github.com/mattari20/estudent-listing/actions/runs/40283314304/artifacts/12632758478
- 36 rows in both reports; 35 blocked; 1 ready for manual review only; zero database mutations; no migrations.

## Direct official-source findings

| Register row / programme | Official evidence found | What it supports | Remaining caveat |
|---|---|---|---|
| line 4 — UET Lahore, BSc. Applied Computing (program_id 16) | https://uet.edu.pk/academics/academics/undergraduate-academic-programs/ | The current UET catalogue lists BSc. Applied Computing and explicitly identifies Main Campus UET Lahore. | Confirm candidate campus ID 12 is the canonical Main Campus record and preserve source capture/date before proposing a relationship. |
| lines 10–13 — UVAS Lahore: BS Biochemistry, BS Environmental Science, BS Human Nutrition & Dietetics, BBA | https://www.uvas.edu.pk/Admissions/undergraduate/ ; https://www.uvas.edu.pk/news-events/2026/orientation.php | Current UVAS undergraduate listings identify the programmes; campus-specific table/orientation information can help distinguish Lahore, Ravi, Jhang and Narowal. | For each row, cite the exact campus column/entry; do not infer all offerings belong to Lahore just because the institution is UVAS Lahore. |
| line 20 — COMSATS Lahore, BS Computer Science (program_id 1) | https://lahore.comsats.edu.pk/cs/programs.aspx ; https://lahore.comsats.edu.pk/cs/bscs.aspx | The Lahore campus CS department lists BS Computer Science and provides a programme page. | Confirm candidate campus ID 1 is the matching canonical Lahore campus before any write. |
| lines 18–19 — LUMS, BSc (Honours) Management Science / Economics | https://admission.lums.edu.pk/undergraduate-programmes | Official LUMS undergraduate programme page lists both degree programmes. | The current general contact page is not direct evidence for the programme-campus relationship; replace it with a suitable official campus/location source or document why the canonical campus association is unambiguous. |
| line 21 — University of Lahore, BS Game Design (program_id 69) | https://soca.uol.edu.pk/course/bs-in-game-design/ | Official School of Creative Arts page confirms BS Game Design and current programme details. | The general contact page does not independently prove which campus delivers the programme. Obtain a programme/campus-specific official source. |
| line 28 — Bahria University, BS Computer Science (program_id 6) | https://www.bahria.edu.pk/Home/AdmissionRoadmap?campus=islamabad&programType=UnderGraduate | Official admission roadmap explicitly scoped to Islamabad E-8 campus lists BS Computer Science. | Confirm candidate campus ID 4 maps to Islamabad E-8 (not another Islamabad campus) before repair proposal. |
| lines 29–31 — Air University, BS Artificial Intelligence / Cyber Security / Information Technology | https://webdata.au.edu.pk/Pages/Admission/newpages/undergraduateprogs.aspx ; https://webdata.au.edu.pk/Pages/Admission/newpages/Bachelor_programs.aspx | Official Air University pages list the named computing programmes. | Exact campus must be supported per row; a programme list without a campus qualifier is not enough to establish Islamabad-campus delivery. |
| lines 32–33 — Virtual University, BS Computer Science / BS Biotechnology | https://vu.edu.pk/AboutUs/ProgramDetails?StudyProgramID=4 ; https://www.vu.edu.pk/AcademicDepartment/ProgramDetails?StudyProgramID=116 ; https://vu.edu.pk/AboutUs/ProgramsOffered?Degree=BS | Official VU pages confirm the two degrees. The Biotechnology page explains that lab-city seats and designated campuses matter. | Do not model virtual programmes as a conventional on-campus offering by default. Review delivery mode and lab/campus relationship separately. |
| line 37 — NUST, BS Computer Science (program_id 10) | https://nust.edu.pk/admissions/undergraduates/list-of-ug-programmes-and-institutions/ | Current official table lists BS Computer Science at SEECS Islamabad and also at NUST Balochistan Campus and PNEC Karachi. | Evidence must specifically tie this row to SEECS Islamabad and canonical campus ID 9, not merely establish that NUST offers BSCS somewhere. |
| lines 5–8 — Punjab University computing rows | https://pu.edu.pk/home/admission_notice/698 | Official 2026–27 admission notice distinguishes FCIT Old Campus and New Campus, and describes BSCS specializations including AI, Software Engineering and IT. | Register rows named BS Data Science, BS Software Engineering and BS Information Technology need exact canonical programme-name/ID validation; do not assume they are standalone programme records or that all belong to the same campus. |
| lines 29–31 — Air University programme names | https://webdata.au.edu.pk/Pages/Admission/newpages/undergraduateprogs.aspx | Official page includes BS Information Technology, BS Cyber Security and BS Accounting and Finance; related official pages list AI. | Verify each row's actual institution/program IDs and campus scope before making a repair proposal. |
| line 34 — KIU, BS Computer Science | https://admissions.kiu.edu.pk/p/bs-fall-admissions-2025-ZSARUH | The registered official admissions link is a KIU admissions page. | The 2025 cycle is historical; obtain a current programme page or current admissions source and verify Gilgit campus association. |
| lines 35–36 — NTU, BS Polymer Engineering / Bachelor of Fashion Design | https://www.ntu.edu.pk/admissions | Official admissions landing page is a starting point only. | Replace with direct programme and Faisalabad-campus sources for each degree; do not infer programme-campus relationships from the generic admissions landing page. |
| line 27 — NCA, Bachelor of Design | https://www.nca.edu.pk/prospectus/bfa-propspectus-25-8-24rv.pdf | Register currently points to a prospectus PDF. | No campus source URL is recorded. Verify the exact current degree name, current prospectus and Lahore campus. |
| line 22 — FUUAST, BS Accounting & Finance | No URL recorded in the register. | No source claim can be verified from the register as written. | Add an official programme source and campus source before treating this row as review-ready. |

## Important source/data-quality observations

1. Punjab University’s official notice makes programme taxonomy and campus specificity material. Do not create distinct programme relationships merely from a label in the register.
2. Virtual University’s delivery model must be represented accurately; a virtual degree with lab-city constraints is not automatically equivalent to a conventional campus-based offering.
3. General contact, faculty landing, merit-list, orientation, or admissions-index pages can be useful starting points, but should not be treated as proof of the exact programme-campus relationship unless the relevant claim is explicitly present.
4. The current report's `READY_FOR_MANUAL_REVIEW_ONLY` row is not permission to insert or activate an offering.
5. 35 of 36 rows remain blocked in the live audit. Keep the issue open until every row has evidence-based disposition and the final read-only audit has zero unexplained blockers.

## Next actions

- Capture row-specific official evidence and current dates for the remaining records.
- Resolve programme-name/ID and campus-ID mismatches, especially PU, UE Township, VU delivery mode, NUST SEECS, and missing FUUAST/NCA source pointers.
- Update the version-controlled register only where the source directly supports the exact claim.
- Re-run read-only preflight/reconciliation; produce a row-level repair proposal with duplicate checks.
- No production inserts, updates, deletes, migrations, promotions, approvals or publications are authorized by this note.
