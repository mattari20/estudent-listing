# DEV-022 — Public Directory Integration

Status: Implemented
Branch: dev/022-024-integration-hardening

## Scope
Connect the existing canonical Institution, Campus, Geography, Program and Program Offering domains to the public `/institutes/` experience.

## Implemented
- Public directory pagination remains at `/institutes/`.
- Public institution URLs remain `/institutes/{institution-slug}`.
- Only active institutions are publicly listed and resolved.
- Institution profiles load active campuses with geography.
- Institution profiles load active programs and their active offerings.
- Search also checks governed InstitutionName aliases.
- Search exposes campus/program counts without becoming a quality ranking.

## Boundary
This is an integration layer. It does not introduce ranking formulas, recommendation algorithms, AI generation, or new canonical entities.

## Next hardening
Add richer filters, admissions/eligibility presentation, provenance/verification display, and production data ingestion only after the corresponding governance is connected.
