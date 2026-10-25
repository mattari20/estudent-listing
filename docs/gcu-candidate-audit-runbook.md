# GCU Discovery Candidate Audit Runbook

**Scope:** read-only, row-level review of the latest GCU Lahore discovery batch.  
**Baseline to re-confirm from the protected runtime:** 112 candidate rows. This is a historical baseline, not a claim about the current batch.  
**Safety:** this workpack must not approve, reject, promote, publish, delete, or rewrite canonical records.

## Required input package

Retrieve the current protected review/export package from the runtime. Preserve the original package and record:

- retrieval timestamp and operator;
- source batch ID and source snapshot IDs;
- export checksum (SHA-256);
- extractor version used for the batch;
- total candidate rows and unique import-row IDs;
- validation report and current duplicate-match results.

The export must include the fields represented in `docs/gcu-candidate-disposition-template.csv`. If candidate payloads, source snapshots, evidence excerpts, or row IDs are absent, mark the affected rows `needs-official-evidence` or record a specific blocker; do not infer missing facts.

## Review each row

1. Confirm the candidate belongs to the GCU official programme source and identify the exact source snapshot/version.
2. Open the official source URL and locate the candidate text. Record the exact excerpt and the relevant heading/section or page title.
3. Check the surrounding HTML/content context. A degree-looking link in global navigation, header, footer, sidebar, or unrelated promotional content is not programme evidence.
4. Compare the candidate to the current canonical catalogue using normalized slug and exact/near-match review. A slug match is a signal to investigate, not proof of identity.
5. Check whether another row in the same batch or an earlier batch represents the same candidate. Preserve all historical rows and record the exact duplicate target row ID where verified.
6. Record validation errors and current row status from the export, not from assumptions.
7. Select exactly one disposition from the allowed list below and explain the evidence in reviewer rationale.

## Allowed dispositions

- `keep-for-review` — credible official evidence exists, but governed approval is still required.
- `duplicate-of-row` — the same entity is evidenced elsewhere; populate `duplicate_target_import_row_id`.
- `invalid-navigation-or-footer` — candidate is sourced only from navigation, header/footer, or unrelated site chrome.
- `unsupported-by-source` — reviewed official source does not support the claimed programme.
- `needs-official-evidence` — source or snapshot is unavailable/insufficient, or identity remains ambiguous.
- `already-dispositioned` — current authoritative row state proves the candidate was already reviewed; cite that state.

Do not introduce a new disposition without updating this runbook and obtaining review of the change.

## Output quality gates

Before publishing the audit package for human review, confirm:

- exactly one output row per input candidate;
- input count matches output count (expected baseline 112 only if re-confirmed from the current export);
- no blank or duplicate `import_row_id` values;
- each `duplicate-of-row` target exists in the supplied row inventory and is not the row itself;
- every disposition has a non-empty reviewer rationale;
- every source-supported disposition includes an official URL, snapshot/evidence reference, and excerpt;
- every `invalid-navigation-or-footer` row records where the link was found;
- ambiguous or unavailable evidence remains blocked rather than guessed;
- the original input, output, and a summary of counts are retained together with timestamps/checksums.

Report separate counts for each disposition, duplicate targets, missing evidence, validation errors, and rows that could not be reviewed. Do not describe 0 validation errors or 0 canonical slug matches as proof that candidate names are correct.

## Human review and production safety

The output is a review recommendation only. Do not call bulk approval/promotion endpoints, update canonical institution/programme tables, delete historical batches, or change source status during this audit. Any future data repair requires a separate row-level proposal with evidence, duplicate checks, rollback considerations, and explicit authorization through the governed production workflow.

## Current blocker

The connected repository tools do not expose the protected live 112-row candidate export or its source snapshots. Until that package is retrieved, the record-level audit remains **BLOCKED / NOT VERIFIED**. This runbook makes the review reproducible but does not substitute for the missing data.
