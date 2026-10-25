# Programme-offering gap verification runbook

## Purpose and safety

Verify the 36 programme records listed in `step3-offering-gap-evidence-register.csv` against current official institution and campus sources, then determine whether an active offering is already present in the live database. This is a read-only evidence and planning workflow. It does not authorize inserts, updates, promotions, deletes, migrations, or publication.

## Current known baseline (2026-10-24)

The live integrity audit reported 36 active programmes without active offerings and `structurally_ready=false`. The repository register contains 36 programme rows. The register is an evidence worklist, not proof that each relationship is correct or that the live database has not changed since the last audit.

Known high-priority evidence gaps in the register:
- Programme IDs 71 (BS Accounting & Finance) and 74 (Bachelor of Design) have no official programme source URL.
- Campus source URLs are blank for programme IDs 69, 71, 74, 7, and 55.
- Programme ID 71 has an unresolved institution/campus mapping.
- Programme ID 74 requires confirmation of the exact current award name.
- IDs 69, 7, and 55 need stronger current evidence for the programme's campus or delivery model.
- Records marked `blocked` or `review` must not be treated as approved repairs.

These are worklist observations, not newly verified live facts. Recheck against current official pages and a fresh live preflight.

## Per-programme verification sequence

1. Preserve the register's `program_id`, `institution_id`, and programme name exactly as the database identifiers under review.
2. Open the official programme source and capture the exact title, qualification/award, current availability or admissions status, and source retrieval timestamp.
3. Open an official campus source that explicitly supports the campus relationship. A general contact page is insufficient when it does not connect the programme to that campus.
4. For online/virtual programmes, determine whether the offering is campus-bound, online, or has a named support/study centre. Do not infer that a physical campus is the delivery location.
5. Run the read-only offering-gap preflight against the intended deployed revision. Record whether an offering already exists, including inactive offerings and possible duplicates.
6. Compare institution and campus IDs to the official evidence. Names/slugs alone are not enough to resolve an identity or parent relationship.
7. Record a proposed outcome in the review worksheet: `evidence_sufficient_manual_review`, `missing_programme_source`, `missing_campus_source`, `campus_relationship_unproven`, `programme_name_needs_confirmation`, `existing_offering_check_needed`, or `blocked_identity_mismatch`.
8. Cite source URLs and concise excerpts; note retrieval date and reviewer. If the official page does not support a field, leave it unresolved rather than filling it by inference.
9. Review duplicate risk and parent/campus identity before drafting any repair plan.
10. Keep all findings read-only. A proposed offering is not permission to create it.

## Cross-register acceptance checks

- Exactly 36 expected programme IDs are accounted for; report missing IDs, repeated IDs, and unexpected IDs.
- Every row has an explicit review outcome; no blank or implicit approval.
- Every row proposed for manual review has official programme and campus/delivery evidence or a clearly documented exception.
- Existing active/inactive offerings and possible duplicates are checked from the fresh live report.
- The sum of row outcomes equals 36.
- A proposed repair plan contains exact programme, institution, campus, delivery mode, source URLs, duplicate checks, and rollback/reversal notes.
- Report the application revision, audit timestamp, preflight run ID, exit status, and explicit zero-mutations statement.

## Automation

Use issue #626's guarded read-only offering-gap preflight workflow when available. Treat `READY_FOR_MANUAL_REVIEW_ONLY` as a preflight result, not approval to write. If the workflow or deployed command is unavailable, report the blocker and do not substitute guessed database state.

## Completion gate

Do not mark the 36-gap task resolved until all 36 records have current source-backed dispositions and a fresh live audit shows no unexplained gaps. Any canonical repair requires a separate reviewed, row-level plan and explicit owner authorization.
