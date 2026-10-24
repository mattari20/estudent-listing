# Step 3: Offering gap remediation

The latest read-only live audit reported 36 active programmes with no offering rows:
https://github.com/mattari20/estudent-listing/actions/runs/40065220614

## Campus-aware evidence register

The register now records a candidate campus ID/name, candidate delivery mode, and a separate campus-source URL for each gap. These are review targets, not verified facts. Candidate mappings were carried forward from the current audit context and must be checked against the live campus table and current official institutional evidence before any offering is proposed.

Programme existence alone is not campus evidence. Do not map a Lahore programme to an Islamabad campus, and do not assume a virtual programme's support campus is its teaching location. The Virtual University rows are marked online as a review hypothesis; confirm the delivery model before using it.

## Automated validation

Run:

```bash
python scripts/validate_step3_offering_gap_register.py
```

The validator checks the 36-record count, required columns, unique programme IDs, numeric IDs, allowed delivery modes, HTTPS source URLs, and safety gates for missing evidence, unconfirmed campuses, and wrong-campus records. GitHub Actions runs the validator on relevant pull requests and main-branch changes.

## Live database preflight (read-only)

After the new command is deployed with the approved application revision, run:

\`\`\`bash
php artisan institutes:offering-gap-preflight --json --no-ansi
\`\`\`

The command compares every register row to live programme and campus IDs, verifies both records belong to the expected institution, checks for existing programme/campus offering rows (including active rows), and applies the official programme/campus evidence gate. It reports blockers per record and always declares zero database mutations. It does not insert, update, activate, deactivate, or publish any offering. A `READY_FOR_MANUAL_REVIEW_ONLY` result is not production-write authorization.

The register file must be present at the path supplied by `--register`; otherwise the command fails closed. Run it only after the command and evidence register are deployed at the same approved revision.

## Apply gate

No production database writes are authorized by this register or PR. Before any apply workflow is proposed, it must:
1. Default to dry-run and print exact proposed rows.
2. Resolve programme and campus IDs against the live database; verify both belong to the same institution.
3. Require current official programme evidence and explicit campus/delivery evidence for each row.
4. Reject duplicate programme/campus/delivery-mode offerings and refuse ambiguous rows.
5. Require an explicit apply switch, log every affected ID, and remain idempotent.
6. Re-run the read-only live audit and compare before/after counts.

The `ready-for-validation` label means only that a row can enter a deeper preflight; it is not approval for a production insert. Any row still lacking campus-specific evidence must remain blocked from application.
