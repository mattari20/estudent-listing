# Source-backed institution candidate CSV

**Dataset date:** 2026-10-10  
**Status:** Staging candidates only — not imported, approved, promoted, or published.  
**Source:** Higher Education Commission (HEC), *HEC Recognised Campuses*  
https://www.hec.gov.pk/english/universities/Pages/DAIs/HEC-recognized-Campuses.aspx

## What this file contains

Ten institution name candidates copied from the institution names shown in the HEC recognised-campus listing. The file intentionally contains no invented contact details, websites, fees, programmes, scholarship claims, or coordinates.

## Important caveats

- The source is a campus listing, not a complete nationwide institution master list.
- Some names may be historical or differ from current official branding. Preserve the source text at intake and resolve the canonical/current name against the institution's official site and current HEC records before promotion.
- Blank fields are intentionally left blank when not confirmed from this source.
- These are candidates for the existing controlled import/review workflow. They must not be treated as canonical or public records without duplicate checking, source evidence capture, identity resolution, and operator approval.
- This file has not been checked against the live Hostinger database, because no live database connection/export was available.
- Do not infer that every campus or programme belonging to a named institution is recognised; HEC publishes campus-specific restrictions and time-bound permissions separately.

## Import schema

The columns follow the repository's institution CSV schema profile v1.0:
`name, official_name, short_name, slug, institution_type, status`

Only `name` is required by the current schema. The source's exact name is retained in `name`; unverified fields remain blank.
