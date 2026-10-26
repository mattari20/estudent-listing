# Clean data rebuild — official-source pilot

Checked: 2026-10-26  
Branch: `data/clean-rebuild-pilot-2026-10-26`  
Status: **source candidate manifest only — not approved for direct database import**

## Why this exists

The previous test dataset should not be reused blindly. This manifest starts a new evidence-backed collection path, with official university pages and the HEC recognized-campus register as the authoritative cross-check.

## Important interpretation

- `candidate-needs-record-level-review` means the row is a research lead, not a verified live record.
- The official HEC campus register must be checked against the exact institution/campus name and any validity/programme restrictions before the campus is marked verified.
- A general programme catalogue does not prove a programme is available at every campus.
- Delivery modes such as Open Distance Learning and face-to-face must be represented separately where the schema supports them.
- No addresses, phone numbers, geographic coordinates, fees, admission status, rankings, or programme details are inferred here.
- Do not load this CSV directly into canonical tables. First map each source row to the repository's import schema and produce row-level evidence excerpts/fingerprints.

## Initial official sources

1. HEC recognized campuses: https://www.hec.gov.pk/english/universities/Pages/DAIs/HEC-recognized-Campuses.aspx
2. HEC accreditation section: https://www.hec.gov.pk/english/universities/Pages/Accreditation.aspx
3. HEC PQR advisory (19 March 2026) describes PQR as the official source for programme recognition and active/suspended/closed status: https://www.nu.edu.pk/Public/EventsImages/HEC_DOC2026.pdf

## Next gates

1. Read-only inventory of all deployed tables, row counts, FK dependencies, and schema fingerprint.
2. Classify canonical records versus import staging, provenance/history, auth/admin, and framework/system data.
3. Create a dry-run reset manifest; keep schema unchanged and preserve required admin access and migration metadata.
4. Expand source collection to 10 complete institution records, including only individually evidenced campuses and programme-campus offerings.
5. Validate import mapping against existing Laravel import pipeline, run local/CI validation, then use a guarded Hostinger workflow only after dry-run review.
6. Import the 10-record pilot, verify counts and relationships, then expand in controlled batches.

No production data has been cleared or imported by this change.
