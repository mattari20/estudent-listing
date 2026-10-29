# Institutes Directory — Step 1 recovery status

**Updated:** 2026-10-29  
**Repository:** `mattari20/estudent-listing`  
**Current main target:** `4b95e2d945583f36ff07376ed51f2d2727c0b78d`  
**Status:** Step 1 remains open. Step 2 has not started.

## Confirmed PRs and CI

- PR #900 merged as `fb82aa6043b342819be84f71e2ddf448cfbceac5`; Test [#40827745368](https://github.com/mattari20/estudent-listing/actions/runs/40827745368) and Laravel CI [#40827745303](https://github.com/mattari20/estudent-listing/actions/runs/40827745303) passed.
- PR #901 merged as `c83f0162731a7efccda9cf1e8c6878697659d017`; Test [#40829679792](https://github.com/mattari20/estudent-listing/actions/runs/40829679792) and Laravel CI [#40829679788](https://github.com/mattari20/estudent-listing/actions/runs/40829679788) passed.
- PR #902 merged as `4b95e2d945583f36ff07376ed51f2d2727c0b78d`; post-merge Test [#40830834468](https://github.com/mattari20/estudent-listing/actions/runs/40830834468), Laravel CI [#40830834622](https://github.com/mattari20/estudent-listing/actions/runs/40830834622), and frontend build [#40830834508](https://github.com/mattari20/estudent-listing/actions/runs/40830834508) passed. Public Site Monitor [#40830834637](https://github.com/mattari20/estudent-listing/actions/runs/40830834637) and Command Orchestrator [#40830834514](https://github.com/mattari20/estudent-listing/actions/runs/40830834514) also passed.
- PR #902 sets `apiPrefix: 'institutes/api'` in `bootstrap/app.php`, aligns API feature tests to `/institutes/api/v1/institutions`, and tests a valid empty JSON paginator. This corrects the API namespace for the deployed `/institutes/` subfolder in source code.

## Live routing evidence and deployment blocker

