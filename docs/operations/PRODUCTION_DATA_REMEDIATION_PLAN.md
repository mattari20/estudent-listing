# Institutes Production Data Remediation Plan

## Status and safety boundary

- Status: proposed plan; private report review and human approval required
- Baseline report date: 2026-10-16
- Scope: data review and controlled remediation planning
- No production writes are authorized by this document.
- Do not include private record payloads, access tokens, or unnecessary personal data in this repository.

## 1. Baseline from the read-only diagnostic report

The report reviewed on 2026-10-16 recorded:

| Area | Baseline |
|---|---:|
| Active institutions | 10 |
| Active campuses | 0 |
| Active programs | 1 |
| Program offerings | 0 |
| Admission intakes | 0 |
| Geography records | 0 |
| Proposed geography seed records | 8 |
| Import rows | 24 |
| Import rows promoted | 11 |
| Import candidates | 6 |
| Import rows rejected | 7 |
| Captured evidence records | 0 |
| Source snapshots | 0 |
| Duplicate institution-name candidate groups | 0 |
| Import conflicts | 0 |

These are a timestamped database snapshot, not proof of real-world completeness or official-source accuracy. Refresh the report before any approved production change.

## 2. Batch plan and gates

### Batch 0 — Preserve baseline and verify environment

**Goal:** ensure the team is working against the correct production instance and has a recovery path.

Tasks:
- Retrieve a fresh aggregate report, record-level audit, and geography preflight.
- Verify the production deployment revision independently using the hosting/deployment record.
- Confirm a database backup exists, is recent, and can be restored; record its timestamp and responsible operator in authorized private operations notes.
- Keep the record-level report in the private report repository.

Exit gate:
- Fresh reports exist; environment identity is established; backup restore capability is verified.
- If any gate fails, stop without writing to production.

### Batch 1 — Geography identity and hierarchy review

The preflight proposed eight entries:

| Name | Proposed code | Review required |
|---|---|---|
| Pakistan | PK | Country identity and root semantics |
| Punjab | PK-PB | Parent and accepted code |
| Sindh | PK-SD | Parent and accepted code |
| Khyber Pakhtunkhwa | PK-KP | Parent and accepted code |
| Balochistan | PK-BA | Parent and accepted code |
| Islamabad Capital Territory | PK-IS | Administrative type and parent |
| Gilgit-Baltistan | PK-GB | Administrative type and parent |
| Azad Jammu and Kashmir | PK-AJ | Administrative type and parent |

Before any write:
- Compare every proposed row against current records by stable identity, not only name.
- Review the exact fields the seed would create or update, including active/status changes and parent IDs.
- Validate codes and administrative types against the project's selected authoritative reference standard.
- Resolve duplicates and mismatched existing records explicitly.
- Treat this as a reviewed change list, not a command to run the existing seed.

