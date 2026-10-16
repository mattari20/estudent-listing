# Historical Import Batch Review and Remediation Gate

| Field | Value |
| --- | --- |
| Document ID | RUN-INST-IMPORT-HISTORY-001 |
| Status | Read-only review procedure; no production writes authorized |
| Scope | Historical institution import batches, rows, source evidence, and promotion decisions |
| Intended output | Private record-level review matrix plus a sanitized public summary |

## 1. Purpose and known baseline

Use this procedure to establish what happened to historical import batches before proposing any correction. The current documented production snapshot reports 5 import batches, 24 rows (11 promoted, 6 candidates, 7 rejected), 5 active sources, 0 evidence records, and 0 source snapshots. Those counts are tied to the report timestamp and must be refreshed from the latest private report before any record-level conclusion is made.

Do not infer that a row is invalid solely because its batch name, source type, or label contains `test`. Establish the row's actual provenance and canonical effect first.

## 2. Required evidence inputs

Obtain these inputs from the latest successful, protected, read-only production report and the private report repository:

1. Report timestamp, source commit/deployment identity if available, overall status, exit code, and `mutation_performed` flag.
2. Record-level inventory of canonical institution IDs, names, slugs, types, statuses, campus/program counts, and the existing program-to-institution relationship.
3. Import batch IDs, batch keys, entity/source types, linked source IDs, validation status, row counters, and timestamps.
4. Import row IDs, statuses, match states, validation result codes, and canonical subject type/ID references.
5. Promotion decisions/events and reversal history, if present.
6. Linked source status and evidence/snapshot references, including whether evidence is active and the subject/attribute it supports.
7. Relevant identity conflicts or duplicate candidates.

If an input is unavailable, mark it `UNKNOWN / NOT RETRIEVED`; do not treat it as zero, absent, or safe.

## 3. Private review matrix

Create one row per import row in a private working document. Include only the minimum identifiers needed for review:

| Field | Required review |
| --- | --- |
| Batch ID/key | Match to the current report; do not rely on label alone |
| Import row ID | Stable row reference |
| Batch validation state | Confirm validated state and timestamp |
| Row state | Promoted, candidate, rejected, or other observed value |
| Source ID/state | Confirm linked source exists and is active |
| Evidence state | Active evidence, missing evidence, inactive evidence, or unknown |
| Promotion decision | Decision ID, type, timestamp, and actor where available |
| Canonical relationship | Subject type/ID; verify the referenced record exists |
| Identity risk | Duplicate candidate, conflict, orphan reference, or none observed |
| Assessment | Verified, needs source research, needs identity review, inconsistent, or unknown |
| Proposed next action | Read-only follow-up only; no action is executed by this audit |
| Reviewer notes | Evidence-based explanation and unresolved questions |

Keep institution names, internal IDs, raw report JSON, row payloads, and validation details in the private review artifact. The public repository may contain only aggregate counts, non-sensitive findings, and a reference to the private review.

## 4. Review order

### Stage A — Reconcile the report

- Confirm the report is the newest successful run and has `mutation_performed: false`.
- Compare its timestamp with the private repository's newest report file.
- Reconcile batch and row totals against the individual records.
- Record discrepancies; do not repair them during the audit.

### Stage B — Trace promoted rows

- For each promoted row, verify its canonical target exists and its promotion event/decision is consistent.
- Confirm the row had a validated batch and a linked active source at promotion time where history supports that conclusion.
- Verify active evidence supports the factual assertion; a source URL by itself is not proof that the current value is correct.
- Flag missing evidence, missing history, invalid targets, duplicate identities, and mismatched counts for human review.
- Do not automatically reverse any promotion.

### Stage C — Review candidates and rejected rows

- Preserve candidate and rejected states while reviewing.
- Distinguish a validation failure from a factual contradiction or an identity conflict.
- Record the specific missing evidence or validation reason needed to reconsider a row.
- Do not promote candidates or reopen rejected rows as part of this audit.

### Stage D — Reconcile canonical population

