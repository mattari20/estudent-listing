# Step 3 proposed repair-plan review gate

Date reviewed: 2026-10-26

No production record changes are authorized by the current evidence-gathering work. Before proposing any write, prepare a separate row-level repair plan containing:

1. Exact programme, offering, institution, and campus IDs affected.
2. Current values and proposed values, with a reason for each field change.
3. Official source evidence for the exact institution identity, award title, delivery mode, and teaching campus.
4. Duplicate/collision analysis showing whether rows are unique, duplicates, or historical failure events only.
5. Impact analysis for listings, search, detail pages, foreign keys, and related records.
6. Reversible execution plan, validation queries, expected row counts, and post-change checks.
7. Explicit approval scope and a rollback/recovery plan appropriate to the environment.
8. Confirmation that the database connection and effective grants are understood; a green audit is not proof of read-only permissions.

## Decision rules

- If identity, award, or campus mapping remains ambiguous, mark the row blocked and do not write.
- Do not delete or merge based only on similar names.
- Do not equate 76 historic promotion-failure events with 76 unique unresolved programme rows.
- Do not execute the plan until the exact impact and record-level changes have been reviewed and explicitly authorized.

This gate preserves the existing no-production-write boundary during remediation.