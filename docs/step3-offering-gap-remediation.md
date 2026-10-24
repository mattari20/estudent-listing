# Step 3: Offering gap remediation

The live audit at https://github.com/mattari20/estudent-listing/actions/runs/40065220614 reported 36 active programs with no offering rows.

For each record, verify both the official current programme source and the exact campus. Do not create an active offering based only on programme existence. Keep intake dates, fees, duration, and availability unknown unless official evidence supplies them. Any proposed insert must be idempotent, default to dry-run, verify the program and campus belong to the same institution, and be followed by the read-only live audit.
