## Known baseline
The issue #585 baseline reports 76 historical promotion-failure events: 31 duplicate/identity collisions and 45 validation/missing-field events. These are events, not confirmed unique unresolved rows.

## Required output
Produce a read-only report with one row per stable import/candidate row ID, retaining all related event IDs and timestamps. Include current candidate status, latest failure category, prior categories, duplicate/identity collision target (if known), validation fields missing, source batch, and proposed disposition.

## Classification rules
- Do not equate event count with unique row count.
- Group only on a stable row identifier; do not group solely by programme title, slug, or institution name.
- Keep duplicate/identity collision events distinct from missing-field/validation events; a row may have more than one event category.
- Separate historical events from currently unresolved candidate rows.
- Mark rows with missing stable IDs or ambiguous identity as `needs-manual-reconciliation`; never infer a canonical target.
- Preserve raw event history and provenance; do not delete or rewrite historical events.

## Validation checks
- Total source events reconcile to 76 for the baseline snapshot, unless the snapshot is explicitly newer.
- Event-to-row mapping accounts for every event exactly once.
- Unique row count is computed from stable IDs, not assumed to equal 76.
- Category subtotals reconcile to 31 and 45 for the baseline snapshot, while allowing multi-category rows to be reported transparently.
- Read-only tests cover repeated events for one row, mixed categories, missing IDs, and stale historical events.

## Safety
This is a reporting specification only. Any candidate disposition or canonical repair requires separate source evidence, duplicate checks, a row-level plan, and explicit authorization.