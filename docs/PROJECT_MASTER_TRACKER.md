# eStudent Institutes Directory — Master Tracker

Last reviewed: 2026-10-27
Repository: https://github.com/mattari20/estudent-listing
Phase 2 structure: exactly five sequential steps. Do not start the next step until the current step's completion gate is satisfied.
Production safety: read-only diagnostics first. No destructive data operation or production migration is authorized by this tracker.

## Operating rules
- Work in reviewable batches and link every change to a PR, CI run, or diagnostic report.
- A step is complete only when its gate is met and the evidence is recorded here.
- If a test fails, remain in that step, fix the cause, and rerun the relevant test.
- Treat imported/test data as unverified until source evidence and identity checks pass.
- Before any live write, inspect schema and migration state, document impact and recovery, and obtain separate explicit authorization.
- Stop on partial/inconsistent migration state; never blindly retry a potentially partial migration.
- Report blockers plainly; never describe a workflow as deployed if it was only a dry run.
- No step may be marked complete solely because its PR merged.

## Phase 2 — Exactly five sequential steps

### Step 1 — Foundation, Access & Automation Readiness
**Target:** Establish an accurate, testable baseline for repository changes, CI, deployment access and safe diagnostic automation.

**Requirements and tasks**
- Review PR #826 (migration error handling) and PR #827 (this tracker); confirm current PR state and relevant CI.
- Keep this tracker aligned to exactly five Phase 2 steps, each with target, requirements, tests, evidence and a completion gate.
- Verify available GitHub workflow triggers, Hostinger SSH workflow configuration, and whether a workflow can be triggered from the available automation tools.
- Identify a read-only database diagnostic path and its safety switches; do not run a migration or write to production.
- Record access/tool limitations rather than assuming an ability to dispatch workflows or inspect secrets.

**Tests and verification**
- Laravel CI and Test workflow results for the current PR heads.
- Review PR diffs for the intended migration safety behavior and absence of production data changes.
- Confirm the tracker has exactly five Phase 2 steps and explicit stop/go gates.
- Verify the available workflow trigger mechanism from actual tool capabilities; document manual-trigger dependency if automated dispatch is unavailable.
- Confirm no production write/migration was executed during this step.

**Completion gate:** PR/code review evidence is current; tracker contains exactly five Phase 2 steps; available access and workflow trigger paths are documented; a safe read-only diagnostic path is identified; all blockers are explicit. If any item cannot be proven, record it as a blocker and keep Step 1 open.

**Step 1 closeout status: BLOCKED / NOT CLOSED (reverified 2026-10-27).** PR #828 was merged to `main` at `32b1d17d5394b4f56f8a1b5df61584c46f47c392`, and scheduled governed discovery run #29 completed successfully: https://github.com/mattari20/estudent-listing/actions/runs/40452755916. The run confirmed deployed and target application-code revisions matched; canonical counts remained unchanged at institutions=0, campuses=0, programs=0, program_offerings=0, admission_intakes=0. The profile `gcu-lahore-programs` was safely blocked because its required parent slug `government-college-university-lahore` is absent; the run reported `NO_DUE_POLICIES_NOOP`, so this proves safe completion/no canonical mutation, not successful discovery of GCU data. Hostinger read-only diagnostics run #626 also succeeded: https://github.com/mattari20/estudent-listing/actions/runs/40452941556. Step 1 remains open because PR #826 and PR #827 remain open, and the Project Controller PR #829 must finish its full regression checks and be reviewed before its implementation can be treated as integrated. No production migration, deletion, or canonical write was performed. Do not create an unverified parent institution.

