# Institution Import and Smart-Table Approval Test Pack

**Prepared:** 2026-10-21  
**Repository:** `mattari20/estudent-listing`  
**Status:** repository package prepared; deployment and live UI test still pending  
**Production writes:** none performed by this documentation/data-package change

## Goal

Provide one versioned place for source-led institution candidates and a safe, repeatable test procedure for import, review, bulk approval/rejection, and promotion boundaries.

## Candidate data

The review pack contains ten institution candidates:
- COMSATS University Islamabad
- Federal Urdu University of Arts Sciences and Technology
- National University of Modern Languages
- National College of Arts
- Bahria University
- Air University
- Virtual University of Pakistan
- Karakoram International University
- National Textile University
- National University of Sciences and Technology

The source manifest records official institutional websites and HEC source pages. Several HEC pages use legacy names or include campus/study-centre information whose meaning must be preserved. The candidate list must not be treated as an authoritative complete list of Pakistani HEIs.

LUMS and UVAS are existing canonical targets in the earlier source review, so their campus/programme candidates remain in the separate enrichment staging directory; they are not duplicated in the institution import CSV.

## Test matrix

| Test | Dataset / action | Expected result | Safety boundary |
| --- | --- | --- | --- |
| Institution CSV header validation | Upload `institution-candidates-v1.0.csv` after checking the live schema | Header accepted if deployed schema is v1.0 | Header acceptance is not identity approval |
| Institution row validation | Review all ten rows | Names/slugs normalize; duplicate/invalid rows remain visible | Do not assume a candidate is new |
| Identity resolution | Open review details for candidates | Existing matches/conflicts are explicit and auditable | No bulk approval for institution identity |
| Enrichment header validation | Use the current live campus/program template and the existing LUMS/UVAS staging files | Supported columns accepted; invalid values flagged | Check parent institution and current template |
| Bulk approve | Select eligible campus/program candidates with no open conflict and valid parent | Approved count increases; rows remain non-canonical until promotion | Approval must not publish records |
| Bulk reject | Select review-only candidates intended for rejection test | Rejected count increases and reason/audit event is recorded | Rejected rows leave the active queue; re-import for repeat testing |
| Bulk promotion | Only select approved candidates after full source/evidence/geography readiness | Only eligible rows promote; failures/skips are reported | Do not promote the currently geography-incomplete campus candidates |
| Post-test reconciliation | Review batch summary and audit history | Imported/valid/invalid/approved/rejected/promoted counts reconcile | Never equate imported with published |

## Required test evidence

For each run, record:
- deployed commit or release evidence;
- import batch ID and source record;
- CSV filename and schema version;
- total, valid, invalid, duplicate, approved, rejected, promoted, skipped, and failed counts;
- identity matches and conflicts;
- evidence/source readiness result;
- row-level audit history for approve/reject/promote;
- read-only post-test canonical counts if any promotion was explicitly authorized.

## Known data caveats

1. The previous HEC candidate file used several older or misspelled display names. The new review pack uses normalized display labels but preserves caveats in the source manifest.
2. A HEC source page may confirm institution identity or list campus locations; it does not automatically verify every programme, campus status, contact detail, fee, admission deadline, or accreditation claim.
3. Existing LUMS and UVAS staging CSVs leave geography IDs blank intentionally. Do not promote those campus rows until a valid active geography ID has been verified.
4. Existing production counts and parent IDs are time-sensitive; confirm them through the current read-only diagnostic before relying on them.
5. No upload, approval, promotion, publication, or production database write is performed by adding this pack to GitHub.

## Next step

After this package merges and the deployed revision is verified, run the institution import and enrichment approval/rejection tests through the authenticated Operations UI. Record actual outcomes before declaring the feature complete.
