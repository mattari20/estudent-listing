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

**Step 1 closeout status: PASS (2026-10-27).** All Step 1 requirements are met based on the evidence below. The available connector cannot dispatch a workflow, so a human must manually start the guarded workflow if/when live diagnostics are needed. This limitation is documented, not hidden. No production migration or write was executed. Live schema inspection belongs to Step 2 and is not claimed complete here.

**Initial evidence (2026-10-27):**
- PR #826 open, mergeable, not merged. Laravel CI run #1639 passed: https://github.com/mattari20/estudent-listing/actions/runs/40406254876
- PR #826 Test run #1875 passed: https://github.com/mattari20/estudent-listing/actions/runs/40406254874
- PR #827 open, mergeable, not merged. Laravel CI run #1640 passed: https://github.com/mattari20/estudent-listing/actions/runs/40406299544
- PR #827 Test run #1876 passed: https://github.com/mattari20/estudent-listing/actions/runs/40406299502
- These CI runs do not prove live database schema correctness or that the migration workflow ran on Hostinger.
- Previous migration run reported a MySQL index identifier-length error; the live schema/migration state must be checked read-only before any migration retry.

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