**Evidence reviewed (2026-10-27):**
- PR #826 is open, mergeable, not merged; reviewed the migration and workflow patches. Laravel CI run #1639 passed: https://github.com/mattari20/estudent-listing/actions/runs/40406254876; Test run #1875 passed: https://github.com/mattari20/estudent-listing/actions/runs/40406254874.
- PR #827 is open, mergeable, not merged; it changes only this tracker. Latest head commit `68fb85f30934e104a057849f878a38d145568c22` passed Laravel CI #1642: https://github.com/mattari20/estudent-listing/actions/runs/40409450790 and Test #1878: https://github.com/mattari20/estudent-listing/actions/runs/40409450807.
- Tracker validation and PR diff inspection confirm exactly five Phase 2 steps, each with target, requirements/tasks, tests/verification and a completion gate.
- The migration workflow exposes a manual `workflow_dispatch` boolean `confirm_migration` defaulting to false; read-only mode is the safe first path. The available GitHub connector has no workflow-dispatch action, so manual initiation is required for a live diagnostic. No production migration or write was run as part of Step 1.
- CI does not prove live database schema correctness or that the migration workflow ran on Hostinger. The previous migration run reported a MySQL index identifier-length error; live schema/migration state must be inspected read-only before any migration retry.
- Governed discovery run #25 failed: https://github.com/mattari20/estudent-listing/actions/runs/40326346342. Actual job logs show deployed revision matched target `69e29d4e17fefe8dbec7d28b805c6086caba5720`; canonical counts before and after were all zero (institutions, campuses, programs, program_offerings, admission_intakes); source monitoring recorded one outcome; bootstrap failed only because the configured GCU parent slug is missing. No canonical count change was observed.
- PR #828 was merged and its post-merge scheduled governed discovery run #29 passed: https://github.com/mattari20/estudent-listing/actions/runs/40452755916. Logs show matching deployed/target revision, the missing-parent profile safely blocked, `canonical_counts_before` equal to `canonical_counts_after` (all five canonical counts zero), and `canonical_mutations=NONE`. Hostinger read-only diagnostics run #626 passed: https://github.com/mattari20/estudent-listing/actions/runs/40452941556.


**Step 1 accelerated recovery / consolidated remediation evidence (2026-10-27; live API rechecked):**
- Latest verified `main` commit at recovery start: `4ccf6e22c9fd6102b7bbccd028c204b53d61fba6`. PR #826 (migration error handling), #829 (read-only controller), #831 (command orchestration), and #832 (status parser/newline regression fix) are merged. PR #827 remains open and non-mergeable; its tracker branch is stale against current main and must not be force-merged or used as the integration base.
- **Orchestrator root cause identified from current workflow YAML:** the gate step redirects stdout to `controller-report/command-state.stdout.json` before creating `controller-report/`. Bash cannot open the redirection target, so the Python gate evaluator never starts. This explains why failed runs `40461291326` and `40462349709` have zero jobs/artifacts exposed by GitHub. A cohesive fix was committed to branch `fix/step1-orchestrator-smoke-tracker` as `29ca239b220a9591412e5870668963686ca077ce`: create the report directory before redirection and enable `set -euo pipefail`. CI and a new workflow run still must verify the fix; no successful end-to-end result is claimed yet.
- **Ruleset verified directly via GitHub REST read:** ruleset ID `25936221`, name `main`, enforcement `active`, target `refs/heads/main`; rules are deletion protection, non-fast-forward protection, and one required status check named exactly `test` (integration ID `15368`). `strict_required_status_checks_policy=false`; no bypass actors. The GitHub App integration cannot read the separate branch-protection endpoint (403), so this is a confirmed ruleset, not proof that no additional legacy protection configuration exists.
- **Public detail-page failure evidence:** smoke run `40460453350` confirmed `https://estudent.pk/institutes/` returns HTTP 200 and `https://estudent.pk/institutes/university-of-the-punjab` returns HTTP 404 after retries. The route exists in `routes/web.php` as `/{institution:slug}`; previous successful read-only diagnostics recorded all canonical counts (institutions, campuses, programs, offerings, admissions) as zero. Therefore the leading evidence-backed explanation is that the smoke test assumes a Punjab University canonical record that is not present, rather than proof that the route handler itself is broken. Do not seed or fabricate canonical records. Fix the smoke test to resolve a real verified detail slug from the public listing/official-source evidence, or report a clear missing-data blocker when there is no record; verify actual detail navigation once a legitimate record is available.
- **Remaining Step 1 acceptance checklist:**
  1. [x] Recover current main commit, authoritative protocol/tracker, and PR states.
  2. [x] Verify merged migration/controller/orchestrator/parser fixes and regression coverage.
  3. [x] Verify active main ruleset and exact required context `test`; record strict=false and missing branch-protection read permission.
  4. [ ] Run controller unit tests, offline governance smoke test, Laravel CI and Test against the consolidated remediation PR head; repair/retest any failures (bounded to three cycles per failure).
  5. [ ] Observe the repaired command-orchestrator workflow running successfully; verify gate output, issue-comment state persistence, artifact upload and fail-closed behavior, without moving to Step 2.
  6. [ ] Repair public smoke check so it tests a real, verified institution detail route or explicitly reports missing canonical data; do not invent/seed records. Re-run listing + detail HTTP smoke checks.
  7. [ ] Reconcile PR #827: transfer its valid tracker changes onto the consolidated branch based on current main, verify exactly five Phase 2 steps and all gates, then close the stale conflicted PR only after replacement PR exists and contains its intended content.
  8. [ ] Confirm current-main controller, Laravel CI, Test, public smoke, and Hostinger read-only diagnostic outcomes; distinguish CI from live-host verification.
  9. [ ] Update this tracker and persistent resume comment with exact commit/run IDs; keep Step 1 blocked until all applicable criteria are verified.