- Previous pre-fix Hostinger run [#40829824507](https://github.com/mattari20/estudent-listing/actions/runs/40829824507) verified Laravel registered `api/v1/institutions`, `api/v1/institutions/nearby`, and `api/v1/institutions/{institution:slug}`, while candidate public URLs returned HTML 404. The root URL `/api/v1/institutions` fell through to the WordPress site.
- Immediately after PR #902, Hostinger diagnostic [#40830834506](https://github.com/mattari20/estudent-listing/actions/runs/40830834506) stopped at the deployment parity gate: deployed revision was still `c83f0162731a7efccda9cf1e8c6878697659d017`; expected application revision was `4b95e2d945583f36ff07376ed51f2d2727c0b78d`. This run did not test the new API prefix and must not be described as a failed deployed fix.
- Automated Live Data Integrity Audit [#40830834606](https://github.com/mattari20/estudent-listing/actions/runs/40830834606) likewise stopped because the target revision could not be reconciled with Hostinger's deployed Git metadata.
- The repository's Controlled Hostinger Code Deployment workflow is manual and defaults `confirm_deployment=false`. No production deployment action was performed in this execution. Therefore, live success of `/institutes/api/v1/institutions` is NOT VERIFIED until the approved deployment workflow has run and a fresh read-only probe confirms HTTP 200, `application/json`, and the JSON paginator schema.

## Empty data and browser exception

- Pre-fix live Hostinger observations [#40829824507](https://github.com/mattari20/estudent-listing/actions/runs/40829824507) and [#40830834506](https://github.com/mattari20/estudent-listing/actions/runs/40830834506) show `institutions`, `campuses`, `programs`, and `program_offerings` each have zero total and zero active rows. The owner confirmed dummy records were intentionally removed. This is the expected current database state, not a data deletion failure to repair.
- The root directory page is rendered server-side by `InstitutionController@index` and returns HTTP 200 with the empty-state markup. Its query only selects `institutions.status=active`.
- Repository `resources/js/app.js` handles password visibility only; `resources/js/bootstrap.js` is a placeholder. No directory API fetch/JSON parse is present in those app scripts. The browser `SyntaxError: Unexpected end of JSON input` remains NOT VERIFIED as resolved and has not been causally attributed to a particular browser request. The earlier root/subfolder API failures are proven separate symptoms; after deployment, repeat live API diagnostics and, if the browser exception persists, capture the exact Network request and initiator from DevTools.
- The two `unload` permissions-policy warnings are not currently linked to the empty dataset or JSON exception; low priority pending evidence.

## Candidate import audit evidence

The last row-level read-only trace in Hostinger diagnostic [#40829824507](https://github.com/mattari20/estudent-listing/actions/runs/40829824507) showed five candidate institution import rows:

| Import row ID | Batch ID | Name / slug | Status | Evidence / decisions |
| --- | ---: | --- | --- | --- |
| 348 | 19 | Government College University Lahore / `government-college-university-lahore` | candidate | Active source evidence present; no approved identity or promotion decision |
| 349 | 20 | Same name / slug | candidate | Active source evidence present; no approved identity or promotion decision |
| 350 | 21 | Same name / slug | candidate | Active source evidence present; no approved identity or promotion decision |
| 351 | 22 | Same name / slug | candidate | Active source evidence present; no approved identity or promotion decision |
| 352 | 23 | Same name / slug | candidate | Active source evidence present; no approved identity or promotion decision |

All five rows reported validated batches, active source ID 27, `match_state=new_candidate`, no validation-error fields, no identity decision, no approved promotion decision, and no canonical institution status. The campus import trace was empty. Since the canonical tables are empty, there is no current canonical record to point these rows at. The identical identity/slug across five batches is a duplicate-risk cluster requiring the existing cross-batch audit and source provenance review. This is not a claim that the five source contents are identical; payloads were deliberately excluded from the diagnostic. No candidate row has been approved, promoted, published, rejected or deleted during this execution.

The system's row-level audit and readiness services enforce these gates: batch validated, source active, explicit `approved_new` or `approved_match` identity decision, active source evidence, no open conflict, and canonical match handling. Source freshness/retrieval lineage is a further production-readiness gate. The safe workflow is official-source discovery/import → row-level verification and duplicate audit → reviewer decision → evidence and identity resolution → batch/readiness gates → controlled promotion/publication. Do not bypass or bulk approve this cluster.

## duplicate_rows schema/ledger incident

Pre-fix Hostinger diagnostics [#40829824507](https://github.com/mattari20/estudent-listing/actions/runs/40829824507) verified:
- `import_batches.duplicate_rows` exists as `int(10) unsigned`, NOT NULL, default `0`, position 11 after `candidate_rows` and before `received_at`.
- Column definition/position matches migration `2026_10_23_000001_add_duplicate_rows_to_import_batches_table`.
- Target migration file exists but exact migration-ledger match count is `0`; existing creation migration `2026_09_24_000001_create_import_batches_table` is in the ledger.
- Diagnosis: `COLUMN_PRESENT_LEDGER_ABSENT`; `schema_equivalence=UNVERIFIED`; `migration_origin=UNKNOWN`; no write requested or performed.
- Source-code history points to commit `556bfce3d9c381bfd50d38017ee0982356b0b2af` (PR #518, 2026-10-23) as the likely introduction of the column migration/code, but this does not establish how the live column was physically added.

Safe reconciliation plan remains read-only: wait until the deployed app-code revision is current, rerun the upgraded schema diagnostic, compare the complete live table definition (all columns/order/defaults/nullability/unsigned flags/indexes/FKs/engine/collation) against the original creation migration and all later migrations, inspect exact and related ledger entries and Git history, then present a narrow repair proposal with impact and rollback/verification. No ledger row insertion, migration rerun, column alteration, or other database mutation is authorized by this report.

## Scheduler

- Laravel `bootstrap/app.php` schedules `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly.
- Hostinger PHP executable/version recorded: `/opt/alt/php85/usr/bin/php`, PHP `8.5.12`.
- Expected every-minute scheduler command: `* * * * * cd /home/u537436044/domains/estudent.pk/public_html/institutes && /opt/alt/php85/usr/bin/php artisan schedule:run >> /dev/null 2>&1`.
- `crontab -l` returned exit 127 / unreadable. That is inconclusive for hPanel. User will check hPanel's existing job list; do not create/modify/delete a cron job until confirmed absent and exact command/path validated.

## Step 1 gate checklist

| Criterion | Status | Evidence / remaining gate |
| --- | --- | --- |
| 1. Directory page renders | PASS for HTML render; overall browser error unresolved | Public Site Monitor #40830834637 passed; user still sees JSON SyntaxError. |
| 2. Root cause of JSON error found and resolved | BLOCKED | Confirmed API route prefix mismatch in code; fix merged, but deployment not verified and exact browser initiator not captured. |
| 3. Verified published institutions render | BLOCKED / expected empty state | Canonical tables deliberately empty; no real records should be inserted until official verification and approval. |
| 4. Search and empty-state behavior | PARTIAL | Server-side query and empty-state tests exist; public search/API path smoke after deployment not verified. |
| 5. Pagination on empty dataset | PARTIAL | Unit test now asserts valid empty paginator; live API response after deployment pending. Public listing correctly has no pagination for zero records. |
| 6. Detail pages show correct records | BLOCKED | No canonical active institution exists for real public detail smoke. |
| 7. Campus/program links and relationships | NOT VERIFIED | Table counts are zero; previous integrity checks have no rows to assess. |
| 8. Candidate data risks documented | PARTIAL | Rows 348–352, batches 19–23 documented; official source content and duplicate relationships require a fresh full row-level evidence bundle. |
| 9. Migration/schema reconciliation plan | PARTIAL | Evidence-based safe plan documented; full schema equivalence and migration origin remain unknown. |
| 10. Scheduler/deployment checks | BLOCKED | Expected cron command known; hPanel state not yet verified. Production code revision mismatch is proven. |
| 11. Required CI | PASS | Post-merge Test #40830834468, Laravel CI #40830834622, frontend build #40830834508 all passed. |
| 12. Production acceptance recorded | PARTIAL | HTML smoke passed, but API acceptance and deployed-code parity for new route fix are unverified. |

## Safety boundary / next actions

1. Have the owner perform/authorize the controlled deployment via the existing workflow when ready; until that action is explicitly confirmed, do not deploy.
2. After deployment, run the existing read-only diagnostic and verify `/institutes/api/v1/institutions` returns valid empty JSON pagination.
3. If JSON parse error persists in the browser after the API path succeeds, capture the exact Network request and initiator; use that evidence for a focused code fix.
4. Run existing private row-level duplicate and source-readiness diagnostics after deployment and produce a provenance report for rows 348–352 without changing them.
5. Rerun live migration metadata after code alignment and prepare the reconciliation proposal, still without database writes.
6. Confirm hPanel cron list and recent execution history manually; the rest can remain automated.

No Step 2 work has begun.
