# Phase 1C — Five-Package Closure Execution Plan

**Prepared:** 2026-11-06  
**Repository:** `mattari20/estudent-listing`  
**Baseline main:** `e1d253c8d363226d8efc6e3f7ee5774b3adfa0d9`  
**Latest evidence carrier:** PR #1047, merged into `main` (screenshot-confirmed; two checks passed).  
**Purpose:** convert the remaining Phase 1C blockers into five bounded, reviewable delivery packages without duplicate PRs or unsafe production operations.

## Executive decision

PR #1047 is merged. It records the source-level review of all 18 migrations and the latest Test B read-only evidence. That work is now part of main.

**Test B is not accepted. Phase 1C is not closed. Phase 2 remains on hold.**

The five packages below are a delivery sequence, not a claim that five PRs already exist. Each package should become one PR only when its implementation and tests are ready. GitHub assigns the actual PR numbers. Existing unrelated or superseded open PRs must not be repurposed as these packages.

## Package 1 — Test B definition-level schema and integrity preflight

**Objective:** finish the Test B source-to-production comparison without changing production.

Current consolidated preflight already checks the 18 migration ledger entries, required table/column/index presence, gated duplicate and backfill counts, 18 orphan relationships, 24 expected audit trigger names, and 23 named foreign keys. Its current weakness is that object-name presence is not full definition parity.

Deliver:
- Exact column metadata comparison: data type, length/precision, nullability, default, extra/auto-increment and ordinal position for every object introduced or altered by the 18 migrations.
- Exact index comparison: ordered columns, uniqueness, index type and prefix lengths where applicable.
- Exact foreign-key comparison: child and referenced columns, referenced table, update rule, delete rule and constraint name.
- Exact trigger comparison: schema/table, timing, event, action statement and normalized body hash; distinguish missing, equivalent, conflicting and uninspectable definitions.
- Explicit prerequisite states: PASS, FAIL, NOT_CHECKED_PREREQUISITE_MISSING, UNKNOWN and ERROR. Missing prerequisites never count as passes.
- Aggregate-only data checks; do not emit application row payloads or sensitive values.
- Isolated MySQL fixtures covering matching definitions, mismatches, absent prerequisites, duplicate/orphan gates and safe rollback of test fixtures.
- A protected workflow-dispatch contract retaining the current exact-SHA, operation-reason, confirmation-phrase and read-only environment controls.

Acceptance:
- All 18 migrations have an explicit source-to-schema comparison.
- All checks are evaluated or explicitly NOT_CHECKED with a named prerequisite.
- No production DDL/DML, migration, application-file write or persistent report overwrite occurs.
- Exact-head Test, Laravel CI, Pint and isolated MySQL checks pass.

**Production evidence currently known:** prior read-only runs reported 18 pending ledger entries, 11 missing tables in the 39-table inventory, 0/24 expected trigger names and 0/23 expected named foreign keys in the then-current database observation. Seven orphan checks returned zero; eleven were skipped. These counts must be refreshed by an authorized current-main run before acceptance.

## Package 2 — Public directory and browser/API verification

**Objective:** establish current public behavior without inserting test or dummy records.

Deliver:
- Run current-main public site monitor and browser runtime diagnostics using their existing protected workflow-dispatch paths.
- Verify directory page, search/filter controls, reset/clear behavior, request/response status, JSON content type and browser console/network failures.
- Verify empty-state behavior against the intentionally empty production directory. Empty results are not automatically a failure.
- Verify detail-page behavior as far as the empty dataset permits; do not fabricate a detail record.
- Record exact main SHA, workflow run IDs, timestamps, URLs, status codes and bounded redacted evidence.
- Keep browser observations separate from production schema conclusions.

Acceptance:
- Current public page and API results are evidenced.
- Browser console/network output is classified.
- Empty production data remains unchanged.
- Any behavior that cannot be exercised because there are no published records is marked NOT TESTABLE WITH EMPTY DATA, not passed.

## Package 3 — Hostinger operational, revision, schema and recovery evidence

**Objective:** close the operational evidence gates that code/CI cannot prove.

Deliver:
- Extend the existing read-only Hostinger diagnostic to include Package 1's exact schema/trigger/FK checks and the bounded seven-column legacy NULL identity inventory.
- Resolve the historical report collision without reading, deleting or overwriting the existing report. Use a unique, approved, non-overwriting destination or stdout-only bounded output.
- Verify deployed application-code revision parity against the exact application-code SHA on main.
- Inspect provider cron/scheduler invocation and persistent worker/supervisor scope; if hPanel does not expose a fact, record the limitation as UNKNOWN.
- Obtain dated backup inventory, retention evidence and a separately controlled restore/recovery proof. A backup listing is not a restore test.
- Keep the production diagnostic read-only. No migration, DDL, DML, deployment, cron/worker change or restore is part of this package.

