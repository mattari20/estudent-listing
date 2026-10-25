# Programme-offering gap reconciliation (read-only)

## Purpose

The existing discovery agent collects programme/campus candidates into governed import batches. It does not currently model a programme-to-campus offering relationship. The Step 3 register is a separate reconciliation input, so the agent must not treat a candidate programme as proof that an offering exists.

The command `php artisan institutes:offering-gap-reconcile --json` connects the evidence register to the live canonical tables in read-only mode. It reports one disposition per register row and is designed to be run against a controlled read-only database connection.

## Row classifications

- `ACTIVE_OFFERING_ALREADY_EXISTS`: exact programme, campus, and delivery mode already have an active offering. Verify evidence before closing the gap.
- `INACTIVE_OFFERING_EXISTS_REQUIRES_REVIEW`: an exact relationship exists but is not active. Never reactivate automatically.
- `OFFERING_EXISTS_DELIVERY_MODE_MISMATCH_REQUIRES_REVIEW`: the programme/campus pair exists with another delivery mode. Compare against official evidence.
- `OFFERING_EXISTS_AT_OTHER_CAMPUS_REQUIRES_REVIEW`: the programme has an offering elsewhere. This is a campus-mapping review, not proof that the proposed campus is wrong.
- `BLOCKED_INSUFFICIENT_OR_MISMATCHED_EVIDENCE`: identity, parent relationship, register uniqueness, or evidence-pointer checks fail.
- `MISSING_RELATIONSHIP_CANDIDATE_REQUIRES_SOURCE_REVIEW`: no matching relationship was found and the register passes its basic readiness gates. This is still a proposal, not an approved repair.

## Safety and limits

- The command performs SELECT queries only and explicitly reports zero database mutations.
- It does not fetch URLs; URL validation checks syntax and must not be interpreted as confirmation that the page exists or supports the proposed relationship.
- It never creates, updates, activates, approves, promotes, publishes, or deletes canonical data.
- A missing relationship may be repaired only after source-by-source review, a row-level impact plan, a second read-only validation, and explicit authorization for the specific production write.
- The committed CSV is an evidence register and may be stale. A live run must retain the report, commit SHA, run ID, and source-review evidence together.

## Running

```bash
php artisan institutes:offering-gap-reconcile --json
```

For isolated tests only, the expected row count can be overridden:

```bash
php artisan institutes:offering-gap-reconcile --register=/tmp/sample.csv --expected-rows=2 --json
```

The default expected register size is 36. A count mismatch is surfaced as a blocked report status; it does not trigger any write.
