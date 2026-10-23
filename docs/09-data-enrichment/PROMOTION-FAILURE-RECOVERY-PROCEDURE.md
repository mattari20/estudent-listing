# Promotion failure recovery procedure

This procedure is for diagnosing failed import-row promotion attempts. It does not authorize retrying a row, changing canonical records, or publishing data.

## Before investigating

1. Capture the check timestamp and independently observed deployed revision.
2. Identify the import batch and row by stable IDs.
3. Preserve current status, match state, canonical target, latest decision, latest attempt, and audit-event IDs.
4. Confirm the configured database and required schema tables are available.
5. Keep raw payloads and unredacted exception details in restricted diagnostics only.

## Triage order

1. **Decision gate:** confirm an explicit decision exists, belongs to this row, and authorizes the intended operation.
2. **Batch/source gate:** confirm the batch is in the expected validated state and its source is active.
3. **Evidence gate:** verify active evidence supports the exact claim being promoted.
4. **Conflict gate:** identify open conflicts and require explicit resolution before retry.
5. **Parent gate:** verify parent type, ID, existence, and active status where required.
6. **Identity gate:** compare canonical identity and aliases; never resolve duplicates from a name-only guess.
7. **Data gate:** check required fields, normalized payload, foreign keys, unique constraints, and geography references.
8. **Transaction outcome:** correlate attempt start/end, sanitized error category, canonical IDs, and audit events.
9. **Visibility gate:** if a canonical row exists, independently verify publication status and public filters.

## Recovery classifications

- retry in isolated test after correction;
- request source/evidence review;
- resolve identity conflict;
- repair parent relationship through an approved workflow;
- inspect schema/deployment mismatch;
- already promoted; investigate visibility separately;
- unknown; hold and escalate.

## Retry controls

A retry requires a documented cause, corrected input or condition, duplicate-risk assessment, expected postcondition, and reviewer approval. Do not retry solely because a counter does not match. Do not auto-approve, auto-resolve conflicts, or auto-publish during recovery.

## Completion record

Record the diagnosis, evidence IDs, safe error category, proposed action, authorization, and post-check. A successful code workflow is not proof that the live database or public directory was repaired.
