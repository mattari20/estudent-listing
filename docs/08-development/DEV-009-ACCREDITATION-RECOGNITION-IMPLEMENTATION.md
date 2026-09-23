# DEV-009 — Accreditation, Recognition & Regulatory Status Implementation

## Status
Implemented on the development branch for review.

## Scope
Adds the canonical storage foundation for accreditation, recognition, affiliation, authorization, licensing, and regulatory status information.

## Governance Rules
- Accreditation, recognition, affiliation, authorization, licensing, and regulatory status remain distinguishable through recognition_type.
- Directory verification is not itself a government or professional recognition decision.
- Scope is explicit and may apply to an institution, program, program offering, or campus.
- Authority identity is retained separately from the status and claim.
- Reference identifiers support traceability to external records.
- Effective dates preserve historical status without overwriting prior states.
- Unknown recognition is not interpreted as non-recognition.
- Absence of evidence is not evidence of absence.
- Conflicting claims remain representable through the existing Source → Evidence → Assertion → Verification chain.
- AI may assist extraction, matching, monitoring, and review prioritization but must not independently declare accreditation or regulatory approval.
- Search, SEO, ranking, and recommendation layers consume governed records rather than inventing recognition claims.

## Deferred
Public accreditation submission, automated regulatory crawling, automatic verification, professional-body integrations, ranking formulas, and dedicated accreditation APIs are deferred.
