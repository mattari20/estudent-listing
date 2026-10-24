# DEV-122 — Phase 2 acceptance matrix

This matrix distinguishes repository-level checks from live-host verification.

## Automated repository gates

- [ ] Laravel CI and Test workflows pass on the final merge commit.
- [x] Discovery review package generation and idempotent reuse have feature coverage.
- [x] Discovery-run outcome ledger records governed outcomes.
- [x] Protected operator route is present for run history and package retrieval.
- [x] Retention command defaults to dry-run and requires explicit `--apply`.
- [ ] Retention edge-case regression tests pass in CI.
- [ ] Protected download integration tests cover unauthenticated access, invalid batch types, missing files, and valid CSV/manifest downloads.
- [ ] Discovery run-history integration test verifies operator authorization and pagination.
- [ ] Scheduler configuration is confirmed to invoke deterministic discovery without requiring an AI provider key.

## Hostinger acceptance gates

- [ ] Read-only diagnostics confirm the deployed application code matches the intended `main` revision.
- [ ] Migration status confirms the discovery outcome table exists before the operator history page is enabled.
- [ ] Authenticated operator can open discovery run history.
- [ ] Authenticated operator can download an existing package; unauthenticated access is denied.
- [ ] Scheduler run records a result and generates a package when source content changes.
- [ ] An unchanged source produces a recorded no-change outcome without duplicate candidate batches.
- [ ] Private package files are outside the public web root and are not publicly accessible.
- [ ] No production canonical records are modified by discovery or package generation.

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