**Seed safety gate (updated after PR #176):** `PakistanGeographySeedService::seedTopLevel()` now checks for duplicate identities and mismatched existing values and stops before writes when it finds them; it creates missing rows without updating existing rows. This is a code-level safety improvement, not authorization to run the seeder against production. Before any production use, refresh the preflight, validate the proposed administrative codes/types against the selected authoritative standard, confirm deployment identity and backup/restore readiness, and obtain explicit approval for the exact change.

Exit gate:
- Each geography item is marked create, update, no-change, or unresolved with evidence and rationale.
- All unresolved items remain untouched.

### Batch 2 — Institution-to-campus identity and locations

The baseline has 10 active institutions and no active campuses.

For each institution:
1. Capture institution ID and canonical name in private working notes.
2. Verify official identity and canonical website from an authoritative source.
3. Determine whether it has one campus, multiple campuses, or a legally/operationally different location model.
4. Verify each campus name and address/location independently.
5. Match location to reviewed geography only when evidence supports it.
6. Record source URL/document, checked date, relevant excerpt/reference, reviewer, confidence, and unresolved conflicts.
7. Do not create a campus based on a missing-data count alone.

Exit gate:
- Each institution has an explicit outcome: verified campus proposal, verified multi-campus proposal, no campus applicable with reason, or unresolved.
- No campus is promoted solely to satisfy a count.

### Batch 3 — Import exceptions and source evidence

The baseline contains 24 import rows: 11 promoted, 6 candidates, and 7 rejected. It reports zero captured evidence records and zero source snapshots.

Tasks:
- Review the six candidate rows and their validation details individually.
- Review the seven rejected rows and preserve the original rejection reason.
- Inspect original source rows only through the authorized review workflow.
- Confirm identity resolution for every institution row: approved new record, approved match, or unresolved.
- For each factual claim, capture source evidence and the date it was checked.
- Do not bulk-approve candidates, retry all rejected rows, or infer that rejection indicates a system defect.

Exit gate:
- Every reviewed row has a documented decision and reason.
- Unresolved rows remain candidates/rejected; no silent overwrite or identity merge.

### Batch 4 — Programs, offerings, and admissions

The baseline contains one active Program, zero Program Offerings, and zero Admission Intakes.

Tasks in dependency order:
1. Verify reusable Program identity and classification.
2. Verify that an institution or campus actually offers the program.
3. Create a proposal for the Program Offering separately from the Program.
4. Capture official source evidence and relevant academic year/session.
5. Add an admission intake only when a specific cycle and dates are supported by a current source.
6. Mark expired or unverified information accordingly; do not infer current admissions from generic promotional pages.

Exit gate:
- Every proposed program and offering has evidence, ownership/scope, and applicable dates.
- Admission information without a current source remains unpublished/unverified.

### Batch 5 — Review, execution approval, and rollback

Before any production mutation, assemble a private change package containing:
- Fresh before-report and record-level audit.
- Exact affected IDs and fields with current and proposed values.
- Evidence references and reasons for each change.
- Known dependencies and integrity implications.
- Verified backup identifier/time and restore procedure.
- Exact commands/actions to be executed and a named responsible operator.
- Rollback procedure and clear stop conditions.
- Explicit human approval for the specific batch.

No approval of this document alone authorizes production changes.

### Batch 6 — Post-change verification

After a separately approved batch:
- Rerun aggregate report, record-level audit, and geography integrity checks.
- Compare before/after record counts and identities, not just total counts.
- Confirm no unintended deactivation/reactivation, duplicate identity, orphan relationship, or unexpected update.
- Confirm public pages remain healthy.
- Record actual changes and exceptions in private operational notes.
- Stop further batches if unexpected differences appear.

## 3. Prioritization

1. Environment identity, backup/restore readiness, and fresh baseline.
2. Geography identity/hierarchy and safe seed behavior.
3. Verified campus and location proposals for existing institutions.
4. Import candidates/rejections and provenance.
5. Program identities, offerings, and admission intakes.
6. Controlled execution and post-change verification.

This order is dependency-driven: campus location relationships depend on canonical geography; offerings depend on programs and institution/campus identity; admissions depend on verified offerings and cycle-specific evidence.

## 4. Completion criteria

The remediation program is complete only when:
- Each proposed factual change is source-supported and reviewed.
- The production revision and database target are known.
- Backups and rollback are verified before writes.
- No unreviewed candidate or rejected row is bulk-promoted.
- Read-only verification confirms relationships and integrity after each approved batch.
- Remaining unknowns are documented rather than guessed.
- Private report and evidence handling rules are followed.

## 5. Current blockers

1. The successful retrieval workflow stores the report privately and intentionally suppresses its contents from logs; the actual private report still needs to be reviewed through authorized private access.
2. The exact Hostinger production revision has not been independently verified.
3. No verified backup/restore evidence is recorded in this repository.
4. No individual campus, geography, or import-row change has yet been approved for production.

Until these blockers are cleared, work remains read-only and planning-only.
