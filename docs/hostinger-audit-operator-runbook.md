# Hostinger read-only offering-gap audit operator runbook

Date reviewed: 2026-10-26

## Preconditions

- [ ] Verify the intended repository commit and workflow file.
- [ ] Configure `HOSTINGER_SSH_FINGERPRINT` using a fingerprint confirmed through a trusted channel.
- [ ] Do not derive trust solely from an unverified `ssh-keyscan` response.
- [ ] Confirm no command in the workflow performs writes, migrations, approvals, promotions, or deletions.

## Run and inspect

1. Run the workflow **Hostinger Read-only Offering Gap Audit** from GitHub Actions after the required secret is present.
2. Confirm SSH host verification succeeds and the workflow identifies the intended application/database context.
3. Confirm the preflight and reconciliation commands exit successfully.
4. Download the workflow artifact; confirm the report commit, timestamp, run ID, expected 36 rows, and zero-mutation declaration.
5. Review all rows and compare programme/offering/campus IDs with the evidence register.
6. Record unresolved blockers and reviewer dispositions in issue #585.
7. Verify database grants independently using a trusted supported method. If unavailable, report the limitation; do not call the account read-only.

## Current limitations

The current GitHub connector does not support adding repository secrets or dispatching workflows. Those steps must be completed through GitHub's interface by an authorized operator and must not be represented as already done.

A green workflow means the workflow completed; it does not prove all data is correct or that the DB user is SELECT-only. This runbook does not authorize production writes.