- Account for all institutions, the existing program, and every promoted import row.
- Verify program parent relationship and provenance.
- Compare campus/geography gaps with supported institution locations; do not infer campuses from institution names.
- Do not deactivate institutions simply because campuses or other optional data are absent.

## 5. Decision classifications

Use exactly one primary classification per row:

- `VERIFIED_NO_CHANGE` — available records and evidence are internally consistent; this does not certify external truth.
- `NEEDS_AUTHORITATIVE_SOURCE` — evidence is missing, stale, inactive, or insufficient.
- `NEEDS_IDENTITY_REVIEW` — duplicate, match, or canonical identity is unresolved.
- `INTERNAL_INCONSISTENCY` — references, counters, states, or events conflict.
- `UNKNOWN_INCOMPLETE_INPUT` — necessary report or history could not be retrieved.
- `PROPOSED_REMEDIATION_REQUIRES_APPROVAL` — a specific proposed change is documented but not authorized.

Every classification must include its evidence reference and rationale. Do not use a classification to trigger an automated write.

## 6. Required outputs

1. **Private row-level matrix** with all reviewed rows and supporting evidence references.
2. **Reconciliation summary** showing observed totals, calculated totals, and discrepancies.
3. **Risk register** for missing evidence, duplicate identities, orphan references, and inconsistent promotion history.
4. **Proposed remediation plan** listing exact record identifiers, current state, proposed state, source evidence, expected effect, rollback plan, and validation query.
5. **Sanitized public summary** with aggregate counts, unresolved issue categories, and a link/reference to the private artifact without exposing sensitive data.

## 7. Hard safety gates

Until each gate is explicitly satisfied, the work remains read-only:

- [ ] Latest report retrieved and freshness confirmed.
- [ ] Private row-level report available and complete.
- [ ] Batch totals reconcile with row-level totals.
- [ ] Promoted rows and canonical targets accounted for.
- [ ] Source and evidence status reviewed.
- [ ] Duplicate/identity conflicts reviewed.
- [ ] Proposed change set reviewed record by record.
- [ ] Backup and tested restore procedure verified for any future write.
- [ ] Owner explicitly approves the exact production changes.

This document does not authorize deployment, migrations, seed commands, imports, promotion, activation, deactivation, deletion, or SQL writes. Any future write must be a separate approved operation with a reviewed preflight and rollback plan.

## 8. Completion criteria for the audit

The audit is complete only when every row in the selected historical batches has a classification, each promoted row is traced to a canonical target or explicitly marked unresolved, all count discrepancies are explained, and the private evidence matrix is available for owner review. If the private report cannot be accessed, report the audit as blocked rather than guessing.


## 9. Current operational integration checkpoint

The protected diagnostics controller on repository `main` now includes the record-level audit alongside the aggregate report and geography preflight, and the private-report workflow validates all three sections. This is a repository-level fact, not confirmation of production deployment. Before starting the row-level review, confirm the deployed release and use the newest successful private report. The current workflow is configured for manual and daily scheduled execution; verify the actual latest run timestamp rather than assuming a scheduled run succeeded.

The report must remain private. Do not copy record-level names, internal IDs, row details, or raw JSON into this public repository or chat. No production correction is authorized by this review procedure.

## 10. Evidence gate for the next review group

The next review group must not start from the historic 5-batch/24-row count without refresh. First verify that the newest private report is tied to the deployed release, then reconcile its batch/row totals against the record-level audit. Preserve one private review row per import row with provenance, source/evidence status, validation outcome, canonical link, and promotion/reversal history. Where provenance or supporting evidence is absent, classify the row as `UNVERIFIED`; do not silently mark it safe or invalid.

Output from this group is a sanitized findings summary and a proposed, source-backed change set only. It is not authorization to promote rows, seed geography, or modify canonical data.

## Evidence-linked disposition rule (next review group)

Every import row must receive a provisional disposition based on retrieved evidence: `SUPPORTED`, `INSUFFICIENT_EVIDENCE`, `CONFLICTING_EVIDENCE`, `DUPLICATE_CANDIDATE`, or `INVALID_STRUCTURE`. Record the source/evidence reference and reviewer rationale for each disposition. A promoted status alone is not proof of correctness; a rejected status alone is not proof that the underlying institution is invalid. Rows without source evidence remain unverified and must not be promoted or republished as part of this audit.

