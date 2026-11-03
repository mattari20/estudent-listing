# Institutes Directory — Phase 1C Operational Readiness and Safe Execution Plan

Date: 2026-11-03
Repository: \`mattari20/estudent-listing\`
Planning baseline: \`5fb31b3d5873ab114c3354bfc012a8a90b687b6c\`
Status: PLAN ONLY — no production action authorized

## 1. Objective

Phase 1C converts the explicit operational blockers carried from Phase 1B into verified evidence and safely tested implementation. Its objective is to establish whether the Institutes Directory can run its existing governed discovery pipeline without overlapping execution, unsafe retries, unknown schema state or unverified recovery capability.

Phase 1C is not permission to launch nationwide discovery, enable the 20 additional HEC profiles, enable AI extraction, import GCU records, approve candidates, promote canonical data or publish new directory entries.

**Phase 1B remains closed and frozen.** Do not reopen its scope. Phase 1C work must use new PRs and independently verified acceptance criteria.

## 2. Non-negotiable invariants

- Preserve GCU candidate rows #348–353 and batches #19–23; do not delete, overwrite, renumber, merge, approve, reject, import or promote them.
- Keep the additional 20 HEC identity profiles disabled.
- Keep AI extraction disabled.
- Do not create dummy production data.
- No production database connection, SSH, hPanel, Artisan, migration, cron/worker change, backup/restore, deployment, source retrieval or candidate staging without separate explicit operation-specific authorization.
- A green GitHub check proves the tested commit in CI only; it does not prove production schema, deployment, provider configuration or live scheduler state.
- A missing provider/production fact remains UNKNOWN or BLOCKED; never infer success from absence of evidence.
- Every PR must state exact base/head SHA, files, tests, non-actions, rollout/rollback impact and remaining blockers.

## 3. Phase 1C workstreams and expected PR sequence

PR numbers below are planning labels only; GitHub assigns actual numbers. Keep the work grouped into the smallest safe reviewable set. Do not create one PR per checklist item.

### Step 1 — Re-establish the operational evidence and authorization boundary

**Expected PR: #986 — Phase 1C readiness evidence and authorization register**

Scope:
1. Verify current \`main\`, required checks, workflow states and the exact deployed application revision using already authorized evidence routes.
2. Resolve the Hostinger/provider scheduler question: whether \`schedule:run\` is invoked, by which cron/account, and whether a persistent worker/supervisor exists. If the provider cannot expose system scope, record the exact unknown boundary.
3. Obtain current evidence for AUTH-04: required tables, columns, indexes and migration-ledger entries. Use only a separately authorized, narrowly scoped read-only method. If AUTH-04 remains bypassed, stop and retain NOT VERIFIED.
4. Obtain backup retention and recoverability evidence. A visible backup or restore point is not a successful recovery test. Any restore/recovery test needs a separate plan and explicit authorization.
5. Refresh the workflow and application revision evidence after any deployment-dependent change.
6. Record an evidence matrix with timestamp, source, exact revision/run ID, status, limitations and authorizing scope.

Acceptance:
- Every operational gate is VERIFIED, UNKNOWN or BLOCKED with an evidence reference.
- No unverified fact is marked PASS.
- Provider/Hostinger scheduler and worker scope is resolved or explicitly bounded as unknown.
- AUTH-04 is verified read-only or remains a hard stop.
- Backup retention/recoverability is evidenced or remains a hard stop.
- No production write, migration, schedule change, restore or deployment occurs in this step.

### Step 2 — Implement shared execution lease and command idempotency

**Expected PR: #987 — shared lease, run ledger and idempotency controls**

Dependency: Step 1 must establish a viable supported persistence backend for testing. Production schema readiness and rollout remain separate gates.

Scope:
1. Add an atomic, durable execution lease with a stable task key, owner token, run/correlation ID, acquired-at timestamp, expiry and heartbeat/renewal policy.
2. Ensure only the lease owner can release a lease; stale owners cannot release a successor's lease.
3. Fail closed if the lock store is unavailable or ownership is ambiguous.
4. Record an append-only run ledger with runner, task, application revision, start/end, outcome, lock owner and sanitized failure code.
5. Apply stable idempotency keys to source registration, monitoring policy updates, source snapshots/observations, import batch creation, candidate rows and discovery outcome recording where applicable.
6. Define retry behavior so the same run key resumes/reuses prior results instead of creating duplicate side effects.
7. Keep manual Artisan invocation behind the same lease; no bypass path.
8. Add isolated MySQL integration tests and concurrency tests. SQLite-only tests are not sufficient to establish row-lock/atomic-claim behavior.

Required tests:
- Two concurrent bootstrap runs produce one owner and no duplicate side effects.
- Two concurrent monitoring runs produce one owner for the same task/scope.
- GitHub/manual/Laravel-triggered runs contend on the same durable lease.
- Lock-store outage fails closed before retrieval or candidate writes.
- Expired lease recovers after simulated process death.
- Stale owner release is rejected.
- Same idempotency key returns/reuses the same run/batch; a new key creates a new immutable run.
- Partial failure at each side-effect boundary is recoverable and auditable.
- Run-ledger failure cannot be reported as a successful run.

Safety:
- Migration and code are tested in isolated CI/MySQL only.
- No production migration or deployment is part of this PR.
- If no supported shared backend is verified, do not implement a fake in-process lock or claim cross-runner safety.

### Step 3 — Resolve scheduler ownership and remove duplicate scheduled execution

**Expected PR: #988 — single-owner scheduler convergence**

Dependency: Steps 1 and 2 accepted; provider evidence identifies actual invocation paths.

Scope:
1. Make an explicit owner decision for the hourly bootstrap and monitoring commands.
2. Compare the existing GitHub Actions hourly workflow with Laravel's hourly schedule definitions and the verified Hostinger/provider invocation.
3. Retain one authoritative scheduled owner. The current design recommendation is GitHub Actions, subject to provider evidence and owner approval.
4. Ensure workflow retries, manual dispatch and any retained Laravel/manual invocation use the same shared lease.
5. Remove or disable only the proven duplicate scheduled path; do not change unrelated schedules or workers.
6. Add schedule-definition tests and a workflow contract test proving the chosen owner and safe no-op behavior.
7. Document rollback and the exact operational command/configuration required for any provider-side change.

Acceptance:
- One authoritative scheduled path is evidenced.
- Cross-runner concurrency tests from PR #987 pass.
- A duplicate invocation safely exits as locked/no-op without source retrieval or candidate writes.
- The approved runner resolves the intended application revision and preserves the existing no-AI/no-approval/no-promotion/no-publication guards.
- No production scheduler/cron/worker configuration is changed until the owner separately authorizes the exact change.

### Step 4 — Reconcile protected GCU historical records without mutation

**Expected PR: #989 — GCU evidence export and deterministic dry-run reconciliation**

Dependency: Separate authorization for a read-only export and an approved private report destination.

Scope:
1. Obtain the exact historical mapping for rows #348–353 and batches #19–23; do not infer row-to-batch mapping from ID ranges.
2. Reconcile row/batch IDs with source IDs, snapshots, evidence, provenance, payload, validation state, duplicates, parent/canonical references and historical review events.
3. Classify missing evidence, conflicting evidence, campus/parent ambiguity and confirmed duplicates separately.
4. Generate a deterministic dry-run report with proposed dispositions and field-level evidence references.
5. Store sensitive evidence only in the approved private location; publish only a sanitized summary if explicitly approved.

Acceptance:
- Every target row is accounted for or explicitly marked unavailable.
- Missing evidence is never treated as rejection.
- Ambiguous identities are not auto-merged.
- Repeated dry-runs with identical inputs are deterministic.
- \`canonical_mutation_performed=false\`, \`approval_performed=false\` and \`import_performed=false\`.
- No source row, batch, canonical record, approval or public listing is changed.

### Step 5 — Final readiness gate and controlled-pilot authorization package

**Expected PR: #990 — Phase 1C readiness closeout and pilot decision**

Scope:
1. Reconcile exact merge commits and exact-head checks for PRs #986–989.
2. Confirm deployed application-code parity if a deployment has separately been authorized and completed.
3. Confirm schema/migration readiness, scheduler ownership, shared lease behavior, backup/recovery evidence, GCU dry-run and current source qualification.
4. Produce a go/no-go matrix for one narrowly scoped, already-qualified pilot source.
5. Record the exact proposed command, profile, expected side effects, stop conditions, rollback/recovery path and named approval boundary.

Acceptance:
- Every prerequisite is evidenced and all blocking gates are closed.
- The pilot remains disabled until a separate explicit execution authorization is given.
- No nationwide expansion, additional HEC activation, AI extraction, automatic approval, canonical promotion or publication is bundled into the readiness PR.

## 4. Dependency order and parallel work

\`#986 → #987 → #988\` is the critical operational-safety path.

The GCU dry-run work in \`#989\` may be prepared in parallel after its separate read-only export authorization and private destination are confirmed. It must not block or mutate scheduler implementation.

\`#990\` closes Phase 1C only after the required evidence and tests are available. Do not merge a later PR merely because earlier PRs are merged.

## 5. Phase 1C completion criteria

Phase 1C is complete only when:
- production revision/runtime and AUTH-04 schema evidence are current or explicitly accepted as bounded limitations for the narrowly scoped next action;
- Hostinger/provider scheduler and worker scope is resolved;
- one authoritative scheduler is selected and duplicate execution is prevented;
- shared lease, run ledger and idempotency pass isolated MySQL and cross-runner tests;
- backup retention and recoverability are evidenced for any proposed production change;
- GCU historical reconciliation is deterministic, read-only and evidence-backed;
- exact-head required checks pass for all merged PRs;
- a separate pilot go/no-go decision is recorded without implying authorization to execute it.

If any prerequisite cannot be evidenced, Phase 1C remains BLOCKED at that gate. Do not bypass the blocker to preserve speed.

## 6. Explicitly out of scope

- Nationwide source discovery or activation of all configured sources.
- Activation of the 20 additional HEC profiles.
- AI extraction or paid AI calls.
- Automatic approval, canonical promotion or publication.
- Bulk GCU import or cleanup.
- Production migrations, deployments, cron/worker changes, backup restores or source retrieval without separate authorization.

These are later, separately approved capabilities after the controlled pilot has passed.
