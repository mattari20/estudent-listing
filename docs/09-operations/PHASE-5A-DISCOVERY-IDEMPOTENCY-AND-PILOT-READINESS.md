# Phase 5A — Discovery Idempotency and First Pilot Readiness

Last reconciled: 2026-10-29  
Verified `main` baseline at continuation start: `90fc06164da925ac5ef5d0cfad31ec7f734ae78c`  
Merged repair: [PR #926](https://github.com/mattari20/estudent-listing/pull/926) — merge commit `90fc06164da925ac5ef5d0cfad31ec7f734ae78c`
Latest merged `main` after evidence-backed follow-up PR #927: `96abf59477d4377b64445408aa6dda0d2dac4852`
Current verified `main` snapshot at this reconciliation: `4cd42793471d259d8798feb1243e8bd993651f49` (includes PR #928's report-only update).

## Post-merge repository verification

The live `main` ref was verified first at PR #926's merge commit, then advanced through PR #927's collision-safety/lineage fix and PR #928's report correction. The current `main` SHA at this reconciliation is `4cd42793471d259d8798feb1243e8bd993651f49`. PRs #926, #927 and #928 are merged. The original pre-#926 base was `1b5202c35346c017de1c1dfb8d889d4b7ef6e15d`.

Post-merge checks for the merged SHA:

| Check | Result | Evidence |
|---|---|---|
| Full Test workflow | PASS — 736 tests, 3,312 assertions | [Run #40880064515](https://github.com/mattari20/estudent-listing/actions/runs/40880064515) |
| Laravel CI and formatting verification | PASS — same test total; formatting verification passed | [Run #40880064375](https://github.com/mattari20/estudent-listing/actions/runs/40880064375) |
| Frontend build | PASS | [Run #40880064409](https://github.com/mattari20/estudent-listing/actions/runs/40880064409) |
| Public site monitor | PASS | [Run #40880064345](https://github.com/mattari20/estudent-listing/actions/runs/40880064345) |
| Directory command orchestrator | PASS | [Run #40880064337](https://github.com/mattari20/estudent-listing/actions/runs/40880064337) |
| Hostinger Read-only Diagnostics | BLOCKED — deployed application checkout is behind expected revision | [Run #40880064429](https://github.com/mattari20/estudent-listing/actions/runs/40880064429) |
| Automated Live Data Integrity Audit | BLOCKED before database audit due to deployed revision mismatch | [Run #40880064420](https://github.com/mattari20/estudent-listing/actions/runs/40880064420) |

The two production failures are deployment-parity gates, not failures in the isolated regression suite. The deployed Hostinger checkout was `1b5202c35346c017de1c1dfb8d889d4b7ef6e15d`; the expected application-code revision for that run was `90fc06164da925ac5ef5d0cfad31ec7f734ae78c`. The diagnostics workflow completed its bounded read-only checks but exited with status 10 on the revision mismatch. The integrity audit explicitly stopped before issuing its database query. No production data was changed.

Do **not** infer that the code has been deployed. A deployment must use the existing Controlled Hostinger Code Deployment workflow with its explicit confirmation input and a separate authorized decision. No deployment was requested or performed as part of this continuation.


## Post-merge verification after PR #927

PR #927 is merged at `96abf59477d4377b64445408aa6dda0d2dac4852`. Its post-merge checks passed:

| Check | Result | Evidence |
|---|---|---|
| Test workflow | PASS — 737 tests, 3,314 assertions | [Run #40882355334](https://github.com/mattari20/estudent-listing/actions/runs/40882355334) |
| Laravel CI and formatting verification | PASS — 737 tests, 3,314 assertions; formatting verification passed | [Run #40882355259](https://github.com/mattari20/estudent-listing/actions/runs/40882355259) |
| Frontend build | PASS | [Run #40882355422](https://github.com/mattari20/estudent-listing/actions/runs/40882355422) |
| Public site monitor | PASS | [Run #40882355289](https://github.com/mattari20/estudent-listing/actions/runs/40882355289) |
| Directory command orchestrator | PASS | [Run #40882355456](https://github.com/mattari20/estudent-listing/actions/runs/40882355456) |
| Hostinger Read-only Diagnostics | Read-only data checks ran; final parity gate BLOCKED | [Run #40882355434](https://github.com/mattari20/estudent-listing/actions/runs/40882355434) |
| Automated Live Data Integrity Audit | BLOCKED before live database query | [Run #40882355388](https://github.com/mattari20/estudent-listing/actions/runs/40882355388) |

The first post-PR-#927 diagnostic (#40882355434) reported checkout `90fc06164da925ac5ef5d0cfad31ec7f734ae78c` and an application-file parity mismatch. The later read-only diagnostic (#40883402357), after PR #928 merged, reports checkout `96abf59477d4377b64445408aa6dda0d2dac4852`, expected application-code revision `b40ca2048e1a3b702a48f57233339e4c390e766a`, and `application_code_parity=match`. Thus the latest run verifies deployed application files match the expected app-code tree, even though the checkout SHA is not the current documentation-only `main` commit. The cause/actor for the checkout change was not established, and this task did not deploy production code. The separate Automated Live Data Integrity Audit (#40882355388) still stopped before its database query; a successful post-parity database audit is not yet verified.



## Latest verification after PR #928

The current `main` snapshot at this report update is `4cd42793471d259d8798feb1243e8bd993651f49`.

| Check | Result | Evidence |
|---|---|---|
| Test workflow | PASS — 737 tests, 3,314 assertions | [Run #40883401953](https://github.com/mattari20/estudent-listing/actions/runs/40883401953) |
| Laravel CI and formatting | PASS — 737 tests, 3,314 assertions; formatting verification passed | [Run #40883402021](https://github.com/mattari20/estudent-listing/actions/runs/40883402021) |
| Frontend build | PASS | [Run #40883402034](https://github.com/mattari20/estudent-listing/actions/runs/40883402034) |
| Public site monitor | PASS | [Run #40883402035](https://github.com/mattari20/estudent-listing/actions/runs/40883402035) |
| Command orchestrator | PASS | [Run #40883402082](https://github.com/mattari20/estudent-listing/actions/runs/40883402082) |
| Hostinger Read-only Diagnostics | BLOCKED by live search API response contract, after app-file parity matched | [Run #40883402357](https://github.com/mattari20/estudent-listing/actions/runs/40883402357) |
| Last Automated Live Data Integrity Audit | BLOCKED before database query; not rerun successfully after app-file parity matched | [Run #40882355388](https://github.com/mattari20/estudent-listing/actions/runs/40882355388) |

### New live smoke-test finding

The latest diagnostic reports application-file parity `match`, and the directory page returns HTTP 200 with valid HTML. The base API `/institutes/api/v1/institutions?per_page=2` returns HTTP 200, `application/json`, and a valid empty Laravel paginator (`data=[]`, `total=0`). However, the exact search request `/institutes/api/v1/institutions?q=COMSATS&per_page=2` returns HTTP 404 with `text/html` and a CMS-style HTML error page rather than JSON. This fails the live API contract step in run #40883402357.

Repository inspection does not show a route intentionally different for this query: `routes/api.php` defines GET `/v1/institutions`, `InstitutionApiController::index()` reads `q` and returns JSON pagination, and `tests/Feature/InstitutionApiTest.php` covers a successful `?q=AUP` search. The isolated suite passes. The live HTML 404 therefore does not currently establish a PHP search-service defect; a hosting/security/rewrite layer or other live-only routing condition is plausible but **not confirmed**. Do not commit a speculative code change. An authorized operator should inspect the hosting access/error/WAF logs for the exact timestamp and request path, establish whether the request reached Laravel, and then correct the responsible configuration/application issue through the normal authorized workflow. Re-run the existing read-only diagnostics afterwards.


## Root cause and implemented repair

Exact content-hash reuse explains why changed response bytes create new discovery batches. Before PR #926, top-level institution duplicate detection depended on JSON-path equality for `normalized_payload->slug`, and persistence wasn't serialized per source. The six historical rows all carry the same normalized GCU Lahore slug, so slug variants alone do not establish the historical production root cause; the precise prior failure path remains unverified.

PR #926 implemented source-row locking around the check-and-persist path and normalized identity checks for staged records. It retains snapshots, batches, evidence and duplicate observations for changed content and does not merge or approve rows. Regression tests cover repeated changed snapshots and different official identities with shared generic names.

The post-merge review identified one additional correctness defect worth fixing: the merged matcher returned a slug match before checking whether both known official names conflicted. That could misclassify two institution identities as the same staged or canonical identity. The follow-up PR for this continuation moves known official-name comparison ahead of slug fallback and adds a regression test for a shared slug with conflicting official names. It also broadens the existing read-only evidence-lineage target list to include row 353, which the current diagnostic script had omitted.

## Production GCU trace — limited private evidence

The post-merge read-only diagnostic runs confirm six GCU Lahore candidate rows (IDs 348–353) across batches 19–24, all associated with official source ID 27 for the HEC university identity page. Detailed source/evidence lineage is now returned for all six, including the previously missing row 353. Every row remains a candidate in a validated batch, with active source evidence, no identity decision, no promotion decision, no open conflicts and no validation errors. The canonical institution table remains empty. No row was modified.

The read-only lineage target list was expanded in PR #927, and run #40882355434 now returns complete lineage for all six rows. For row 353 specifically, the private log confirms batch 24, source 27, snapshot 13 and evidence 20, with active evidence tied to the official HEC GCU Lahore identity page. Its identity and promotion decision arrays are empty, there are no open conflicts, and the assertion remains a discovered candidate rather than a verified/published canonical record. The exact content hash, source excerpt and raw row payload remain in the private Actions log, not repeated in this public report; only the minimum lineage coverage needed to reconcile the gap is summarized here. The lineage query sets `mutation_performed=false`; the workflow's later nonzero exit is solely its revision-parity gate.

Sensitive row-level production IDs beyond those needed to identify this review scope, raw response content, evidence excerpts, package manifests and content hashes should remain in the private diagnostics/log view, not be copied into public documentation or pull requests.

### Source-response comparison

All six captured lineage records have distinct content hashes, establishing bytewise differences between the captured responses. Hash differences alone cannot distinguish substantive institution identity changes from incidental page content. The actual source bodies and evidence excerpts are not present in the sanitized output, and the diagnostic run exposed no downloadable artifacts. Therefore, the content-level comparison remains **BLOCKED** until an authorized operator retrieves the private snapshots/evidence or a validated review package and compares their substantive text.

## First GCU review package and human decision

Candidate for initial review: row 348 in batch 19. Its snapshot/evidence identifiers must be matched against the private manifest and CSV during the authorized review. The official source reference recorded for this candidate is the HEC GCU Lahore identity page.

The existing protected package download endpoint is:

- `GET /institutes/operations/discovery-packages/{batch}/manifest`
- `GET /institutes/operations/discovery-packages/{batch}/csv`

These are authenticated operator routes. They only download an existing private package and validate the batch/snapshot association plus CSV checksum; a 404 may mean the package is not present or its provenance cannot be resolved. The existing `InstituteDiscoveryReviewPackageService::create($batch, $snapshot, $evidenceId)` can build a package in private local storage when invoked through an authorized operator/console path.

The connected GitHub interface provided no authenticated production operator session or direct private-storage retrieval action. The package was not generated, downloaded or validated in this continuation. This is the exact blocker; no package path, credential, checksum, excerpt or evidence ID has been inferred.

An authorized operator must sign in through the existing operations UI and retrieve the manifest and CSV above. Validate the manifest's batch, snapshot and evidence IDs; official source URL; raw snapshot hash; CSV SHA-256; candidate identity; evidence excerpt; and `canonical_mutations_performed=false` plus `automatic_approval_performed=false`. If the package isn't already present, the authorized operator must use the existing service through the established protected console/admin workflow to create it, then retrieve and validate it. Do not commit the private package or raw snapshots to Git.

### Preliminary human-review position

**Identity decision: UNRESOLVED — no decision recorded.** There are zero canonical institutions at present, and the candidate payload names GCU Lahore. That is consistent with a possible new canonical institution but is not, by itself, sufficient to approve `approved_new`. The private package and source excerpt still need review. No identity decision, promotion or publication has been made or attempted.

Record an explicit `approved_new` or `approved_match` only after an authorized human reviews validated source evidence, identity lineage and campus/institution ambiguity. Promotion and publication are separate actions and remain blocked by all applicable verification, source-readiness, provenance, promotion, final-readiness, deployment-readiness, readiness-contract and end-to-end gates.

## Pilot source qualification

| Source/profile | Current posture | Decision |
|---|---|---|
| `gcu-lahore-institution-identity` | Enabled official HEC identity profile; six historical candidates remain unreviewed | Review/package preparation only; not cleared for promotion |
| Other HEC identity profiles | Disabled or not independently qualified in this evidence | Keep disabled; do not activate to meet the 5–10 target |
| Wider pilot | No verified set of 5–10 independently qualified enabled official identity sources | BLOCKED pending source qualification and explicit human decisions |

The pilot count remains **0 institutions cleared for promotion**. The first GCU review remains unresolved pending package retrieval and human review.

## Safety state

- No production discovery was invoked to test the merged repair.
- No production migration, SQL repair, schema change, import, identity decision, promotion, publication, source activation, cron change or scheduled-discovery change was performed.
- No canonical institution was created or published.
- No paid AI API call was made.
- The six historic candidate rows, batches, snapshots, evidence and decisions were not modified.
- The historical `import_batches.duplicate_rows` migration-ledger inconsistency was not touched.

## Acceptance matrix

| Criterion | Status | Evidence / remaining action |
|---|---|---|
| 1. Current `main` and merged PR #926 verified | PASS | Current `main` snapshot is `4cd42793471d259d8798feb1243e8bd993651f49`; includes merged PRs #926, #927 and #928 |
| 2. Post-merge CI/regression status verified | PASS for code CI; BLOCKED for live API contract and DB audit | Test #40883401953 and Laravel CI #40883402021 pass. Diagnostics #40883402357 verifies app-file parity but fails because `q=COMSATS` returns 404 HTML; latest integrity audit #40882355388 had stopped before DB query |
| 3. Duplicate prevention / identity collision tests pass | PASS | PR #927 is merged; Test #40882355334 and Laravel CI #40882355259 passed with 737 tests / 3,314 assertions, including the conflicting-official-name regression |
| 4. Six production rows have complete lineage or a documented gap | PASS — lineage complete | Read-only diagnostics #40882355434 and #40883402357 return source/snapshot/evidence lineage for rows 348–353, including row 353; sensitive identifiers/hashes remain in private logs |
| 5. Source-response differences classified from actual content | BLOCKED | Sanitized diagnostics do not expose bodies/excerpts; hashes alone cannot classify changes. Authorized private source comparison required |
| 6. Private review package validated or blocker documented | BLOCKED | Exact blocker: no authorized production operator session/private-storage access through connected tools. Operator must retrieve batch 19 manifest/CSV and verify checksum, provenance and safety flags |
| 7. First GCU review prepared without invented decision | PARTIAL | Review target and official source reference identified; preliminary identity remains unresolved pending package verification and human review |
| 8. No unauthorized production mutation, decision, promotion, publication or activation | PASS | All accessed production checks were read-only; no write action or deployment was invoked |

## Handoff

PR #927 merged the official-name/slug collision fix and row-353 lineage coverage. PR #928 merged the report-only update. Latest code CI passes. The latest diagnostics verifies application-file parity, and confirms row353 lineage, but the public API search smoke test fails because `q=COMSATS` returns HTML 404. The cause is not yet isolated; investigate hosting/access/WAF/rewrite logs rather than speculate or change application code without evidence. The latest live data-integrity database audit remains unverified because the preceding audit stopped before its query.

The remaining GCU blockers are unchanged: an authorized operator must retrieve and validate the private package for the first candidate; the original snapshot bodies/evidence excerpts must be compared to classify the six hash differences; and a human must then decide whether the candidate is a new institution, a match, or remains unresolved. No identity decision was recorded.

Phase 5A is **not complete** until the live API search contract failure is diagnosed and reverified, a post-parity read-only data-integrity audit passes, the private GCU package/source excerpts are reviewed, the human identity decision (or explicit rejection/unresolved disposition) is recorded through the governed workflow, and readiness gates are satisfied.
