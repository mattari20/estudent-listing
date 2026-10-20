# Data Enrichment Phase — LUMS and UVAS Source Review

**Prepared:** 2026-10-20  
**Status:** Research complete; staging/review only. Not imported, approved, promoted, or published.  
**Scope:** Source-backed candidates for the currently empty LUMS and UVAS institution profiles in production.

## 1. Why this review exists

The read-only Hostinger diagnostic on 2026-10-20 confirmed that both canonical institution records exist and are active, but neither has an active campus or program associated with it:

- LUMS: institution ID 8, slug `lahore-university-of-management-sciences`, 0 active campuses, 0 active programs.
- UVAS: institution ID 5, slug `university-of-veterinary-and-animal-sciences-lahore`, 0 active campuses, 0 active programs.

The diagnostic also found one active campus with a missing or inactive geography association. The current diagnostic output does not establish which geography value should be assigned, so that repair is intentionally not guessed in this document.

## 2. Official source review

The URLs below were opened on 2026-10-20. These are primary institutional sources, but the evidence has not yet been captured in the application’s source/evidence/snapshot workflow.

### LUMS

1. **Official contact page** — https://www.lums.edu.pk/contact-us  
   Supports the institution’s Lahore location/address: DHA, Lahore Cantt. 54792, Lahore, Pakistan. It also links to the campus map.
2. **Official undergraduate programme page** — https://lums.edu.pk/undergraduate  
   Lists 21 undergraduate programmes and gives the admissions address in DHA, Lahore Cantt.
3. **Official programme finder** — https://new-admissions.lums.edu.pk/programme-finder  
   Lists programme names and their schools, including BS Computer Science, BS Biology, BSc (Honours) Accounting and Finance, BSc (Honours) Management Science, and BA-LL.B (Honours).
4. **Official campus/venue page hosted on a LUMS subdomain** — https://aasinasia2026.lums.edu.pk/venue-campus.php  
   Identifies the LUMS conference venue as the Lahore campus and gives the address opposite Sector U, DHA, Lahore Cantt. This is supplementary location evidence, not a substitute for capturing the main institutional source.

**Staging conclusion:** one candidate campus record, “LUMS Main Campus”, may be prepared for review. Its address should preserve the institution’s official wording. Do not invent coordinates, postal subdivisions, campus type codes, or geography IDs.

### UVAS

1. **Official UVAS homepage** — https://uvas.edu.pk/  
   Explicitly lists university campuses: Ravi, Jhang, Narowal, and Layyah. The site’s admissions/contact material also identifies the Lahore main campus.
2. **Official undergraduate admissions page** — https://www.uvas.edu.pk/Admissions/undergraduate/  
   Lists programmes by Lahore, Ravi, Jhang, and Narowal campuses.
3. **Official admissions guidelines** — https://www.uvas.edu.pk/Admissions/undergraduate/Admission_guidelines/index.htm  
   Lists Lahore-campus programmes and identifies DVM as a Lahore/Ravi joint offering, plus other named programmes.
4. **Official 2026 admissions information** — https://www.uvas.edu.pk/Admissions/undergraduate/  
   Shows current admissions navigation for 2026 and a campus-by-program table.

**Staging conclusion:** the official site supports reviewing these campus candidates under UVAS: Lahore Main Campus, Ravi Campus (Pattoki), Jhang Campus, Narowal Campus, and Layyah Campus. The official sources consulted do not provide an equivalent programme-by-campus table for Layyah in the material reviewed, so do not attach programmes to Layyah without further source evidence. The exact canonical display names and whether the main Lahore campus should be represented as a campus child must be checked against the existing model and current source wording before import.

## 3. Programme candidates — evidence scope

These are examples of programme names confirmed on official pages, not a complete import list:

- **LUMS:** BS Computer Science; BS Biology; BSc (Honours) Accounting and Finance; BSc (Honours) Management Science; BA-LL.B (Honours).
- **UVAS:** Doctor of Veterinary Medicine (DVM); Doctor of Pharmacy (Pharm-D); BS Applied Microbiology; BS Medical Laboratory Technology; BS Biotechnology; BS Biochemistry; BS Food Science and Technology; BS Human Nutrition and Dietetics; BS Environmental Sciences; BS Computer Science; Bachelor of Business Administration (BBA).

The final import should use the current official programme title, programme level/award/discipline fields only when the schema’s semantics are understood, and campus association only when the official page explicitly supports it. This note intentionally does not create an import CSV with guessed foreign keys or unsupported campus mappings.

## 4. Mandatory gates before production import

The repository’s existing governance requires a source-backed controlled workflow. The web research above is not itself a completed in-application evidence record.

1. Create or verify an active source record for each official institutional source.
2. Capture the exact URL and relevant excerpt through the authenticated evidence-capture route; preserve a source snapshot/content hash when supported.
3. Configure/verify active source monitoring and a completed retrieval run for the source.
4. Run source-verification readiness for the corresponding import batch.
5. Prepare separate campus and programme CSV batches against the existing schema; run header, row, duplicate, parent-institution, and geography preflight.
6. Confirm canonical parent IDs and actual geography IDs from the live database. Do not use assumed IDs.
7. Review the generated verification, provenance, deployment, and promotion readiness screens.
8. Promote only rows that have the required source evidence, approved decisions, and clean preflight. Re-run the read-only production audit afterward.

## 5. Explicit exclusions

- No production database writes were performed.
- No source/evidence/import batch was created by this document.
- No coordinates, fees, admission deadlines, scholarships, accreditation status, or campus-specific programmes were inferred.
- The existing rejected campus import row remains rejected pending its own review.
- The active campus with the missing/inactive geography association remains unresolved pending record-level identification and authoritative geography mapping.

## 6. Acceptance criteria for this phase

- [ ] Source URLs and exact excerpts captured in the application’s governed evidence workflow.
- [ ] LUMS and UVAS campus candidates reviewed against the canonical schema and duplicate checks.
- [ ] Programme candidates mapped only where official source evidence identifies the offering.
- [ ] Geography IDs resolved from the live database and validated against the geography hierarchy.
- [ ] Import readiness and promotion readiness both pass before any production promotion.
- [ ] Post-promotion read-only audit confirms counts, parent associations, geography integrity, and audit events.
