# DEV-119 — Discovery run outcome ledger

## Purpose

Record the result of source-change discovery processing so scheduled monitoring does not silently skip unchanged, empty, disabled, unmatched, or failed runs.

## Outcomes

- `no_change`: the source observation did not report changed content.
- `empty_snapshot`: the observation says changed but has no usable snapshot content.
- `discovery_disabled`: a matching profile disables automatic discovery.
- `candidates_created`: discovery created one or more non-canonical candidate rows.
- `empty_candidates`: discovery completed but created no candidate rows.
- `failed`: discovery threw; the exception class is recorded and the exception is rethrown so the scheduler/monitor still sees failure.
- `no_matching_profile`: no configured profile matched the observed source URL.

The ledger stores source, observation, snapshot, profile, candidate count, package status, outcome timestamp, and safe diagnostic details. Raw exception messages are deliberately omitted.

## Safety and operations

- The ledger is additive and does not mutate canonical institution, campus, or program records.
- Scheduled discovery continues to use the profile's existing AI setting; this change does not enable paid AI.
- Apply the migration through the reviewed deployment process before relying on the ledger in Hostinger.
- Retention/cleanup and operator-facing run history remain follow-up acceptance items.
