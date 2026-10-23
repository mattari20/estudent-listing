# Import Data-Gap Audit Guide

## Purpose

The data-gap record audit is a read-only triage aid for import rows. It reports aggregate status and a row-level review disposition without emitting raw payloads, notes, validation text, or promotion-event reasons.

## Automated commands

- `php artisan institutes:data-gap-report --json` — database/schema health, canonical relationship gaps, geography integrity, import/source table counts, and coverage distributions.
- `php artisan institutes:data-gap-record-audit --json` — row-level audit metadata and proposed review dispositions.
- `php artisan institutes:data-gap-record-audit --json --summary` — aggregate counts only; row-level records are omitted.

These commands do not change, activate, deactivate, promote, publish, delete, or retry records.

## Review disposition meanings

| Disposition | Intended next action |
|---|---|
| `retain_promoted_history` | Preserve the promoted row and its audit trail; separately verify evidence/publication gates if flagged. |
| `verify_duplicate_link` | Check the canonical match and dependencies before any archival decision. |
| `correct_validation_errors` | Review private validation details and correct the source row before any retry. |
| `review_rejection` | Preserve the rejection history and review the recorded decision. |
| `investigate_promotion_failure` | Debug the failure and its redacted category first; do not automatically retry or delete. |
| `resolve_open_conflicts` | Explicitly resolve every open conflict before promotion/publication. |
| `review_identity_decision` | Record an explicit approved-new or approved-match decision for institution identity. |
| `review_promotion_decision` | Review the entity-specific promotion decision requirement. |
| `manual_review` | Available fields do not justify an automated action. |

Disposition is a recommendation for human triage, not an authorization to mutate production data.

## Promotion failure categories

The report emits safe categories for recognized patterns, such as missing active source evidence, canonical identity collision, unresolved conflict, missing approval, invalid row/batch state, unsupported entity type, and database/schema errors. Unrecognized errors remain `other_runtime_exception` or `unclassified`; do not infer the root cause from a generic category.

## Mandatory write gate

Before any cleanup, retry, promotion, or publication:

1. Review the exact row and batch IDs, latest event, identity decision, conflict count, evidence state, canonical links, and dependencies.
2. Verify the proposed change against approved source evidence.
3. Produce a dry-run manifest with before/after counts and a recovery plan.
4. Obtain explicit review/authorization.
5. Record the audit trail and rerun diagnostics and public-site checks.

Never delete promoted history or rejected-row history merely to make aggregate counts look cleaner. Never treat an empty table or a count discrepancy as proof that the data should be deleted or imported again.
