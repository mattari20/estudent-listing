# Institutes Directory Phase 2 — evidence and closure gates

**Report date:** 2026-10-25  
**Status:** **IN PROGRESS — NOT CLOSED**  
**Purpose:** Evidence-based status record. A green workflow or successful code deployment is not, by itself, proof that the Phase 2 data-quality and live acceptance criteria are satisfied.

## 1. Verified implementation milestones

| Work item | Status | Evidence |
|---|---|---|
| PR-E — deterministic discovery refresh idempotency and provenance | Merged | [PR #741](https://github.com/mattari20/estudent-listing/pull/741), merge commit `d6f91d19d5b3c2edbdd90cfbfcce3265e4119fb7` |
| PR-F — batch/snapshot-scoped private review packages and checksum validation | Merged | [PR #742](https://github.com/mattari20/estudent-listing/pull/742), merge commit `73c75f1a8beab44957f970aed2aaf5fb5e5d7d0f` |
| PR-E tests | Passed on latest PR head | [Test workflow](https://github.com/mattari20/estudent-listing/actions/runs/40187826097), [Laravel CI](https://github.com/mattari20/estudent-listing/actions/runs/40187826117) |
| PR-F tests | Passed on latest PR head after updating legacy fixtures | [Test workflow](https://github.com/mattari20/estudent-listing/actions/runs/40187908056), [Laravel CI](https://github.com/mattari20/estudent-listing/actions/runs/40187908055) |
| Hostinger deployed-code and governed scheduler smoke check | Application-code revision matched; no-op | [Scheduled run #19](https://github.com/mattari20/estudent-listing/actions/runs/40188541139) |
| Hostinger read-only diagnostics | Application tree matched; private storage boundary and guest denial passed; native cron status unresolved | [Latest diagnostics](https://github.com/mattari20/estudent-listing/actions/runs/40189064829) |
| Official-source refresh schema readiness | **Failed — migration/table absent** | [Latest readiness run](https://github.com/mattari20/estudent-listing/actions/runs/40189064804) |
| PR-G — reusable read-only runtime readiness audit | Merged | [PR #743](https://github.com/mattari20/estudent-listing/pull/743), merge commit `43398d4d61bfa845139a7126981cf512b7b0bb4c` |
| PR-H — initial revision-check correction and evidence report | Merged | [PR #744](https://github.com/mattari20/estudent-listing/pull/744), merge commit `c3f81b56e62437205bf732cb9645d1044e0ce3af` |
| PR-I — compare deployed application file tree | Pending review/CI | [PR #745](https://github.com/mattari20/estudent-listing/pull/745) |

## 2. Hostinger evidence from scheduled run #19

The scheduled workflow reported:

- Expected application-code revision and Hostinger deployed application-code revision both matched: `73c75f1a8beab44957f970aed2aaf5fb5e5d7d0f`.
- Read-only canonical counts before and after were identical:
  - Institutions: 19
  - Campuses: 22
  - Programs: 85
  - Program offerings: 55
  - Admission intakes: 5
- Discovery run-outcome count remained 2.
- Active monitoring policies due at that check: 0.
- Bootstrap and source-monitor commands exited successfully, with zero source-monitor work performed.
- Paid AI calls, migrations, canonical promotion, and publication were disabled/not run.

**Interpretation:** This verifies the *path-filtered application-code revision* and a safe no-op execution. The latest [Hostinger read-only diagnostics run](https://github.com/mattari20/estudent-listing/actions/runs/40189064829) reported the application tree matched the expected application revision, the `institute_discovery_run_outcomes` table and its migration are present, two monitoring policies are active, the private review-package root is outside the public root, 10 private package files were found, and unauthenticated requests to discovery history/package download redirected to the Institutes login. It also reported `hostinger_scheduler_cron=missing_or_unreadable`. The GitHub Actions scheduled workflow itself is running and invokes the governed commands directly; the Hostinger native cron entry remains unverified and may affect other Laravel scheduled tasks. The diagnostics did not test an authenticated CSV/manifest download or response headers.

These checks do **not** prove that a changed official source produced a new review package or that a due policy completed a discovery run.

The latest read-only official-source readiness run [#40189064804](https://github.com/mattari20/estudent-listing/actions/runs/40189064804) confirmed:
- `official_source_refresh_events` table: absent.
- Expected migration `2026_10_25_000002_create_official_source_refresh_events_table`: migration file present, but not recorded as applied.
- No migration or production data mutation was run.

PR-H's first correction still used `git log -1 -- <application paths>`; after a merge commit, Git path-history simplification selected the merge commit rather than the application-content revision. The readiness run therefore still reported a false drift status. PR-I changes the check to compare the actual deployed application-path tree against the expected application revision, fetching only the expected commit object into Git metadata if needed (no checkout or application-file change). The corrected workflow must run after PR-I merges.

## 3. Blocking acceptance gates

### A. Deployment and schema readiness
- [ ] Dispatch the new read-only runtime readiness workflow after PR-G is merged; the available GitHub actions connector does not expose workflow-dispatch, so no manual dispatch has been claimed.
- [x] Read-only check confirms `official_source_refresh_events` is absent and migration `2026_10_25_000002_create_official_source_refresh_events_table` is not applied. Evidence: [Official Source Refresh Readiness run, attempt 2](https://github.com/mattari20/estudent-listing/actions/runs/40187990642).
- [x] Confirm the official-source readiness workflow's revision comparison was faulty; PR-H attempted a path-history comparison but the latest run showed that still produces a false mismatch.
- [ ] Merge PR-I after CI passes and re-run the readiness check; expected outcome is application-code match and a clear schema-not-ready result until migration is separately approved and applied.
- [ ] Decide whether Hostinger's native cron is required for any Laravel scheduled tasks beyond the GitHub Actions workflow. If yes, verify the entry in Hostinger's scheduler UI; diagnostics returned `missing_or_unreadable`. The GitHub Actions scheduled discovery workflow is active and invokes its governed commands directly.
- [ ] Do not apply migrations from the audit. The required migration remains unapplied; use the controlled migration workflow only after preflight, exact impact, and approval requirements are reviewed.


### B. Scheduled zero-cost discovery
- [ ] Investigate why the scheduled run found zero active due monitoring policies, and verify whether this is intended configuration or a scheduling-policy gap.
- [ ] Obtain evidence from a due policy / controlled test that records a run outcome and creates or reuses the expected immutable batch and private review package.
- [ ] Confirm no `INSTITUTE_AI_API_KEY` is required and no paid AI calls occur.
- [ ] Verify batch row counts, manifest counts, CSV checksum, and provenance reconcile.

### C. Protected package retrieval
- [x] Read-only Hostinger diagnostics found the private package root outside the public root and confirmed unauthenticated requests redirect to the Institutes login. Evidence: [latest Hostinger diagnostics](https://github.com/mattari20/estudent-listing/actions/runs/40189064829).
- [ ] In an authenticated operator session, download CSV and manifest for a known discovery batch.
- [x] Verify unauthenticated access is rejected.
- [ ] Verify batch ID, snapshot ID, and CSV SHA-256 validation; test that a mismatched or tampered package fails closed.
- [ ] Confirm response headers include `Cache-Control: private, no-store, max-age=0` and `X-Content-Type-Options: nosniff`.

### D. Data-quality remediation / Step 3
- [ ] Complete the row-level GCU candidate review using the current protected export and official source snapshots; do not infer candidate correctness from a row count alone.
- [ ] Reconcile the 76 historical promotion-failure **events** to unique import-row IDs and current dispositions.
- [ ] Verify each of the 36 active-programme/offering gaps against official programme and campus evidence; prepare a record-level repair plan with duplicate checks.
- [ ] Re-run the fresh read-only candidate and offering-gap audits and disposition every blocking finding.
- [ ] Review the open workpacks: [PR #738](https://github.com/mattari20/estudent-listing/pull/738), [PR #739](https://github.com/mattari20/estudent-listing/pull/739), and [PR #740](https://github.com/mattari20/estudent-listing/pull/740).

## 4. Safety and change control

- No production canonical records were approved, promoted, published, repaired, or deleted in the evidence cited above.
- No database migrations were executed in the scheduled run.
- Old discovery batches and provenance must remain intact.
- Keep AI disabled for scheduled discovery to preserve the zero-additional-monthly-cost requirement.
- Any production data repair requires a row-level plan, duplicate checks, explicit owner authorization, and the approved controlled execution path.
- Do not mark Phase 2 closed until every gate above is either passed with linked evidence or explicitly accepted as a documented non-blocking exception.

## 5. Closure decision

**Phase 2 closure is not yet justified.** PR-E, PR-F, PR-G, and PR-H are merged with green CI. Hostinger diagnostics confirm the application tree matches the expected application revision. The official-source readiness workflow's revision comparison still falsely flags the merge commit, so PR-I is correcting it. The source-refresh table/migration is absent, Hostinger's native cron entry is missing or unreadable, the latest scheduled discovery run found no due policies, authenticated package downloads/headers remain unverified, and candidate/offering remediation workpacks still need evidence-backed completion. The next closure report must replace each open checkbox with a result, exact run/commit link, and any remaining blocker.
