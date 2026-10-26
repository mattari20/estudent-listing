# Hostinger workflow access and database grants gate

## Verified workflow design
The main-branch read-only audit workflow uses the repository secret `HOSTINGER_SSH_FINGERPRINT`, checks the deployed Laravel project path, runs preflight and reconciliation commands, and validates report JSON. It explicitly records database-grants verification as skipped under the reported shared-hosting limitation.

## Remaining operator-dependent conditions
- A trusted `SHA256:...` SSH host fingerprint must be independently obtained and configured as a repository Actions secret.
- The workflow must be dispatched on main and its exact run and artifact reviewed.
- The live report must be checked row by row; the workflow's success status alone does not close Step 3.
- A supported, independently verifiable method for confirming SELECT-only database grants remains unresolved.

## PR #757 disposition
Do not merge PR #757 as-is: it depends on unverified `OFFERING_GAP_DB_*` credentials and a stale branch. Do not assume that the current application DB user is read-only. Keep the grants limitation explicit until Hostinger provides a supported method or an authorized, verifiable alternative is established.

## Safety
Do not put secret values in repository files, comments, or artifacts. Do not run migrations or production writes. This document does not claim the live audit has been dispatched or completed.