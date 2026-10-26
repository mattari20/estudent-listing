## Purpose
Make the remaining live audit blocker explicit and actionable without weakening safety.

## Current verified state
- The main workflow `.github/workflows/hostinger-readonly-offering-gap-audit.yml` pins the SSH host fingerprint through `HOSTINGER_SSH_FINGERPRINT`.
- The workflow intentionally records database grants as skipped because the reported Hostinger shared-hosting setup does not provide a second DB user in hPanel.
- The live 36-row report has not yet been retrieved and reviewed in this work cycle.
- PR #757 is stale, depends on unverified `OFFERING_GAP_DB_*` secrets, and must remain unmerged unless a supported, independently verified access path is established.

## Operator checklist
1. Obtain the SSH host-key fingerprint from a trusted Hostinger control panel/support channel, not an unauthenticated scan alone.
2. Configure `HOSTINGER_SSH_FINGERPRINT` in repository Actions secrets using the exact `SHA256:...` value.
3. Confirm the existing SSH secrets are available without exposing their values in logs.
4. Dispatch the main-branch read-only workflow with the documented project path.
5. Confirm successful workflow conclusion, expected report artifact, JSON validity, 36 register rows, zero reported mutations, and the explicit review-required status.
6. Download the artifact, review all 36 rows, and record row-level dispositions in the evidence tracker.
7. If the secret cannot be configured or workflow cannot be dispatched, record the exact UI/API permission blocker and do not claim a live audit ran.

## Safety
No migrations, writes, approvals, promotions, deletions, or credentials in artifacts/logs. A successful workflow is not equivalent to Step 3 closure.