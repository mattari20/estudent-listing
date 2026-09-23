# DEV-008 — Scholarships & Financial Aid Implementation

## Status
Implemented on the development branch for review.

## Scope
Adds scholarships as a separate optional financial-aid domain. Scholarship information can enrich directory records but must never be required for an institution, program, offering, search result, profile, or SEO page.

## Governance Rules
- Missing scholarship data does not mean that no scholarship exists.
- Scholarship records are distinct from fee records.
- Scholarship type, eligibility, benefit, duration, renewal, application period, and effective validity are separate concepts.
- Scope may be institution-wide or contextual to a program, offering, campus, or admission intake.
- Merit-based, need-based, category-based, institutional, external, grant, waiver, sponsorship, and other aid concepts remain distinguishable through extensible classification fields.
- Amounts may be fixed, ranged, percentage-based, non-numeric, or unknown; the current model keeps numeric amount fields nullable and uses benefit_type for the semantic form.
- Data availability states include known, partially known, unknown, not published, not applicable, awaiting verification, conflicting, historical only, stale, and superseded.
- Scholarship information remains separate from affordability calculations and recommendations.
- AI may assist discovery, extraction, classification, and change detection but must not invent eligibility, benefits, deadlines, or availability.
- Canonical provenance continues through the governed Source → Evidence → Assertion → Verification model.
- Search must remain inclusive when scholarship information is missing.

## Deferred
Public scholarship submission, automated scraping, automatic verification, scholarship ranking, affordability scoring, recommendation algorithms, and dedicated scholarship API contracts are deferred.
