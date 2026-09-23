# DEV-003 — Program & Program Offering Implementation

Status: Implemented in branch; pending merge.

This slice implements the canonical Program and Program Offering persistence boundary defined by MOD-009 and DB-001.

## Boundary

Program identifies the academic offering family. Program Offering represents a contextual instance such as campus, delivery mode, intake, availability and effective dates.

## Explicitly excluded

Admissions, eligibility, merit, fees, scholarships, accreditation, provenance, search, ranking, recommendations and AI remain separate slices.
