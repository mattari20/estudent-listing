# Pakistan Institute Data Source Audit — 2026-10-10

**Status:** Research/audit only; no institute records imported or published.  
**Repository:** `mattari20/estudent-listing`  
**Purpose:** Identify credible source material for a governed, deduplicated master institute dataset.

## Findings

### 1. Primary authority: Higher Education Commission (HEC)

- Recognised universities and degree-awarding institutions are linked from HEC's [Students Services page](https://www.hec.gov.pk/english/services/students/pages/default.aspx).
- HEC's [Recognised Campuses page](https://www.hec.gov.pk/english/universities/Pages/DAIs/HEC-recognized-Campuses.aspx) lists campus recognition and includes important scope/permission notes.
- HEC's [Accreditation Section](https://www.hec.gov.pk/english/universities/Pages/Accreditation.aspx) links recognised institutions, recognised campuses, and unrecognised-institution information.

**Use:** Treat HEC as the first verification authority for university/DAI recognition and campus status. Capture source URL, retrieval date, evidence, and any time-bound restrictions. A university's recognition must not automatically imply that every campus or programme is approved.

### 2. GitHub dataset lead reviewed

- Repository: [cs50chandrasayush/pakistan_universities_data](https://github.com/cs50chandrasayush/pakistan_universities_data)
- File: [universities.csv](https://github.com/cs50chandrasayush/pakistan_universities_data/blob/main/universities.csv)
- Reviewed file revision (blob SHA): `db756d0580cc152bf4cb2369ddfc73843ac26670`
- Reported structure: 266 data rows, 16 columns, 266 distinct exact-name values, and no malformed row widths in the CSV parse.
- Includes names, category, campus text, contact information, website, coordinates, sector, chartering authority, city, province, claimed recognition, and distance-education flag.

**Quality warning:** This is a third-party dataset, not current proof of HEC recognition. At least one sample row's map URL points to a different university than the row name, demonstrating that fields require validation. Dates and recognition/campus restrictions can become stale. A repository-root `LICENSE` file was not found during this check, so redistribution/reuse permission is **unconfirmed**.

**Decision:** Do not import this CSV wholesale or publish its recognition, contact, location, or campus fields as verified. Use it only as a discovery lead unless licensing is confirmed and each material field is reconciled with authoritative evidence.

### 3. Existing importer and governance constraints

- The current institution CSV profile supports only: `name, official_name, short_name, slug, institution_type, status`.
- The project has an operator-controlled import/review/promotion flow. Candidate rows are not canonical/public records merely because an import batch exists.
- `docs/08-development/DEV-301-IMPORT-VERIFICATION-GATE.md` explicitly requires real, source-backed data and operator review. The DEV-305 extension requires active source evidence attached to an import row before verification readiness.
- The existing discovery-agent route reviewed in `app/Http/Controllers/InstituteOperationsController.php` is profile-driven and currently creates **programme candidates** for a configured institution; it is not a general national university/campus collector.

## Safe collection plan

1. Extract the HEC recognised university/DAI list as the institution baseline.
2. Extract HEC recognised campus records separately; retain time-limited permissions, stopped intake, closure, and other restrictions as explicit evidence rather than flattening them into a simple active flag.
3. Use institution websites and other official government/regulator sources to enrich official names, websites, cities, and contact details.
4. Keep GitHub datasets as secondary discovery inputs only; record repository URL, file URL, revision/hash, retrieval date, and licence status.
5. Normalize names and compare against existing canonical institutions before staging candidates. Do not delete or overwrite existing records during deduplication.
6. Stage source-backed candidates through the current import flow and resolve identity matches/conflicts before operator approval and promotion.
7. Keep programmes, campuses, offerings, admissions, fees, scholarships, and accreditation as distinct entity types with parent relationships and their own evidence.

## Automation and upload status

This audit did not access the live Hostinger database and did not upload, approve, promote, or publish any records. The current repository inspection did not establish a configured GitHub Actions pipeline that safely transfers reviewed master data to the live database. GitHub deployment by itself does not provide database-write access.

Before automatic live upload can be enabled, the project needs a documented and authorized transfer mechanism, least-privilege credentials stored as repository/environment secrets, duplicate-safe import semantics, execution logs, and a tested rollback/recovery procedure. No production database credential or live upload endpoint was available during this audit.

## Acceptance criteria for the eventual master import

- Source and reuse/licence status documented.
- Existing records compared before import; no blind overwrite.
- Institution/campus/programme relationships preserved.
- Source evidence and retrieval date retained.
- Invalid, duplicate, and unresolved rows counted separately.
- Import stays staged until explicit operator review.
- Final counts and public visibility are verified against the live system after deployment.
