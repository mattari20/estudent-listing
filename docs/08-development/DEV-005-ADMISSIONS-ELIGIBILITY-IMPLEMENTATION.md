# DEV-005 — Admissions & Eligibility Implementation

Status: Implemented in branch; pending merge.

This slice implements the admission-intake and eligibility persistence boundary defined by MOD-010 and DB-001.

## Core distinction

Eligibility describes whether an applicant meets published requirements. It is not a merit score, cutoff, selection outcome or enrollment result.

## Included

- Program-offering-specific admission intake.
- Application and selection dates.
- Intake lifecycle status.
- Seat capacity where published.
- Official application destination.
- Applicant-category context.
- Qualification and marks requirements.
- Subject requirements.
- Entry-test requirement.
- Domicile, age and experience fields.
- Unknown/nullable values remain representable.

## Excluded

Merit/selection outcomes, fees, scholarships, accreditation, provenance workflows, search, rankings, recommendations and AI remain separate implementation slices.
