# DEV-024 — Directory Integration Testing

Status: Implemented
Branch: dev/022-024-integration-hardening

## Covered
- Institution profile resolves through its stable slug.
- Campus is connected to its institution.
- Campus geography is rendered.
- Program is connected to its institution.
- Program offering is connected to program and campus.
- Alias search resolves through InstitutionName.
- Inactive institutions are not publicly exposed.

## Verification
Tests use Laravel database refresh isolation.

## Follow-up
Expand coverage for API/public parity, pagination, optional data, admissions, provenance/verification display, canonical/redirect behavior, and negative/edge cases.
