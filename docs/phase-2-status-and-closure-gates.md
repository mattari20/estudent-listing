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
| Hostinger read-only diagnostics | Application tree matched; private storage boundary and guest denial passed; native cron status unresolved | [Latest diagnostics](https://github.com/mattari20/estudent-listing/actions/runs/40189423650) |
| Official-source refresh schema readiness | Application code match; **failed because migration/table is absent** | [Latest readiness run](https://github.com/mattari20/estudent-listing/actions/runs/40189301894) |
| PR-G — reusable read-only runtime readiness audit | Merged | [PR #743](https://github.com/mattari20/estudent-listing/pull/743), merge commit `43398d4d61bfa845139a7126981cf512b7b0bb4c` |
| PR-H — initial revision-check correction and evidence report | Merged | [PR #744](https://github.com/mattari20/estudent-listing/pull/744), merge commit `c3f81b56e62437205bf732cb9645d1044e0ce3af` |
| PR-I — compare deployed application file tree | Merged; CI passed | [PR #745](https://github.com/mattari20/estudent-listing/pull/745), merge commit `b618eb25e25e688af0f7006e2d9fba243337f71b`; [Test](https://github.com/mattari20/estudent-listing/actions/runs/40189226268), [Laravel CI](https://github.com/mattari20/estudent-listing/actions/runs/40189226314) |

## 1A. Original PR-A through PR-H plan — actual completion vs workpack preparation

| Planned work | Current repository artifact | Actual status |
|---|---|---|
| PR-A — fix false-positive discovery extraction | [PR #737](https://github.com/mattari20/estudent-listing/pull/737) | **Merged**; extractor version 4 limits page-wide fallback and excludes navigation/header/footer links. It prevents a known false-positive path but does not audit existing rows. |
| PR-B — classify all 112 GCU candidates | [PR #738](https://github.com/mattari20/estudent-listing/pull/738) | **Workpack PR still open; live audit not done.** Runbook/template exists in the branch, but every current candidate still needs source-backed row disposition. |
| PR-C — reconcile 76 promotion-failure events | [PR #739](https://github.com/mattari20/estudent-listing/pull/739) | **Workpack PR still open; reconciliation not done.** The 76 are historical events, not necessarily 76 unique unresolved rows; current event-to-row mapping is missing. |
| PR-D — verify 36 programme/offering gaps | [PR #740](https://github.com/mattari20/estudent-listing/pull/740) | **Workpack PR still open; live/source audit incomplete.** Static evidence register exists, but source/campus verification and current live duplicate checks remain open. |
| PR-E — idempotent refresh and duplicate prevention | [PR #741](https://github.com/mattari20/estudent-listing/pull/741) | **Merged; CI passed.** |
| PR-F — private package lifecycle and checksum validation | [PR #742](https://github.com/mattari20/estudent-listing/pull/742) | **Merged; CI passed.** |
| PR-G — zero-cost runtime readiness audit | [PR #743](https://github.com/mattari20/estudent-listing/pull/743) | **Merged; CI passed.** Runtime acceptance is only partly verified. |
| PR-H — evidence-based Phase 2 closure report | [PR #744](https://github.com/mattari20/estudent-listing/pull/744) | **Merged; CI passed.** This is a report/closure-gate artifact, not evidence that all gates passed. |
| Follow-up PR-I — robust deployed application-tree comparison | [PR #745](https://github.com/mattari20/estudent-listing/pull/745) | **Merged; CI passed.** Corrected a revision-check issue found during live verification. |

**Important gap:** PRs #738–#740 prepare repeatable review procedures and templates; they do not themselves complete the live candidate classification, failure-event reconciliation, or offering-gap review. The original plan therefore has code/workpack milestones merged or prepared, but its core Step 3 evidence-based data audits are still outstanding.

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

**Interpretation:** This verifies the *path-filtered application-code revision* and a safe no-op execution. The latest [Hostinger read-only diagnostics run](https://github.com/mattari20/estudent-listing/actions/runs/40189423650) reported the application tree matched the expected application revision, the `institute_discovery_run_outcomes` table and its migration are present, two monitoring policies are active, the private review-package root is outside the public root, 10 private package files were found, and unauthenticated requests to discovery history/package download redirected to the Institutes login. It also reported `hostinger_scheduler_cron=missing_or_unreadable`. The GitHub Actions scheduled workflow itself is running and invokes the governed commands directly; the Hostinger native cron entry remains unverified and may affect other Laravel scheduled tasks. The diagnostics did not test an authenticated CSV/manifest download or response headers.

These checks do **not** prove that a changed official source produced a new review package or that a due policy completed a discovery run.

The latest read-only official-source readiness run [#40189301894](https://github.com/mattari20/estudent-listing/actions/runs/40189301894) confirmed:
- `official_source_refresh_events` table: absent.
- Expected migration `2026_10_25_000002_create_official_source_refresh_events_table`: migration file present, but not recorded as applied.
- No migration or production data mutation was run.

PR-H's first correction still used `git log -1 -- <application paths>`; after a merge commit, Git path-history simplification selected the merge commit rather than the application-content revision. PR-I changes the check to compare the actual deployed application-path tree against the expected application revision, fetching only the expected commit object into Git metadata if needed (no checkout or application-file change). The post-PR-I readiness run now reports `application_code_status=APPLICATION_CODE_MATCH`; it fails only at the real schema gate because `official_source_refresh_events` is absent and its migration is unapplied.

## 3. Blocking acceptance gates

### A. Deployment and schema readiness
- [ ] Dispatch the new read-only runtime readiness workflow after PR-G is merged; the available GitHub actions connector does not expose workflow-dispatch, so no manual dispatch has been claimed.
- [x] Read-only check confirms `official_source_refresh_events` is absent and migration `2026_10_25_000002_create_official_source_refresh_events_table` is not applied. Evidence: [Official Source Refresh Readiness run, attempt 2](https://github.com/mattari20/estudent-listing/actions/runs/40187990642).
- [x] Correct the revision comparison in PR-I and verify the deployed application tree matches the expected revision. Evidence: [post-PR-I readiness run](https://github.com/mattari20/estudent-listing/actions/runs/40189301894).
- [x] Re-run the readiness check; it confirms the application code matches and correctly fails the schema gate. The source-refresh migration remains unapplied and requires the separately approved controlled process.
- [ ] Decide whether Hostinger's native cron is required for any Laravel scheduled tasks beyond the GitHub Actions workflow. If yes, verify the entry in Hostinger's scheduler UI; diagnostics returned `missing_or_unreadable`. The GitHub Actions scheduled discovery workflow is active and invokes its governed commands directly.
- [ ] Do not apply migrations from the audit. The required migration remains unapplied; use the controlled migration workflow only after preflight, exact impact, and approval requirements are reviewed.


### B. Scheduled zero-cost discovery
- [ ] Investigate why the scheduled run found zero active due monitoring policies, and verify whether this is intended configuration or a scheduling-policy gap.
- [ ] Obtain evidence from a due policy / controlled test that records a run outcome and creates or reuses the expected immutable batch and private review package.
- [ ] Confirm no `INSTITUTE_AI_API_KEY` is required and no paid AI calls occur.
- [ ] Verify batch row counts, manifest counts, CSV checksum, and provenance reconcile.

### C. Protected package retrieval
- [x] Read-only Hostinger diagnostics found the private package root outside the public root and confirmed unauthenticated requests redirect to the Institutes login. Evidence: [latest Hostinger diagnostics](https://github.com/mattari20/estudent-listing/actions/runs/40189423650).
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

**Phase 2 closure is not yet justified.** PR-E, PR-F, PR-G, PR-H, and PR-I are merged; PR-G/H/I CI checks passed. Hostinger diagnostics and the corrected readiness check confirm application-code match. The source-refresh table/migration is absent, Hostinger's native cron entry is missing or unreadable, the latest scheduled discovery run found no due policies, authenticated package downloads/headers remain unverified, and candidate/offering remediation workpacks still need evidence-backed completion. The next closure report must replace each open checkbox with a result, exact run/commit link, and any remaining blocker.
