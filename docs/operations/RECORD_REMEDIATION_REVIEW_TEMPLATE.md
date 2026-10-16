# Institutes Record Remediation Review Template

Use this template in authorized private working notes for each proposed data correction. Do not place private record-level production data in this public repository.

## Review record

- Review ID:
- Date reviewed (UTC):
- Reviewer:
- Entity type: institution / campus / geography / program / offering / intake / import row
- Internal record ID (keep in private notes):
- Source batch/row ID (if applicable):
- Decision: create / update / no change / reject / unresolved
- Review status: draft / evidence checked / ready for approval / approved / completed

## Current state and proposed change

- Current values (minimum necessary; private notes only):
- Proposed values (minimum necessary; private notes only):
- Exact fields affected:
- Related records/foreign-key dependencies:
- Why this change is needed:
- Is this based only on a missing-data count? yes / no
- Could the action reactivate, overwrite, merge, or deactivate an existing record? yes / no / unknown

## Evidence

- Authoritative source name:
- Source URL or private evidence reference:
- Source type: official institution page / official government source / official prospectus / other
- Date checked (UTC):
- Relevant statement or evidence location:
- Evidence scope: institution-wide / campus-specific / program-specific / intake-specific / geography-specific
- Confidence: high / medium / low
- Conflicting evidence:
- Unresolved questions:

## Safety and approval

- Fresh pre-change diagnostic report ID/time:
- Production deployment revision verified? yes / no
- Backup identifier and timestamp (private operations notes):
- Restore test or restore capability verified by:
- Rollback procedure:
- Required approval and approver:
- Approved exact change list reference:
- Stop condition if actual state differs from reviewed state:

## Post-change verification (complete only after an separately approved write)

- Actual action and timestamp:
- Actual fields changed:
- Result matched approved plan? yes / no
- Post-change aggregate report reference:
- Post-change record audit reference:
- Integrity checks passed? yes / no
- Unexpected side effects:
- Rollback required? yes / no
- Final decision: verified / rolled back / needs investigation

## Rules

- Keep all private identifiers and source payloads in approved private notes only.
- Do not invent missing facts or treat a website mention as evidence for a different campus or admission cycle.
- Do not bulk-promote candidates or retry rejected rows without inspecting each row's recorded reason.
- An approved plan is not itself permission to perform a production write.
- If the live record differs from the reviewed baseline, stop and regenerate the change proposal.
