# Import-promotion failure investigation runbook

**Purpose:** Investigate import rows flagged as approved or promotion-ready but not reflected in canonical records.
**Mode:** Read-only diagnosis first. This runbook does not authorize production writes.

## Required evidence

For each affected row, record only identifiers and safe metadata needed to correlate events:

- import batch ID and import row ID;
- row status and match state;
- canonical subject type and ID, if present;
- approved promotion decision and timestamp, if present;
- latest promotion attempt outcome and sanitized failure category;
- parent subject type and ID for campus/programme rows;
- required-table/column availability at the time of diagnosis;
- source/evidence readiness and geography readiness result;
- whether a canonical record already exists for the intended identity.

Do not copy raw candidate payloads, secrets, personal data, or unrestricted exception messages into a public report.

## Investigation sequence

1. Run the read-only data-gap summary and record its check timestamp and deployed revision separately.
2. Run the record-level audit with summary mode first. Restrict any detailed review to the four flagged rows and an authorized private report.
3. Correlate each row to its batch, approval decision, promotion attempt, canonical target, and audit events.
4. Classify the outcome as one of: validation blocked, identity conflict, missing/inactive parent, source/evidence gate, geography gate, already promoted, transaction/constraint failure, or unknown.
5. For transaction/constraint failures, capture the exception class and a sanitized constraint/table/column hint where available; do not expose SQL credentials or full payloads.
6. Compare the current canonical record counts before and after any *authorized test-only* reproduction. Use an isolated test database for reproduction.
7. Produce a row-level remediation manifest with evidence, expected outcome, risk, and proposed repair. Keep proposed repairs separate from execution.
8. Obtain explicit authorization and a reviewed impact plan before any live write, promotion, activation, or publication.

## Required reconciliation

For the affected rows, reconcile these counts independently:

- rows imported;
- rows valid;
- rows awaiting review;
- rows approved;
- rows eligible for promotion;
- rows attempted;
- rows promoted;
- rows skipped;
- rows failed;
- canonical records created or updated.

These counters represent different lifecycle states and must not be substituted for one another. An approved row is not necessarily eligible for promotion, and a successful attempt does not prove public visibility.

## Exit criteria

The investigation is complete only when every flagged row has a traceable classification, supporting audit evidence, and an explicit next action. Any row classified as unknown remains blocked from promotion.

## Safety constraints

- Keep live diagnostics read-only.
- Do not infer that a row is safe to promote from its status alone.
- Do not auto-approve, auto-resolve identity conflicts, or publish records as part of diagnosis.
- Use synthetic data in automated tests.
- Report live deployment verification separately from repository CI status.


## Fast remediation queue — current verified run

The latest successful read-only audit identifies four rows whose latest promotion event is `promotion_failed` and whose recorded exception class is `RuntimeException`:

| Import row | Institution | Current safe next action |
|---:|---|---|
| 15 | Lahore College for Women University | Correlate private server log entry with attempt timestamp; classify before retry |
| 16 | University of Education Lahore | Correlate private server log entry with attempt timestamp; classify before retry |
| 17 | University of Veterinary and Animal Sciences Lahore | Correlate private server log entry with attempt timestamp; classify before retry |
| 29 | Federal Urdu University of Arts, Sciences & Technology | Correlate private server log entry with attempt timestamp; classify before retry |

Do not retry these rows solely because the identity decision is approved. Promotion also requires a validated batch, active source, active evidence, no open conflicts, a valid candidate state, and no canonical identity collision. Keep exception messages in restricted server logs; public/summary audit output should contain only a safe category.

The same read-only run reports 36 active programs without active offerings. Review those program records against authoritative sources and create offerings only from verified evidence through the approved workflow. No live records were changed by this audit.
