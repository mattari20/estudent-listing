# Phase 2 — Four-Batch Execution Workpack (2026-10-25)

## Scope and execution rules

This workpack separates repository/code verification from live Hostinger/database verification. The repository can be audited through GitHub; it cannot prove that the production scheduler ran or that current live rows match committed CSVs.

**Safety gates**
- Scheduled discovery remains deterministic by default (use_ai: false); no paid AI API calls.
- Discovery output stays non-canonical until governed review and approval.
- Do not delete batches, snapshots, evidence, or candidate history.
- Do not perform production data writes, migrations, approvals, promotions, or deletions during these batches.
- Treat counts of historical failure events as events until reconciled to unique import-row IDs.
- Every live-data finding must cite the report/run timestamp and the specific row/batch IDs.

## Batch 1 — Candidate audit (112 GCU rows)

Deliverable: record-level candidate disposition file using gcu-candidate-disposition-template.csv.

For each row, record the original batch ID and row ID, extracted candidate name, official source URL, evidence excerpt/snapshot, extractor version, normalized slug, duplicate match, validation errors, disposition, and reviewer rationale. Allowed dispositions: keep-for-review, duplicate-of-row, invalid-navigation-or-footer, unsupported-by-source, needs-official-evidence, already-dispositioned. Do not bulk reject or promote.

Exit gate: all 112 rows represented exactly once; source and snapshot provenance retained; no unsupported claims about correctness; duplicate targets reference a real row ID.

## Batch 2 — Promotion-failure reconciliation

Deliverable: promotion-failure-reconciliation-template.csv.

Map each historical event to its event ID and import-row ID, then group events by unique row. Record current row status, current validation errors, duplicate target, whether the condition is still present, final disposition, and evidence. The historical baseline is 76 events (31 duplicate/identity collisions and 45 validation/missing-field events); this is **not** a claim of 76 unique unresolved rows.

Exit gate: every event has a row mapping or a documented unmapped reason; row-level current disposition is clear; event counts and unique-row counts are reported separately.

## Batch 3 — Programme/offering gap verification

Use step3-offering-gap-evidence-register.csv as the starting inventory (36 records / 36 unique programme IDs at the last repository audit). Verify each current official programme page and campus source, canonical institution/campus identity, existing offering duplicates, and whether the programme is active/current. The existing register contains 2 programme rows without an official programme URL (IDs 71 and 74) and 8 rows without a campus source URL (IDs 39, 40, 69, 71, 74, 7, 55, 10).

Exit gate: each gap has a dated evidence URL and duplicate check, or is explicitly blocked pending evidence. Do not infer the correct campus from a programme page that does not support it. Produce a proposed repair list only; no canonical writes in this batch.

## Batch 4 — Scheduler, review package, and closure verification

Use phase2-runtime-verification-checklist-2026-10-25.md. Verify GitHub CI against exact commit SHA, then separately confirm the deployed Hostinger revision, scheduled command execution, deterministic/no-API behavior, private CSV + JSON package retrieval, no-change outcome, and fresh read-only audits. GitHub CI success is not evidence of Hostinger runtime success.

Exit gate: fresh live audits are linked; scheduler and protected retrieval have observable evidence; all blockers are resolved or explicitly dispositioned. Do not close Step 3 merely because code is merged.

## Current baseline and known limitations

- PR #730: missing candidate status stays unknown, not active.
- PR #731: source URLs with embedded credentials are rejected.
- PR #732: previous audit note documents the static register and remaining live checks.
- The last issue #585 baseline reported 112 GCU candidate rows, 36 active programmes lacking active offerings, and 76 historical promotion-failure events. These counts need fresh live confirmation before being treated as current.
- Issue #548 requires zero-cost scheduled discovery and private review packages. Repository configuration alone cannot prove Hostinger's scheduler is running.

## Reporting format

At the end of each batch report: completed evidence; exact changed files/PR/commit; CI state; live checks actually performed; unresolved blockers; count basis (events vs unique rows); and the next batch's exit gate. Mark unavailable runtime evidence as **not verified**, not passed.