- No production database writes, canonical-data changes, deletion, migration, or deployment performed. **Step 1 remains BLOCKED / NOT CLOSED; Step 2 must not begin.**

### Step 2 — Deployment, Database Schema & Migration Integrity
**Target:** Prove which revision is deployed and document the live schema/migration state without risking data.

**Requirements and tasks**
- Verify the deployed application revision against the intended approved revision.
- Capture read-only tables, columns, indexes, and relevant migration-history state.
- Investigate any partial DDL effects from the earlier failed migration.
- Prepare a remediation plan if schema and migration history disagree.

**Tests and verification**
- Revision comparison; read-only schema report; migration-history inspection; workflow error/exit behavior review.
- Confirm the diagnostic path stops on inconsistent/partial state and does not silently retry.

**Completion gate:** Deployed revision and schema state are evidenced; migration state is consistent or every discrepancy has a documented, reviewed remediation plan. No blind migration retry.

### Step 3 — Data Integrity, Verification & Duplicate Audit
**Target:** Produce a fresh and reproducible data-quality baseline for institutions, campuses, programs, offerings and geography.

**Requirements and tasks**
- Capture timestamped baseline counts and relationship checks.
- Audit live records row-by-row with IDs, official-source evidence, duplicate candidates, dependent records and proposed action.
- Identify missing, unverified, inconsistent and duplicate records; distinguish confirmed facts from hypotheses.
- Prepare correction/replacement/deletion proposals; do not delete records during the audit.

**Tests and verification**
- Repeatable duplicate, orphan, relationship, geography and identity checks.
- Reconcile report totals with database query results.
- Ensure unverified candidates are not auto-approved.

**Completion gate:** Every audited record has a clear verified/unverified/duplicate/exception status and supporting evidence; proposed actions include dependency impact. Any deletion requires a separate reviewed plan and explicit authorization.

### Step 4 — Import, Approval, Promotion & Recovery Testing
**Target:** Demonstrate safe import and lifecycle handling for valid, invalid, duplicate and failed records.

**Requirements and tasks**
- Verify CSV/header/BOM/duplicate validation, batch processing, approval/rejection, promotion and audit trail.
- Exercise failure reporting and rollback/recovery on test data or a safe non-production path.
- Document how to recover without damaging canonical records.

**Tests and verification**
- Valid and malformed CSV; duplicate and incomplete records; batch failure; approval/rejection; promotion; rollback/recovery; before/after state.
- Confirm failure responses are actionable and do not report false success.

**Completion gate:** Critical lifecycle and failure-path tests pass with evidence; remaining exceptions have documented impact and remediation. No unapproved production mutation.

### Step 5 — Live Regression, Automated Reporting & Phase 2 Closure
**Target:** Verify user-facing behavior, repeatable monitoring and the final evidence-based handover.

**Requirements and tasks**
- Verify listing, search, pagination and real detail URLs against the live site.
- Configure/verify scheduled or repeatable read-only checks only where a supported trigger exists.
- Produce a fresh final report linking evidence for all prior step gates.
- Assign or explicitly accept any remaining exceptions before closure.

**Tests and verification**
- Live HTTP/route checks, search and pagination regression, valid detail-page navigation.
- Practical scheduled-run verification where configured; failure reporting and timestamped diagnostic output.
- Recheck all five step gates and confirm no dry-run is misrepresented as deployment.

