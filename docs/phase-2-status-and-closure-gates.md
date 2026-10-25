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
| Official-source refresh schema readiness | **Failed — migration/table absent** | [Read-only readiness run](https://github.com/mattari20/estudent-listing/actions/runs/40187990642) |
| PR-G — reusable read-only runtime readiness audit | Pending review/CI | [PR #743](https://github.com/mattari20/estudent-listing/pull/743) |

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

**Interpretation:** This verifies the *path-filtered application-code revision* and a safe no-op execution. It does **not** prove that a changed official source produced a new review package, that a due policy completed a discovery run, or that package downloads work end-to-end.

A separate read-only official-source readiness run reported:
- `official_source_refresh_events` table: absent.
- Expected migration `2026_10_25_000002_create_official_source_refresh_events_table`: migration file present, but not recorded as applied.
- The deployed checkout's full HEAD was `30f8f694b61c273dc5186989765089a137c04704`, versus expected application revision `73c75f1a8beab44957f970aed2aaf5fb5e5d7d0f`; that workflow marked application-code status drift/unverifiable.
- No migration or production data mutation was run.

The later scheduled workflow's path-filtered application-code revision matched `73c75f1a8beab44957f970aed2aaf5fb5e5d7d0f`. These two revision checks use different revision definitions, so the full checkout HEAD vs application-code revision discrepancy remains to be reconciled; do not describe the entire deployment state as fully verified.

## 3. Blocking acceptance gates

### A. Deployment and schema readiness
- [ ] Run the new read-only runtime readiness workflow after PR-G is merged.
- [x] Read-only check confirms `official_source_refresh_events` is absent and migration `2026_10_25_000002_create_official_source_refresh_events_table` is not applied. Evidence: [Official Source Refresh Readiness run](https://github.com/mattari20/estudent-listing/actions/runs/40187990642).
- [ ] Reconcile full deployed checkout HEAD vs the path-filtered application-code revision using a consistent read-only revision check.
- [ ] Do not apply migrations from the audit. The required migration remains unapplied; use the controlled migration workflow only after preflight, exact impact, and approval requirements are reviewed.
- [ ] If the migration is absent, use the existing controlled migration workflow only after its preflight, exact impact, and approval requirements are reviewed. Do not use ad-hoc production SQL.

### B. Scheduled zero-cost discovery
- [ ] Investigate why the scheduled run found zero active due monitoring policies, and verify whether this is intended configuration or a scheduling-policy gap.
- [ ] Obtain evidence from a due policy / controlled test that records a run outcome and creates or reuses the expected immutable batch and private review package.
- [ ] Confirm no `INSTITUTE_AI_API_KEY` is required and no paid AI calls occur.
- [ ] Verify batch row counts, manifest counts, CSV checksum, and provenance reconcile.

### C. Protected package retrieval
- [ ] In an authenticated operator session, download CSV and manifest for a known discovery batch.
- [ ] Verify unauthenticated access is rejected.
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

**Phase 2 closure is not yet justified.** The code improvements in PR-E and PR-F are merged and their CI is green. A scheduled no-op passed and its path-filtered application-code revision matched, but a separate readiness check found the source-refresh table/migration absent and reported a full-checkout revision discrepancy. The live discovery schedule had no due policies, protected downloads have not yet been verified in an authenticated session, and the candidate/offering remediation workpacks still need evidence-backed completion. The next closure report must replace each open checkbox with a result, exact run/commit link, and any remaining blocker.
