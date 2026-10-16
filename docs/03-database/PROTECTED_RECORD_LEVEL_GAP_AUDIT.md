# Record-Level Gap Audit

## Purpose

`institutes:data-gap-record-audit --json` is a read-only companion to the aggregate data-gap report. It lists canonical institution IDs, names, slugs, type/status and campus/program counts; the existing program records; import batch metadata; and import row status/match-state/canonical relationship references.

## Data minimization

The report deliberately excludes import payloads, normalized payloads, free-text notes, and full validation error text. It does include institution names and internal database IDs, so it must be treated as private operational data and must not be committed to the public application repository.

## Safety properties

- Uses database reads only.
- Reports `mutation_performed: false`.
- Does not activate, deactivate, create, update, import, promote or publish records.
- Requires human review; the report does not establish external source truth or prove a record is invalid.
- The command must be tested in CI before being used outside a test environment.

## Operational status

The current `main` branch's `AutomatedDiagnosticsController` includes this command in the authenticated diagnostics response, and the private-report workflow validates and stores the record-level section. This confirms repository integration only. Production deployment and a fresh record-level report must still be verified independently before treating production output as available.

Do not attempt to access the command through a public URL or expose its output publicly. Preserve bearer-token authentication, fail-closed behavior, no-store headers, sanitized errors, and private-only report storage. If rate limiting is not independently verified in the deployed route/middleware, record that as an unresolved hardening item rather than assuming it exists.


## Review outcomes

Use these outcomes in the private audit matrix: `SUPPORTED` (credible evidence agrees with the record), `INSUFFICIENT_EVIDENCE` (not enough reliable evidence), `CONFLICTING_EVIDENCE` (credible sources disagree), `DUPLICATE_CANDIDATE` (possible duplicate requiring comparison), and `INVALID_STRUCTURE` (record fails a documented structural rule). These are review classifications, not automatic database actions. Conflicting or insufficient evidence must remain unresolved; duplicate candidates require comparison of identity, campus, program and source provenance; structural issues require a reproducible rule and row-specific explanation. None of these classifications authorizes an update or deletion.