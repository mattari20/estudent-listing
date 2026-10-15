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

This command is proposed in PR review. It is not yet confirmed deployed or exposed through the protected production diagnostics endpoint. Do not attempt to access it through a public URL or claim that a production record-level report has been retrieved until a separately reviewed integration is merged, deployed and verified.

The protected endpoint integration is a separate change because it increases the sensitivity of the data returned by that endpoint. Any integration must preserve bearer-token authentication, fail-closed behavior, rate limiting, no-store headers, sanitized errors, and private-only report storage.
