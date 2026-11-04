# Phase 1C-19 — Institution Identity Conflict and Resolution Replay Recovery

Date: 2026-11-04  
Repository: `mattari20/estudent-listing`  
Base `main`: `86ded5d52cbc6a329ae4559919e7da5111876b20`  
Previous delivery: PR #1003 — Phase 1C-18, merged.

## Objective

Make institution identity matching, conflict detection, operator conflict resolution and identity-decision recording safe under replay and concurrent attempts. Preserve all historical rows and decision evidence. Do not approve, promote, merge or publish canonical institutions as part of this work.

## Baseline and prior evidence

- Phase 1C-18 PR #1003 is merged at `86ded5d52cbc6a329ae4559919e7da5111876b20`; the verified `main` ref matched this merge commit at Phase 1C-19 start.
- Phase 1C-18 final PR head: `efc0b941c05030f30898116ea42793ce90c9a659`.
- Required Phase 1C-18 workflows completed successfully on the pull-request merge candidate `9938a985d210ad15ed9fb28fd6a8ef7deb2748b5`, whose commit message records the exact PR head merged into the verified base:
  - Test #41763539460 — 859 tests, 3,791 assertions.
  - Laravel CI #41763539432 — 859 tests, 3,791 assertions; Pint and formatting verification passed.
  - Project Controller #41763539440 — workflow job passed; internal read-only inventory reported 2/5 tasks and marked Hostinger diagnostics and open-PR review gates blocked. This is not a production-readiness certification.
  - Isolated MySQL #41763539457 — package feature tests 17 tests/57 assertions; independent-process package writer/cleanup proof passed.
- Actual job logs were inspected. The package concurrency proof showed two writers converging on one package pair while cleanup used the same package lock; candidate records were unchanged.

## Existing architecture and failure boundaries

### Identity-match candidates

- `InstituteIdentityResolver::resolve()` used Eloquent `updateOrCreate` keyed by `import_row_id` and `institution_id`, but the inspected model/schema did not enforce a deterministic database identity key for this relationship.
- Concurrent resolver calls could race between lookup and insert.
- The resolver updated the import row's `match_state` outside an explicit row-locking transaction when called through the operator route.

### Conflict detection

- `ImportConflictDetectionService` checked for an open conflict and then inserted a row. There was no database-enforced identity for a logical conflict, so concurrent attempts could both pass the existence check.
- Existing conflict rows carry status and resolution fields, but resolution had no separate append-only event history.
- A repeated resolver call could reset `match_state` to `matched`/ `new_candidate` before detecting an already-open conflict. The prior conflict record could be reused without restoring the row's blocking `conflict` state.

### Conflict resolution and identity decisions

- The prior controller updated conflict status and resolution columns directly without locking the import row and conflict together.
- A repeated request could overwrite an existing resolution reason/actor/time.
- Identity resolution decisions were appended, but the request had no deterministic idempotency identity; repeated identical submissions could create duplicate decision and audit records.
- Canonical promotion already checks for open conflicts and requires explicit approved identity decisions. Those gates remain in place.

## Recovery design implemented in this branch

1. Add nullable SHA-256 identity keys with unique database constraints to `identity_match_candidates`, `import_conflicts` and `identity_resolution_decisions`. Historical rows retain NULL keys; no backfill, merge or deletion is performed.
2. Serialize resolver work by locking the import row in a database transaction. Candidate matching continues to use the existing canonical name, official name, short name, slug and alias rules.
3. Reuse legacy candidate rows by their existing row/institution pair and assign the deterministic identity key to the row selected for update. New rows are protected by the unique key.
4. Give logical conflicts a deterministic identity key based on the import row, conflict type, related subject and normalized details. Reuse legacy open conflicts without rewriting them.
5. Add `import_conflict_events` as an append-only event ledger with a unique idempotency key. Detection and resolution events are written in the same transaction as their corresponding conflict state changes.
6. Resolve conflicts by locking the import row and matching conflict. An exact retry by the same actor with the same reason returns the existing resolution. A different attempt against a resolved conflict is rejected; the original actor, reason and timestamp are not overwritten.
7. Restore `match_state=conflict` whenever replay encounters an existing open conflict. Once the last open conflict is resolved, return the row to the pre-existing review state rule; conflict resolution itself does not approve identity.
8. Add a deterministic decision request key based on row, decision, target institution, actor and reason. The decision service locks the row, persists the decision and audit event atomically, and reuses the same record for an identical retry. A later decision with a different reason remains a distinct append-only decision.

