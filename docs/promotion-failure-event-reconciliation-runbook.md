# Promotion-failure event reconciliation runbook

## Purpose

Reconcile historical promotion-failure **events** to unique import rows and current record dispositions without changing canonical data. This is an audit workpack, not a promotion or repair command.

## Known baseline (2026-10-24)

- 76 recorded failure events.
- 31 events were described as duplicate-slug / identity-collision failures.
- 45 events were described as validation / missing-field failures.
- The event count is not a count of unique rows. Do not report 76 unresolved records unless a row-level join proves that.
- The protected live event export and current import-row records were not included in the repository evidence reviewed for this workpack. No row-level reconciliation is claimed by this document.

## Procedure

1. Obtain a fresh, read-only export of promotion events and the linked import rows from the deployed revision. Record export timestamp, application revision, filters, and query/report identifier. Do not include credentials or unrelated personal data.
2. Preserve one row per original event in the event ledger. Never overwrite or delete historical events.
3. Link each event to its stable import-row ID and batch ID. If either is absent, mark `missing_row_link`; do not infer identity from a name alone.
4. Group events by stable import-row ID. Keep the event count and unique-row count as separate metrics.
5. Classify each event using the source reason and current row/canonical evidence:
   - `duplicate_identity_review`
   - `validation_missing_fields`
   - `missing_row_link`
   - `already_resolved_verified`
   - `unresolved_other`
6. For identity collisions, record the canonical target ID and evidence for same-entity vs campus/parent ambiguity. Similar names or matching slugs alone are not sufficient to merge identities.
7. For validation failures, list each missing/invalid field and the official evidence required to resolve it. Do not fabricate values.
8. Assign one current row disposition: `hold_for_identity_review`, `hold_for_source_evidence`, `eligible_for_manual_recheck`, `verified_resolved_no_write`, or `unresolved`.
9. Have a reviewer verify all row links, category totals, duplicate targets, and dispositions before publishing the summary.
10. Keep the output read-only. Any later canonical change requires a separate row-level repair plan, duplicate checks, and explicit owner authorization.

## Required reconciliation checks

- Event count in the exported ledger equals the number of source events in the selected export.
- Every event has a stable event ID or is explicitly flagged as missing.
- Unique import-row count is calculated from stable IDs, not names.
- Per-category event counts sum to the event total.
- A row with several events appears once in the unique-row summary but retains all event references.
- Current disposition counts sum to the unique-row total.
- Rows with missing evidence or ambiguous parent/campus identity remain blocked.
- The report explicitly states that no canonical writes, promotions, deletions, or migrations were performed.

## Artifacts

- Use `promotion-failure-event-ledger-template.csv` for one row per event.
- Publish a separate unique-row summary keyed by `import_row_id`; do not replace the event ledger with the summary.
- Include export provenance and report-generation timestamp in the report metadata.

## Completion gate

The reconciliation is complete only when the protected live export has been processed, all event links are either resolved or explicitly flagged, event totals reconcile, every unique row has a documented disposition, and an independent read-only review passes. A successful CI run alone does not satisfy this gate.
