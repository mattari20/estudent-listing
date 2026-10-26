# GCU candidate classification report contract

## Baseline
Issue #585 records 112 candidates in the latest GCU discovery batch. The count is a historical baseline, not a claim about a fresh batch.

## Required per-candidate fields
Candidate ID, batch ID, original URL, final URL, HTTP/fetch status, extractor version, official-domain assessment, page-type classification, programme-title evidence, campus evidence, canonical institution identity, duplicate candidate IDs, disposition, rationale, reviewer, and review timestamp.

## Allowed outcomes
- `verified-programme-candidate`
- `unrelated-navigation-or-admission-page`
- `duplicate-of-candidate-id:<id>` (only with proven stable identity)
- `wrong-institution-or-campus`
- `insufficient-official-evidence`
- `fetch-or-extraction-failure`
- `needs-manual-review`

## Acceptance
Every candidate in the exact batch is represented once; every disposition has a rationale; original provenance remains intact; the report distinguishes the historical 112-row batch from any refreshed batch; ambiguous rows stay blocked. Candidate classification alone must not approve or promote records.

## Validation
Check report row count against the exact source batch, uniqueness of candidate IDs, required evidence fields, and zero mutation indicators. No current classification result is asserted by this specification.