# Step 3 Remediation Batch Audit — 2026-10-25

## Scope and safety

This is a repository-level audit note based on the committed evidence register, discovery-agent source, scheduled-command configuration, and the current remediation issue. It is **not** a live production database audit. No production records were changed, approved, promoted, deleted, or published during this review.

## Confirmed code changes in this batch

- PR #730 preserves a missing discovery-candidate status as `unknown` rather than defaulting it to `active`.
- PR #731 rejects source URLs containing embedded username/password credentials.
- Both PRs were merged on 2026-10-25. Their associated Laravel CI and Test workflows reported success before merge.

## Static offering-gap register findings

The committed file `docs/step3-offering-gap-evidence-register.csv` contains 36 records and 36 unique programme IDs.

- 2 records lack an official programme source URL and are marked `not-verified`:
  - Programme ID 71 — BS Accounting & Finance
  - Programme ID 74 — Bachelor of Design
- 8 records lack a campus source URL:
  - Programme IDs 39, 40, 69, 71, 74, 7, 55, 10
- Remediation decisions in the register: 31 `review`, 1 `ready-for-validation`, 4 `blocked`.

These counts describe the committed CSV only. They do not prove that current database records or campus/offering relationships are correct. The 4 blocked records must not be repaired from assumptions.

## Discovery-agent safety review

- `bootstrap/app.php` schedules `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly with overlap protection.
- The GCU programme profile is enabled and configured for deterministic extraction (`use_ai: false`); the COMSATS profiles remain disabled pending correct parent-institution resolution.
- The discovery service records source snapshots, evidence, import batches/rows, duplicate checks, and provenance.
- Review packages are written to the local storage disk and include a CSV plus JSON manifest. Existing tests cover provenance, package reuse, and no automatic approval/promotion.
- Repository configuration does not establish that Hostinger's scheduler is running or that private packages are retrievable in the live deployment.

## Still unresolved — requires live read-only data or official evidence

1. Classify all 112 GCU candidates by record, source evidence, extraction quality, duplicates, and disposition.
2. Reconcile 76 historical promotion-failure events to unique import-row IDs and current dispositions. Event counts must not be reported as unique unresolved rows.
3. Verify all 36 programme-offering gaps against current official programme and campus sources, and prepare a duplicate-checked proposed repair plan.
4. Confirm safe refresh/supersede behavior for stale populated discovery batches without deleting history or duplicating rows.
5. Verify Hostinger scheduler runs, deterministic zero-API behavior, protected review-package retrieval, and fresh live audit results.

## Next gate

Do not declare Step 3 complete until record-level findings are dispositioned, code tests pass, deployment verification is documented, and a fresh read-only live audit has no unexplained blocking findings. Production data repair remains a separate action requiring a reviewed record-level plan and explicit authorization.
