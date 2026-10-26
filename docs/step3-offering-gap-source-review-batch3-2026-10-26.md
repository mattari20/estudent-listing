# Phase 2 Step 3 — official-source evidence review, batch 3 (2026-10-26)

## Scope and safety

This is a documentation-only follow-up to the 36-row offering-gap evidence register. It records source checks and conservative row dispositions. It does not authorize canonical data writes, migrations, promotions, approvals, publication, or deletion.

Baseline read-only live audit: [Run #11](https://github.com/mattari20/estudent-listing/actions/runs/40283314304), artifact [12632758478](https://github.com/mattari20/estudent-listing/actions/runs/40283314304/artifacts/12632758478). Baseline was 36 rows, 35 blocked, 1 ready for manual review only, zero database mutations.

## Batch 3 findings

| Register row / programme | Official source | Evidence and disposition |
|---|---|---|
| Program IDs 60 and 61 — University of Education, BS Computer Science / BS Information Technology, candidate campus 16 Township | https://www.ue.edu.pk/campus.php?id=40 ; https://ue.edu.pk/degreeprograms.php | The university-wide degree list can establish that a degree exists at the institution, but the Township campus page does not list BSCS or BSIT. Keep both relationships blocked unless a current campus-specific source proves Township delivery. |
| Program ID 62 — University of Education, BS English, candidate campus 16 Township | https://www.ue.edu.pk/campus.php?id=40 | Township campus page lists BS English. This strengthens programme-campus evidence, but canonical campus ID 16 and the candidate's current identity must still be verified before proposing a relationship. |
| Program ID 63 — University of Education, BS Business Analytics, candidate campus 16 Township | https://ue.edu.pk/programs.php?id=200 ; https://www.ue.edu.pk/campus.php?id=40 | The programme page supports programme existence; the Township campus page does not list it. Keep blocked pending campus-specific evidence. |
| Program IDs 3, 35, 36, 37 — Punjab University computing rows | https://pu.edu.pk/home/admission_notice/698 | The official 2026–27 notice distinguishes FCIT New Campus (including BSCS, BSCS-AI and BS Data Science) from FCIT Old Campus (including BSCS and specializations such as AI, IT and Software Engineering). Register labels “BS Software Engineering” and “BS Information Technology” may be specializations rather than standalone canonical programmes. Reconcile canonical programme IDs and campus IDs before any proposed relationship. |
| Program IDs 30 and 31 — NTU, BS Polymer Engineering / Bachelor of Fashion Design | https://www.ntu.edu.pk/ug-programs.php | Official undergraduate listing supports the programme names. It does not by itself establish that candidate campus ID 8 is the correct Faisalabad campus relation; keep review-only until canonical campus mapping is confirmed. |
| Program ID 74 — NCA, “Bachelor of Design” | https://www.nca.edu.pk/prospectus/bfa-prospectus-27-7-25.pdf | Current prospectus uses specific design-degree names (including Visual Communication Design, Textile Design, Fashion Design, Product Design and Ceramic Design). The broad register label “Bachelor of Design” is not enough to identify a canonical programme safely. Keep blocked until the intended degree and Lahore campus are explicitly mapped. |
| Program ID 55 — Virtual University, BS Biotechnology | https://www.vu.edu.pk/AcademicDepartment/ProgramDetails?StudyProgramID=116 ; https://www.vu.edu.pk/NewsDetails?NewsId=9088&type= | Official programme information describes lab-city constraints. This is not equivalent to a conventional “Lahore Main Campus” offering. Keep blocked until the schema/candidate relation accurately models online delivery and any lab-city relationship. |
| Program ID 71 — FUUAST, BS Accounting & Finance | No verified official programme/campus URL currently recorded in the register. | No evidence-backed campus relationship can be asserted. Keep blocked; add a current official programme source and a campus-specific source before review. |

## Required next actions

1. Obtain a current official programme and campus source for FUUAST BS Accounting & Finance.
2. Resolve NCA's exact canonical programme identity; do not substitute a broad label for a named degree.
3. Reconcile Punjab University programme taxonomy against canonical programme records and campus IDs.
4. Verify campus IDs for all remaining rows, not just the existence of a programme at an institution.
5. Finish the 36-row evidence review and produce a row-by-row repair proposal with source URLs and duplicate checks.
6. Run fresh read-only preflight and reconciliation after the evidence register is updated.

## Status

This batch improves evidence classification but does not resolve the full live gap. The 36-row baseline remains the last confirmed audit result until a new live run completes. Keep Step 3 open. No production data mutation or migration is authorized.