Acceptance:
- Current deployed revision is verified or explicitly blocked.
- Schema/trigger/FK results are current and exact.
- Legacy NULL counts are aggregate-only and no key is assigned or backfilled.
- Scheduler/worker and backup/recovery evidence is referenced.
- No production mutation occurs.

## Package 4 — Protected GCU historical evidence reconciliation

**Objective:** prepare deterministic, read-only reconciliation of the exact historical records without touching them.

Protected scope: candidate rows #348–353 and import batches #19–23.

Deliver:
- Build/validate a deterministic reconciliation harness using synthetic fixtures in isolated tests only.
- Require an approved private destination before any real export.
- Reconcile exact row-to-batch mapping from evidence, never infer it from adjacent IDs.
- Compare source, snapshot, evidence, provenance, payload, validation state, duplicate indicators, parent/canonical references and historical review events.
- Classify missing evidence, conflicting evidence, campus/parent ambiguity and confirmed duplicates independently.
- Emit field-level evidence references and a stable digest; redact sensitive payloads from public summaries.

Acceptance:
- Every target record is accounted for or explicitly unavailable.
- Identical inputs produce identical output.
- Missing evidence is not treated as rejection.
- Ambiguous identities are not auto-merged.
- `canonical_mutation_performed=false`, `approval_performed=false`, `import_performed=false`.
- No protected row or batch is changed.

## Package 5 — Final Phase 1C acceptance and controlled-pilot decision

**Objective:** close the phase only from integrated, exact-revision evidence.

Deliver:
- Reconcile merge commits and exact-head checks for Packages 1–4.
- Run unified current-main Test, Laravel CI, Pint, Project Controller, frontend build and isolated MySQL regression where applicable.
- Include the current public verification, production read-only schema/runtime evidence, backup/recovery evidence and GCU dry-run disposition.
- Publish a single go/no-go matrix with PASS, FAIL, BLOCKED, UNKNOWN and NOT APPLICABLE states.
- Record a narrowly scoped pilot proposal, exact command/profile, expected side effects, stop conditions, recovery path and separate approval boundary. The pilot stays disabled.

Acceptance:
- Every Phase 1C requirement is evidenced or explicitly blocked with owner/action.
- No unsupported claim of production readiness.
- No Phase 2 transition while blocking gates remain.
- No live discovery, AI extraction, additional HEC activation, automatic approval, import, canonical promotion or publication is bundled into closure.

## Dependency and execution order

1. Package 1 — exact Test B schema/data-integrity diagnostic.
2. Package 2 — public page/API/browser evidence (can proceed in parallel with Package 1 once current-main dispatch is available).
3. Package 3 — Hostinger revision/runtime/scheduler/backup evidence, after the diagnostic is ready.
4. Package 4 — GCU reconciliation harness and, only after approval/private destination, exact read-only export.
5. Package 5 — integrated final acceptance and pilot decision.

A package may be prepared while another is blocked, but its acceptance must not be marked complete until its own evidence exists.

## Existing PR hygiene

- PR #1047 is merged and must not be recreated.
- PR #1016 is a previously identified superseded production-workflow patch; do not merge it as a shortcut.
- Older unrelated open PRs are not Phase 1C completion packages. Review individually; do not mass-merge or close them to improve counts.
- Preserve branch protection and all current production workflow dispatch controls.
- Create no duplicate PR for work already merged into main.

## Non-negotiable safety invariants

- No production migrations, DDL, DML, schema repair or dummy data.
- No deployment, SSH/hPanel, scheduler/worker change or backup restore without a distinct operation-specific plan and authorization.
- No GCU row/batch edits, deletion, renumbering, merge, approval, rejection, import or promotion.
- AI extraction remains disabled; the additional 20 HEC identity profiles remain disabled.
- No sensitive application row payloads in logs, public artifacts or this report.
- A workflow execution PASS means the diagnostic ran; it does not mean production acceptance passed.

## Current status at this plan's baseline

| Area | Status |
|---|---|
| PR #1047 source-level review | MERGED |
| Test B all-18 source review | COMPLETE |
| Test B exact production schema parity | BLOCKED / needs current definition-level evidence |
| Test B data-integrity checks | PARTIAL; skipped checks remain NOT_CHECKED |
| Public directory/browser current evidence | PENDING |
| Hostinger revision/runtime/schema evidence | PENDING |
| Scheduler/worker and backup/recovery | NOT VERIFIED |
| GCU historical reconciliation | PENDING; protected records untouched |
| Phase 1C formal acceptance | OPEN |
| Phase 2 | ON HOLD |