**Completion gate:** All critical gates pass, or remaining exceptions are explicitly documented with owner/decision and accepted status. Phase 2 closes only with a final report and handover.

## Long-term roadmap beyond Phase 2
These are broader future workstreams, not additional Phase 2 steps:
1. Verified master education dataset and coverage.
2. Zero-additional-cost discovery agent producing review packages, not automatic promotion.
3. Official-source refresh and change history.
4. Geography, coordinates and nearby search/maps.
5. Admissions, eligibility, deadlines, fees, scholarships and recognition.
6. Ratings, reviews and community with moderation; keep user ratings distinct from official rankings.
7. Advanced search and recommendations based on verified data.
8. SEO, performance and growth.
9. Security, administration, monitoring and recovery.
10. Long-term operations and handover.

## Required closeout report for every step
1. Step number and target.
2. Requirements/tasks completed and deliberately not performed.
3. Tests run, outcomes, and evidence links.
4. Verification results (separate CI, live deployment, schema, and data evidence).
5. Blockers, risk and remaining work.
6. Explicit completion-gate decision: PASS or NOT YET.
7. Do not begin the next step unless the current gate is PASS.

**Step 1 current-head verification (2026-10-27; latest CI-evaluated code commit `f1942d0273ad64c1edbcee21a9544f62b9e11a24`; tracker documentation commit `b9cccab79b99d0dc634966143ffbf5d996e4657c`): NOT YET — live read-only diagnostic has not been run.**
- PR #826 remains open, mergeable, not merged; current head `576c0b237e003df8cd346dabd49b3c5d206a246d`. Laravel CI #1639 and Test #1875 completed successfully: [Laravel CI #1639](https://github.com/mattari20/estudent-listing/actions/runs/40406254876), [Test #1875](https://github.com/mattari20/estudent-listing/actions/runs/40406254874). The PR diff was inspected. The controlled migration workflow has `confirm_migration=false` by default, code-parity checks, an explicit inconsistent-schema block, and `script_stop: true`; no production migration was executed.
- PR #827 remains open, mergeable, not merged. The CI-evaluated commit before this tracker-only update was `f1942d0273ad64c1edbcee21a9544f62b9e11a24`. The exact-head Laravel CI #1649 and Test #1885 both completed successfully: [Laravel CI #1649](https://github.com/mattari20/estudent-listing/actions/runs/40412479692), [Test #1885](https://github.com/mattari20/estudent-listing/actions/runs/40412479687). Laravel CI's Laravel tests and format verification passed; Test's asset build, database preparation, and test suite passed.
- Tracker structure check: exactly five Phase 2 step headings and five explicit completion gates are present. Each step has a target, tasks/requirements, checks, and a gate.
- Workflow configuration verified: Laravel CI and Test trigger on PRs targeting `main` and pushes to `main`; their database migrations run against SQLite test databases. `.github/workflows/hostinger-readonly-diagnostics.yml` supports push, scheduled runs every six hours (`17 */6 * * *`), and manual `workflow_dispatch`. It references SSH secrets `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_USER`, `HOSTINGER_SSH_PRIVATE_KEY`, and `HOSTINGER_SSH_PORT`. `.github/workflows/controlled-official-source-refresh-migration.yml` has manual `confirm_migration` default false, deployed-code parity checks, and blocks inconsistent table/migration state.
- Access limitation verified from the available connector: it can read PRs, workflow runs/jobs and rerun failed jobs, but has no workflow-dispatch action. Hostinger secret values cannot be inspected. This connector therefore cannot invoke the live diagnostic or prove SSH access solely from repository configuration. If no other authorized dispatch interface is available, a human must click **Run workflow** on the read-only diagnostic workflow.
- No production write, record deletion, migration, or deployment was executed. Live schema inspection is assigned to Step 2 and has not been started.
- Final Step 1 gate: **NOT YET**. Repository/CI/configuration checks are verified, but live read-only diagnostics have not confirmed current Hostinger access. Resume point: initiate `Hostinger Read-only Diagnostics` manually in GitHub Actions (not the migration workflow; do not set `confirm_migration=true`), capture its read-only output and outcome, inspect logs for unintended mutation risk, then re-evaluate this gate. Do not start Step 2 until the gate passes.
