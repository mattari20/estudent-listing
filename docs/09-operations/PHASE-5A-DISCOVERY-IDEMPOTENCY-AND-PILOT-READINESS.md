# Phase 5A — Discovery Idempotency and First Pilot Readiness

Last reconciled: 2026-10-29  
Verified `main` baseline at continuation start: `90fc06164da925ac5ef5d0cfad31ec7f734ae78c`  
Merged repair: [PR #926](https://github.com/mattari20/estudent-listing/pull/926) — merge commit `90fc06164da925ac5ef5d0cfad31ec7f734ae78c`

## Post-merge repository verification

The live `main` ref was read from GitHub and equals PR #926's merge commit. PR #926 is closed and merged. The merged commit's parent is the pre-merge `main` commit `1b5202c35346c017de1c1dfb8d889d4b7ef6e15d`.

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

## Root cause and implemented repair

Exact content-hash reuse explains why changed response bytes create new discovery batches. Before PR #926, top-level institution duplicate detection depended on JSON-path equality for `normalized_payload->slug`, and persistence wasn't serialized per source. The six historical rows all carry the same normalized GCU Lahore slug, so slug variants alone do not establish the historical production root cause; the precise prior failure path remains unverified.

PR #926 implemented source-row locking around the check-and-persist path and normalized identity checks for staged records. It retains snapshots, batches, evidence and duplicate observations for changed content and does not merge or approve rows. Regression tests cover repeated changed snapshots and different official identities with shared generic names.

The post-merge review identified one additional correctness defect worth fixing: the merged matcher returned a slug match before checking whether both known official names conflicted. That could misclassify two institution identities as the same staged or canonical identity. The follow-up PR for this continuation moves known official-name comparison ahead of slug fallback and adds a regression test for a shared slug with conflicting official names. It also broadens the existing read-only evidence-lineage target list to include row 353, which the current diagnostic script had omitted.

## Production GCU trace — limited private evidence

The latest post-merge read-only diagnostic log confirms six GCU Lahore candidate rows (IDs 348–353) across batches 19–24, all associated with official source ID 27 for the HEC university identity page. Every row is still a candidate in a validated batch, with active source evidence, no identity decision, no promotion decision, no open conflicts and no validation errors. The canonical institution table remains empty. No row was modified.

The current diagnostic script's source/evidence lineage query is explicitly hard-coded to rows 348–352, while a later summary row trace includes row 353. That explains why row 353's snapshot/evidence identifiers were absent from the detailed lineage section; it was an audit coverage defect, not proof that the row lacks provenance. The follow-up changes the existing read-only target list to include 353. After this PR merges, the already-existing Hostinger Read-only Diagnostics workflow should emit row 353's actual lineage in its next run; it may still finish with a nonzero conclusion until the production application revision is separately aligned.

Sensitive row-level production IDs beyond those needed to identify this review scope, raw response content, evidence excerpts, package manifests and content hashes should remain in the private diagnostics/log view, not be copied into public documentation or pull requests.

### Source-response comparison

The diagnostic shows distinct recorded content hashes for the five rows whose detailed lineage was returned, proving only that the captured bytes differ. The actual source bodies and evidence excerpts are not present in the sanitized output. Therefore, no evidence-based determination can yet classify those response differences as substantive institution identity changes or incidental page content. Keep this classification **UNVERIFIED** until an authorized operator compares the private snapshots/evidence.

## First GCU review package and human decision

Candidate for initial review: row 348, batch 19, snapshot 8, evidence 15 (lineage verified in the earlier bounded report). The official source reference recorded for this candidate is the HEC GCU Lahore identity page.

The existing protected package download endpoint is:

- `GET /institutes/operations/discovery-packages/19/manifest`
- `GET /institutes/operations/discovery-packages/19/csv`

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
| 1. Current `main` and merged PR #926 verified | PASS | `main` equals merge commit `90fc06164da925ac5ef5d0cfad31ec7f734ae78c`; PR #926 is merged |
| 2. Post-merge CI/regression status verified | PASS for code CI; BLOCKED for production parity | Test #40880064515 and Laravel CI #40880064375 pass; operations runs #40880064429 / #40880064420 show deployed revision mismatch |
| 3. Duplicate prevention / identity collision tests pass | PASS on merged head; FOLLOW-UP REPAIR OPEN | 736 tests / 3,312 assertions passed. Follow-up addresses the verified official-name-versus-slug collision defect and adds a targeted regression case |
| 4. Six production rows have complete lineage or a documented gap | PARTIAL | Row-level status and decision state verified for all six; detailed evidence section omitted row 353 due hard-coded target list. Follow-up includes row 353 in that read-only query; next diagnostic must confirm the actual IDs |
| 5. Source-response differences classified from actual content | BLOCKED | Sanitized diagnostics do not expose bodies/excerpts; hashes alone cannot classify changes. Authorized private source comparison required |
| 6. Private review package validated or blocker documented | BLOCKED | Exact blocker: no authorized production operator session/private-storage access through connected tools. Operator must retrieve batch 19 manifest/CSV and verify checksum, provenance and safety flags |
| 7. First GCU review prepared without invented decision | PARTIAL | Review target and official source reference identified; preliminary identity remains unresolved pending package verification and human review |
| 8. No unauthorized production mutation, decision, promotion, publication or activation | PASS | All accessed production checks were read-only; no write action or deployment was invoked |

## Handoff

Follow-up PR for the confirmed official-name/slug collision and row-353 lineage coverage is being prepared against the verified `main` SHA. Its required checks must pass before merge. Then inspect the next existing read-only diagnostics run for row 353's actual evidence/snapshot lineage. Production deployment remains a separate explicit decision. The first human identity decision must wait for authorized private package retrieval and review.

Phase 5A is **not complete** until deployment parity is addressed through an explicitly authorized path, row 353's actual lineage is confirmed, the private GCU package/source excerpts are reviewed, and the human identity decision (or explicit rejection/unresolved disposition) is recorded through the governed workflow.
