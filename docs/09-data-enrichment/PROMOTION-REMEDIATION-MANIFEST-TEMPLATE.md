# Import-promotion remediation manifest template

Use this template for a restricted, read-only investigation of rows whose approval or promotion state does not reconcile with canonical records. One row per affected import row. Do not place raw candidate payloads, credentials, personal data, or unrestricted exception text in this manifest.

| Field | Required content |
|---|---|
| Check timestamp | ISO-8601 timestamp and timezone |
| Deployed revision | Revision observed independently from repository CI |
| Import batch ID | Stable batch identifier |
| Import row ID | Stable row identifier |
| Entity type | Institution, campus, program, or offering |
| Current row status | Exact lifecycle status |
| Match state | Exact match/review state |
| Approval decision ID | ID or not found |
| Promotion attempt ID | ID or not found |
| Attempt outcome | Promoted, skipped, failed, not attempted, or unknown |
| Canonical target | Entity type and ID, if any |
| Parent relationship | Parent IDs and active/inactive state |
| Source evidence | Evidence IDs and verification status; no raw payload |
| Geography check | Pass, fail, not applicable, or unknown |
| Existing canonical identity | Found, not found, or ambiguous |
| Failure category | Controlled category from the investigation runbook |
| Sanitized diagnostic | Exception class and safe constraint hint only |
| Classification | Explicit category and short rationale |
| Proposed action | Review, correct source data, retry in isolated test, or hold |
| Risk and dependencies | Duplicate risk, parent dependencies, evidence needs |
| Approval needed | Role/authorization required before action |
| Verification criterion | Expected read-only count/relationship after authorized repair |

## Controlled failure categories

- validation blocked
- identity conflict
- missing or inactive parent
- source/evidence gate
- geography gate
- already promoted
- transaction/constraint failure
- not attempted
- unknown

## Reconciliation checks

Record each lifecycle counter independently: imported, valid, review, approved, eligible, attempted, promoted, skipped, failed, and canonical records created/updated. Do not derive one count by subtracting another unless the implementation explicitly guarantees mutually exclusive states.

## Stop conditions

Keep a row on hold when its outcome is unknown, canonical identity is ambiguous, evidence is insufficient, a parent is missing/inactive, or the proposed repair would require a live write without reviewed authorization. This template does not authorize promotion, activation, publication, or any database mutation.