## Private audit matrix fields for each row

Keep one row per import row in the private review workspace. Record: report retrieval/run identifier; batch identifier; import-row identifier; source reference and retrieval date; institution/program/campus candidate; source agreement or conflict; current row state; canonical relationship if present; validation findings; duplicate candidates; promotion/reversal history; provisional disposition; reviewer rationale; confidence; and next action. Do not copy internal identifiers or row-level details into public PRs. Missing values must be recorded as `UNKNOWN` or `NOT PROVIDED`, never inferred as zero or treated as proof of invalidity.

A row is ready for a proposed correction only when its evidence reference is reviewable, the proposed change is specific, the current canonical relationship is understood, and a second check confirms the change will not create a duplicate or break linked programs/campuses. Otherwise keep it in the unresolved queue.

## Next execution group: 20 controlled review batches

This group is a sequence of review work items, not permission to write to production. Execute each item only after the report acceptance gate in `PRODUCTION_DATA_GAP_REPORT_RUNBOOK.md` passes. Keep raw rows and identifiers in the private review workspace. Do not infer current counts from historical reports.

### Batch checklist

- [ ] **Batch 01 — Release identity:** confirm the deployed Hostinger release/commit using existing deployment evidence.
- [ ] **Batch 02 — Report freshness:** capture the latest successful diagnostics run and report timestamp.
- [ ] **Batch 03 — Contract completeness:** verify aggregate report, record-level audit and geography preflight belong to one retrieval.
- [ ] **Batch 04 — Read-only markers:** validate all `mutation_performed: false` markers and geography's `seed_command_executed: false`.
- [ ] **Batch 05 — Aggregate reconciliation:** compare current aggregate totals with record-level totals; document any mismatch.
- [ ] **Batch 06 — Batch inventory:** enumerate import batches from the accepted private report, not old screenshots or historical totals.
- [ ] **Batch 07 — Row coverage:** confirm every row in scope has a unique private audit entry.
- [ ] **Batch 08 — Provenance coverage:** record source reference, retrieval date and evidence availability for each row.
- [ ] **Batch 09 — Evidence quality:** distinguish official/primary evidence from secondary or unverified references.
- [ ] **Batch 10 — Identity normalization:** compare institution names and alternate names without changing canonical records.
- [ ] **Batch 11 — Campus relationships:** review campus identity and parent-institution relationships.
- [ ] **Batch 12 — Geography mapping:** compare country/province/district/city references and report orphaned or conflicting links.
- [ ] **Batch 13 — Program relationships:** check program ownership and campus association against evidence.
- [ ] **Batch 14 — Duplicate candidates:** cluster possible duplicates for human comparison; do not auto-merge.
- [ ] **Batch 15 — Structural validation:** identify reproducible schema/relationship rule failures without changing records.
- [ ] **Batch 16 — Promotion history:** reconcile import, promotion and reversal history where available.
- [ ] **Batch 17 — Row disposition:** assign only the documented provisional outcome and rationale to each reviewed row.
- [ ] **Batch 18 — Correction proposals:** draft source-backed, minimal proposed changes for fully evidenced rows only.
- [ ] **Batch 19 — Independent review:** recheck proposed changes, duplicates, linked relationships and rollback implications.
- [ ] **Batch 20 — Final readiness report:** publish a sanitized summary of counts, unresolved blockers, proposed changes and required approvals.

### Completion record

For each batch, record status (`PASS`, `BLOCKED`, or `NOT STARTED`), evidence reference, reviewer, timestamp, findings, and next action. Mark a batch `PASS` only when its required evidence exists and its acceptance criteria are met. A blocked batch stops dependent batches; it must not be marked complete merely to preserve speed.

### Explicit no-write boundary

This checklist does not authorize database updates, imports, promotion/reversal, publication, geography seeding, migrations, or deployment changes. Proposed corrections remain proposals until separately reviewed and explicitly approved. If deployment identity or report freshness is unknown, stop at Batch 01 or 02 and report the blocker rather than auditing stale data.
