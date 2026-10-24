# Step 3: Offering gap remediation

The latest read-only live audit reported 36 active programmes with no offering rows:
https://github.com/mattari20/estudent-listing/actions/runs/40065220614

## Evidence gate

For every programme, verify both (1) a current official programme source and (2) the exact campus or delivery model. Programme existence alone is not campus evidence. Do not infer a physical campus for a virtual programme. Do not silently map a Lahore programme to an Islamabad campus or vice versa.

Keep intake dates, fees, duration, availability, and status unknown unless official evidence supports each field. The register is an evidence tracker, not an authorization to publish or activate records.

## Automated validation

Run:

```bash
python scripts/validate_step3_offering_gap_register.py
```

The script checks the expected row count, required columns, unique programme IDs, valid decision states, HTTPS sources, and safety gates for missing sources, unconfirmed campuses, and wrong-campus records. GitHub Actions runs the same validator on pull requests and pushes that change the register.

## Apply gate

No production database writes are authorized by this PR. Before any apply workflow is proposed, it must:
1. Default to dry-run and show the exact proposed rows.
2. Confirm the programme and campus exist and belong to the same institution.
3. Require a source URL and explicit campus/delivery evidence for each row.
4. Reject duplicate programme/campus/delivery-mode offerings and refuse ambiguous rows.
5. Require an explicit apply switch, log every affected ID, and remain idempotent.
6. Re-run the read-only live audit and compare before/after counts.

The current register has only one row marked `ready-for-validation`; that is not approval for a production insert. All other rows remain review/blocked pending evidence.