## Safety contract

- Conflict resolution is not identity approval.
- Identity approval is not canonical promotion.
- Canonical promotion is not publication.
- No new path automatically chooses a canonical institution or merges candidate rows.
- Historical conflict, identity-match, candidate, decision and audit records are not deleted or merged.
- The migration adds nullable keys and a new event table only; it does not rewrite historical records.
- GCU rows #348–353 and batches #19–23 remain protected. AI extraction remains disabled. The additional 20 HEC profiles remain disabled.
- No production database, SSH, Hostinger/hPanel, cron, queue worker, deployment, source retrieval, staging, approval, import, promotion or publication operation is part of this phase.

## Regression coverage

The focused feature test covers:
- Repeated identity resolution creates one match candidate and one conflict.
- An open conflict remains blocking after resolver replay.
- Conflict resolution creates immutable detected/resolved events.
- An identical resolution retry reuses the same state and event.
- A different resolution attempt cannot overwrite the existing resolution.
- A failure while inserting the resolution event rolls back the conflict and row state.
- Conflict identity keys are enforced by a database unique constraint.
- Identical identity-decision replay reuses one decision and one audit event; a materially different reason creates a separate history entry.

The isolated-MySQL process proof starts two independent PHP workers against the same import row for both resolver replay and identity-decision replay. It requires one match candidate, one conflict, one detection event, one identity decision and one decision audit event, with the row still blocked in conflict state.

## Remaining limitations and review points

- Existing historical rows keep NULL identity keys; legacy open-conflict matching is retained to avoid creating duplicate active conflicts during transition.
- A resolved conflict remains resolved on replay. New conflict conditions with different related identity/details produce a different key and can be recorded separately; the system does not automatically reopen a human-resolved record.
- The idempotency identity for operator decisions intentionally includes the reason. A changed reason is a new decision event, not an overwrite.
- Identity match candidates and conflict event history remain review metadata; this work does not certify the accuracy of source evidence or canonical identity.
- Project Controller's internal 2/5 inventory remains a separate operational gate. Hostinger/runtime readiness is not inferred from repository CI.
- Final PR #1004 implementation head: b33e95287e377176a0e9c80b9c5eef05214a7157; merged at 54bf5e6b9878080951b521b61a261a233f18322a. Current main was fetched directly and matches the merge commit.
- Exact final-head Test #41769183222 PASS (865 tests / 3,822 assertions); Laravel CI #41769182981 PASS (same total; Pint format and verification passed for 59 files); Project Controller #41769182984 job PASS (internal inventory 2/5, Hostinger diagnostics blocked); isolated MySQL #41769182903 PASS.
- Actual MySQL logs confirm migration 2026_11_04_000012, identity feature tests 6 tests / 31 assertions, event-failure rollback, and independent-process identity proof PASS. Two workers converged on one match candidate, one open conflict, one detection event, one identity decision and one audit event; row remained blocked in conflict state.
- The independent-process proof covers identical decision replay. It does not prove concurrent different actor/reason outcomes; that remains an explicit follow-up coverage gap.
- Project Controller internal inventory is not production readiness. Hostinger read-only diagnostics remain blocked.

## Next Phase 1C task

Phase 1C-20 — Discovery-to-Review Persistence Boundary Audit is documented in docs/audits/PHASE_1C_DISCOVERY_TO_REVIEW_PERSISTENCE_BOUNDARY_AUDIT.md. It records the complete lifecycle findings, including the remaining operator transition atomicity, state-guard and snapshot ordering gaps.

The recommended next implementation is Phase 1C-21 — Operator Transition Atomicity and State-Guard Hardening. Do not treat this audit as permission to start implementation before its documentation PR is reviewed and validated. Canonical promotion/publication and all production runtime operations remain separately gated.
