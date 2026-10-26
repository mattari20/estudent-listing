# Failure-event reconciliation implementation contract

## Purpose
Define the exact contract for the read-only reconciliation implementation before modifying the production-facing import lifecycle.

## Input contract
- Read historical promotion-failure events without updating them.
- Require a stable import-row or candidate-row identifier to group events.
- Preserve the raw event ID, category, timestamp, batch ID, and relevant validation/identity metadata.
- If the stable row ID is absent, emit an explicit unresolved-event entry; never infer identity from a programme title or slug alone.

## Output contract
- One summary per stable row ID, with the list of associated event IDs and categories.
- A separate unresolved-event list for events without reliable row identity.
- Distinguish historical failure events from candidates that remain unresolved now.
- Report baseline totals separately from current snapshot totals; do not hard-code the old 76-event total as a live invariant.
- Include `mode=read_only`, `mutation_performed=false`, `database_mutations=0`, and `production_write_authorization=false`.

## Required automated cases
1. Several events for one row count as one unique row.
2. One row with both duplicate and missing-field events retains both categories.
3. An event without a stable row ID remains unresolved.
4. Re-running against the same snapshot yields the same grouping.
5. Historical failures are not counted as current unresolved candidates without a current-state check.

## Guardrails
No event history edits, row approvals, promotions, deletes, or canonical data changes. This contract is not a claim that the live 76-event reconciliation has already run.