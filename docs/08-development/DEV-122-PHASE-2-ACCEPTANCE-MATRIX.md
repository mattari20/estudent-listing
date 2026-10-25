# DEV-122 — Phase 2 acceptance matrix

This matrix distinguishes repository-level checks from live-host verification.

## Automated repository gates

- [x] Laravel CI and Test workflows pass on the latest verified merge commit (PR #617 checks passed; post-merge checks are being confirmed).
- [x] Discovery review package generation and idempotent reuse have feature coverage.
- [x] Discovery-run outcome ledger records governed outcomes.
- [x] Protected operator route is present for run history and package retrieval.
- [x] Retention command defaults to dry-run and requires explicit `--apply`.
- [ ] Retention edge-case regression tests pass in CI.
- [x] Protected download integration tests cover unauthenticated access, invalid batch types, missing files, and valid CSV/manifest downloads (PR #608 CI coverage).
- [x] Discovery run-history integration test verifies operator authorization and pagination (PR #608 CI coverage).
- [ ] Scheduler configuration is confirmed to invoke deterministic discovery without requiring an AI provider key.

## Hostinger acceptance gates

- [x] Read-only diagnostics confirm deployed application-code match (run #40077517184; target app revision `00c3ed557d2981befb74dec9aed262531c4065ad`).
- [x] Live schema diagnostics confirm `institute_discovery_run_outcomes` exists and migration is applied (run #40077517184).
- [ ] Authenticated operator can open discovery run history.
- [ ] Authenticated operator can download an existing package; unauthenticated access is denied.
- [ ] Scheduler run records a result and generates a package when source content changes.
- [ ] An unchanged source produces a recorded no-change outcome without duplicate candidate batches.
- [x] Live filesystem diagnostics prove the package root is outside the public web root and found four package files (run #40077517184). Direct guest requests to protected history/download routes redirect to login; direct public access to an individual private file remains untested.
- [x] Latest governed scheduler attempt reported identical before/after canonical counts and `canonical_mutations=NONE`; that run was a no-due-policy no-op, not proof of a source-monitoring outcome.

## Safety constraints

- No automatic candidate approval or promotion.
- No package deletion unless explicitly invoked with `--apply` after reviewing dry-run output.
- No production data repair writes without a separately reviewed record-level plan.
- AI extraction remains optional; deterministic discovery must work without paid AI credentials.

Phase 2 is not complete until automated gates pass and the live Hostinger gates are independently verified.


## Evidence checkpoint — 24 October 2026

- [x] Laravel CI and Test passed for PR #608 operator history/download acceptance tests.
- [x] Live diagnostics confirmed the discovery outcome table exists and the migration is applied.
- [x] Controlled deployment of `df79fc97537716aa0b680e17651ba5e5f24aae6a` passed the Laravel bootstrap smoke test and read-only offering preflight.
- [ ] Deploy the newer main application-code revision containing PR #610's unchanged-outcome fix using the controlled deployment gate.
- [ ] Observe a live scheduled run with a due policy and a newly recorded outcome while canonical counts remain unchanged.
- [ ] Independently verify authenticated operator run-history and package downloads, guest denial, private package path, and response headers on the live host.
- [ ] Verify a live run at due cadence; no-due-policy hourly runs are valid no-ops and do not close this gate.

The current main branch is ahead of Hostinger application code. Phase 2 remains open until the current revision is deployed and the live gates above are evidenced. No live database writes or migrations are authorized by this matrix.


## Current evidence batch — 24 October 2026 (after PRs #613–#617)

- [x] Guest HTTP requests to `/institutes/operations/discovery-run-history` and the discovery-package route both returned HTTP 302 to `/institutes/login` in live diagnostics.
- [x] The configured local storage path for discovery review packages is resolved outside the public web root; four private package files were found.
- [x] The live integrity audit now passes after correcting target-revision fetch and commit comparison; run [40077517197](https://github.com/mattari20/estudent-listing/actions/runs/40077517197).
- [x] The live data integrity report remains read-only and reports 19 institutions, 22 campuses, 85 programs, 55 offerings, and 36 active programs without offerings.
- [ ] A due source-monitoring policy must execute and record a new outcome. Latest governed run [40076404070](https://github.com/mattari20/estudent-listing/actions/runs/40076404070) was `NO_DUE_POLICIES_NOOP`, with outcome count 0 before and after.
- [ ] Authenticated operator must load live run history and download an existing CSV and manifest; verify `Cache-Control: private, no-store, max-age=0` and `X-Content-Type-Options: nosniff`.
- [ ] Verify a source-change run and an unchanged-source run each record the expected outcome without changing canonical counts.
- [ ] Hostinger cron remains `missing_or_unreadable`; GitHub's scheduled workflow is configured, but Hostinger cron itself is not independently verified.


## Structured enrichment promotion audit coverage — 25 October 2026

- [x] Dedicated `structured_enrichment_promotion_events` table and Eloquent model merged in PR #638.
- [x] Successful canonical promotion records before/after snapshots in the same transaction as the canonical write.
- [x] Program idempotency test verifies two audit events and the before/after program name.
- [x] Campus idempotency test verifies two audit events and the before/after campus name (this PR).
- [x] Offering idempotency test verifies two audit events and the before/after offering status (this PR).
- [ ] Add an explicit rollback regression test proving a failed audit insert rolls back the canonical write.
- [ ] Verify audit-table migration and audit-event contents in a deployed test/staging environment.
- [ ] Live Hostinger revision, scheduler, and authenticated operator acceptance gates above remain open; this repository-only audit coverage does not close them.

Audit events currently record successful promotions only. They are transactionally coupled to the canonical write, but this table must not be treated as a complete immutable security log: failed/blocked attempts, deletion/retention controls, and production access policy require separate review.

## Official-source drift refresh acceptance — 25 October 2026

The authoritative source-of-truth policy is documented in `docs/official-source-drift-refresh-policy.md`. Repository-level changes in PRs #642–#645 add the following safeguards, subject to CI and merge verification:

- [ ] A changed field is marked `refresh_eligible` only when evidence includes verified source identity, successful retrieval, confirmed entity match, observation timestamp, and content fingerprint; an `official=true` flag alone is insufficient.
- [ ] Missing snapshot hashes are recorded as `uncomparable`, not falsely reported as a material source change.
- [ ] Re-comparing the same snapshot reuses its observation and does not create duplicate drift findings.
- [ ] An existing open reverification item escalates when a higher priority arrives, preserves its first-observed timestamp, and cannot be downgraded by a later lower-priority reason.
- [ ] Integration connects `refresh_eligible` field diffs to a governed, transactional canonical update using current source evidence.
- [ ] A real source change on a due monitoring policy creates a field-level before/after report and applies verified updates without indefinite review delay.
- [ ] A failed, empty, malformed, or unverified source never overwrites, clears, or deactivates canonical data.
- [ ] Staging verifies scheduler execution, applied migration state, audit snapshots, and no-change idempotency before any production write path is enabled.

Important distinction: `refresh_eligible` is a classification outcome, not a database write. These PRs do not by themselves prove end-to-end automatic refresh, scheduler operation, or live Hostinger deployment. Phase 2 remains open until implementation, CI, staging, and live acceptance gates have evidence.

