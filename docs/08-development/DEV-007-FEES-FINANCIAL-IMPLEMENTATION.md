# DEV-007 — Fees & Financial Information Implementation

## Status
Implemented on the development branch for review.

## Scope
This increment adds the canonical storage foundation for optional financial information. Fees are an enrichment layer and never a prerequisite for an institution, campus, program, offering, search result, profile, or SEO page.

## Design Rules
- Missing financial information is a data-availability state, not system failure.
- Records may be scoped to institution, program, program offering, campus, admission intake, or combinations.
- A fee may be fixed or represented as a range; amounts remain nullable.
- Currency, unit, period, payment stage, applicant category, delivery mode, and effective dates preserve context.
- Data states support known, partially known, unknown, not published, not applicable, awaiting verification, conflicting, historical only, stale, and superseded.
- The governed Source → Evidence → Assertion → Verification chain remains canonical provenance.
- Derived totals must retain assumptions and must not overwrite source facts.
- AI may assist extraction and normalization but must not invent missing fees.
- Scholarships and financial aid remain separate.
- Search remains inclusive when fee information is absent.

## Initial Fee Types
The fee_type field is intentionally extensible. Expected values include application, admission, tuition, semester, annual, credit_hour, laboratory, examination, registration, technology, library, hostel, transport, deposit, and other institution-defined components.

## Integrity Boundaries
- Missing amount is never interpreted as zero.
- Missing fee information is not evidence that an institution is free.
- Both amount fields may be null when information is unavailable or cannot be represented reliably.
- When both amounts exist, minimum must not exceed maximum.
- Effective dates describe validity context and do not independently prove verification.

## Deferred
Public fee entry, scraping, automatic verification, affordability scoring, fee-based ranking, scholarship logic, financial recommendations, payment processing, dedicated financial API, and dedicated search indexing are deferred.
