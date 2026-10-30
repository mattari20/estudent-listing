# Phase 5B — Official Source Qualification and First Institution Pilot Readiness

**Status:** IN PROGRESS — no source profile activation or production pilot authorization  
**Inspection date:** 2026-10-30  
**Repository baseline:** `4cd42793471d259d8798feb1243e8bd993651f49`  
**Prior completed repository milestones:** PRs #926, #927 and #928 (all merged)

## Executive status

This report separates source ownership/authority evidence, synthetic application tests, live application retrieval qualification, and production readiness. These are not interchangeable.

- There are 21 configured top-level HEC institution identity profiles. Twenty additional profiles added by PR #918 remain disabled; the pre-existing GCU Lahore identity profile is enabled.
- The HEC source qualification work produced 21 identity profiles and an isolated deterministic extraction test with synthetic HTML. It did **not** capture any direct live application HTTP status/content-type/raw-body SHA-256 tuples. The previous source report explicitly records 18 browser-rendered HEC pages, 3 direct page-open timeouts, and 0 application-level retrieval tuples.
- A separate HEC recognized-campus directory should be qualified as a parent/campus cross-check before institution identity or campus records are proposed. A campus must not be promoted as a separate parent institution solely from a campus-list entry.
- The scheduled discovery workflow is configured hourly at minute 15, but recent October 30 schedule execution evidence has not been retrieved in this audit.
- The current production parity gate remains blocked. The October 29 Hostinger job reported deployed checkout `90fc06164da925ac5ef5d0cfad31ec7f734ae78c` versus expected app-code revision `b40ca2048e1a3b702a48f57233339e4c390e766a`. The live integrity audit stopped before its database query.
- No profile has been newly activated; no production candidates were imported by this phase; no canonical Institution was promoted or published; no production deployment, migration, cron change, or data mutation was performed.

## Current source inventory and qualification levels

Qualification labels:

- **Authority lead:** authoritative government/regulator ownership and relevant identity scope are evidenced by the official public directory.
- **Profile fixture-tested:** the configured profile has been exercised using synthetic HTML, fixture hashes, and isolated application tests.
- **Live application-qualified:** the application SafeSourceHttpRetriever has captured the actual HTTP status, content type, final URL and raw response fingerprint from the official endpoint, with deterministic extraction evaluated in memory and its result stored in a private qualification report. This read-only qualification does not create a SourceSnapshot or other database record.
- **Pilot-ready:** live qualification, duplicate/campus resolution, evidence provenance, approval workflow, deployment/schema readiness, and bounded human review are all satisfied.

No source in this report is marked live application-qualified or pilot-ready.

| Priority | Source candidate | Authority / scope | Current evidence | Remaining gate |
|---:|---|---|---|---|
| 1 | HEC recognized universities directory | National regulator; university identity/recognition | Authority lead; 21 configured institution profiles fixture-tested | Live retrieval, raw status/content type/hash, extractor qualification, source freshness |
| 2 | HEC recognized campuses directory | National regulator; campus/parent relationship | Official campus directory identified | Live retrieval; parent-university mapping; campus-vs-institution collision tests |
| 3 | Punjab Higher Education Department public universities | Provincial government; public university listing | Official directory identified | Live retrieval; compare with HEC identity records and resolve duplicates |
| 4 | Sindh Universities and Boards Department | Provincial government; universities/institutes listing | Official directory identified | Live retrieval; distinguish universities from institutes/boards and campuses |
| 5 | Sindh Higher Education Commission | Provincial higher education authority | Official listings identified | Live retrieval; corroborate scope and duplicate/alias behaviour |
| 6 | KP Higher Education Regulatory Authority (HERA) | Provincial regulator; university/DAI list | Official directory identified | Live retrieval; qualification of charter/status and parent/campus relationships |
| 7 | ICT Administration education directory | Government directory; local education listings | Official directory identified | Determine university identity coverage and source authority per record; live retrieval |
| 8 | Official university websites | Primary institutional source; names, address, official details | Existing profile candidates plus HEC identity cross-check | Per-domain live retrieval, content structure, provenance, freshness, and identity conflict checks |

### Official source references

- HEC recognized universities: https://www.hec.gov.pk/english/universities/pages/recognised.aspx
- HEC recognized campuses: https://www.hec.gov.pk/english/universities/Pages/DAIs/HEC-recognized-Campuses.aspx
- Punjab HED public universities: https://hed.punjab.gov.pk/public-universities
- Sindh Universities and Boards Department: https://universitiesboards.sindh.gov.pk/universitiesinstitutes
- Sindh HEC: https://sindhhec.gov.pk/academics-detail/
- KP HERA university list: https://herakp.gov.pk/pages/university_list.php
- ICT Administration education and health directory: https://ictadministration.gov.pk/education-and-health/

These references establish public authority leads, not successful application retrieval.

## First controlled pilot candidate set

This is a research shortlist, not a qualified/approved import list.

| Proposed institution | Existing profile | Preliminary identity evidence | Live app qualification | Current disposition |
|---|---|---|---|---|
| Government College University, Lahore | `gcu-lahore-institution-identity` | HEC profile inspected; historical candidates 348–353 unresolved | Not completed in this audit | Human evidence review and production parity first |
| Lahore University of Management Sciences | `hec-lums-institution-identity` | HEC search-index text; direct page-open timeout in prior review | Not captured | Keep disabled |
| University of the Punjab | `hec-punjab-institution-identity` | HEC search-index text; direct page-open timeout in prior review | Not captured | Keep disabled |
| University of Engineering & Technology, Lahore | `hec-uet-lahore-institution-identity` | HEC page rendered in prior browser inspection | Not captured | Keep disabled |
| University of Peshawar | `hec-peshawar-institution-identity` | HEC page rendered in prior browser inspection | Not captured | Keep disabled |
| NED University of Engineering & Technology | `hec-ned-institution-identity` | HEC page rendered in prior browser inspection | Not captured | Keep disabled |
| Quaid-i-Azam University | `hec-qau-institution-identity` | HEC page rendered in prior browser inspection | Not captured | Keep disabled |
| National University of Sciences & Technology | `hec-nust-institution-identity` | HEC page rendered in prior browser inspection | Not captured | Keep disabled |
| University of Central Punjab | `hec-ucp-institution-identity` | HEC page rendered in prior browser inspection | Not captured | Keep disabled |
| Government College University, Faisalabad | `hec-gcuf-institution-identity` | HEC page rendered in prior browser inspection | Not captured | Keep disabled |

The two GCU names refer to different cities and must not be collapsed because of the shared generic phrase “Government College University”. The merged PR #927 rule correctly refuses to let a matching slug override conflicting known official institution names.

## Automatic discovery implementation review

Existing implementation inspected:

- `InstituteDiscoveryAgentService`: bounded HTTP retrieval through `SafeSourceHttpRetriever`, content hashing, immutable snapshots, evidence and provenance, transaction/row-lock around generic candidate persistence, duplicate classification and private review package generation.
- `InstituteIdentityDiscoveryService`: deterministic top-level identity extraction, snapshot reuse, evidence, batch/row/Assertion provenance, identity resolution candidates, private review package; no canonical Institution creation/update, approval, promotion, or publication.
- `BootstrapInstituteAgent`: iterates configured profiles, skips disabled profiles, disables profiles requiring missing canonical parents, registers Sources and monitoring policies, runs the institution-specific identity service or generic program/campus agent, captures per-source failures and continues independent sources.
- `SafeSourceHttpRetriever`: validates URLs and each redirect, blocks unsafe redirect targets and HTTPS downgrade, pins resolved DNS address, and limits redirects.
- `scheduled-institute-discovery.yml`: hourly at minute 15; checks application-code parity and official-source-refresh schema readiness; blocks if paid AI extraction is enabled; compares canonical counts before/after; checks due-policy run outcomes; logs zero canonical mutation/approval/promotion/publication.

### Repository-side gap identified

The live scheduled bootstrap uses the separate `InstituteIdentityDiscoveryService` for top-level Institution identity profiles. Unlike the generic agent service, its original staging method queried existing batches and inserted evidence/batches/rows without wrapping the persistence sequence in a transaction and source-level `lockForUpdate`.

This leaves a race window: simultaneous scheduler/manual runs against the same source can both observe no matching prior batch and create duplicate candidate batches. The historical identity tests exercised the generic agent path rather than directly proving the separate service's persistence boundary.

**Implemented in merged PR #929** ([merge commit `cee61fe0c0128e278b30b495138a73c8147e93e6`](https://github.com/mattari20/estudent-listing/commit/cee61fe0c0128e278b30b495138a73c8147e93e6)):
- identity candidate reuse/staging is wrapped in a database transaction and locks the source row;
- changed source content retains its immutable snapshot, evidence and batch;
- the service calls the shared `ImportDuplicateDetectorService::findInstitution()` so an already-staged identity is classified for duplicate review across batches;
- the shared detector preserves PR #927's official-name precedence: a shared slug or generic display name does not override two known conflicting official names;
- no canonical Institution is created, approved, promoted or published by this flow.

PR #929 passed Test (#41005553735), Laravel CI and formatting (#41005553794), and Project Controller (#41005553740), all on PR head `dc96305ed9ecd5da3ec93be10e4b17f4d25bc5f8`. Test and Laravel CI each reported 737 tests and 3,314 assertions.

**Regression-test follow-up:** Post-merge inspection found that the final merged test file did not contain the direct-service changed-snapshot test (the last test consolidation commit had removed it). A focused follow-up branch, `test/phase5b-identity-changed-snapshot-regression-20261030`, restores this missing coverage and adds a conflicting-official-name regression for the duplicate-detection path. It must pass fresh CI before its test coverage can be marked verified.

The PR #929 suite proves repository-wide tests, but it is not counted as direct regression coverage for the dedicated identity service until the follow-up passes.

## Production and historical GCU review gates

The six historical GCU candidates (rows 348–353) have complete source/snapshot/evidence lineage in the private October 29 diagnostics. Their captured content hashes are distinct, but bytewise differences cannot classify substantive institution identity changes.

Required operator review:
1. Use an authorized production operator session to retrieve batch 19's protected manifest and CSV.
2. Validate CSV SHA-256 against the manifest and verify source, batch, row, snapshot, evidence, original URL, and captured timestamps.
3. Compare actual source snapshots/evidence excerpts across the six historical rows. Separate changes to institution name, official title, location, recognition or institution identity from incidental footer, analytics, timestamps and page layout changes.
4. Prepare the row-348 first human-review decision with all evidence attached.
5. Have an authorized human explicitly choose match, new institution, reject, or unresolved in the existing governed workflow.
6. Do not promote/publish through this Phase 5B report.

Current blocker: this connected session has GitHub repository access but no authenticated Hostinger/operator session or private production storage access. No package content was downloaded or inferred.

## Pilot acceptance checklist

| Gate | Current status |
|---|---|
| Current main and #926–#928 verified | PASS |
| Discovery/snapshot/evidence/staging core implementation exists | PASS |
| Institution identity duplicate matcher rejects official-name conflicts | PASS |
| Synthetic 21-profile deterministic extraction test exists | PASS (fixture only) |
| Live application HTTP status/content-type/fingerprint for HEC sources | BLOCKED — 0 tuples captured |
| Live extractor outcome per source | BLOCKED |
| HEC campus parent mapping and campus-collision check | NOT VERIFIED |
| Historical GCU six-row snapshot body comparison | BLOCKED |
| Batch 19 private package checksum/provenance validation | BLOCKED |
| Human GCU identity disposition | PENDING |
| Hostinger deployed app revision parity | BLOCKED |
| Fresh scheduled workflow execution on current main | NOT VERIFIED |
| 5–10 institution controlled pilot | NOT STARTED |
| Canonical import/publication | NOT AUTHORIZED |

## Pilot readiness estimate

**30% — gate-weighted engineering readiness estimate, not an imported-record percentage.**

Rationale: substantial governed discovery, snapshot/evidence, duplicate matching, private review and approval plumbing exists; the 21-source configuration has synthetic fixture coverage. However, none of the sources has a captured live application retrieval tuple, zero new sources are cleared for activation, the historical first GCU review is unresolved, and production revision parity plus current scheduler execution evidence are outstanding.

## Required next actions

1. Review/merge the accompanying identity-staging serialization PR only after Test/Laravel CI and focused regression checks pass.
2. Resolve the explicit production deployment authorization and then run the existing controlled deployment workflow; do not deploy merely to clear a report gate.
3. Run the existing read-only diagnostics after the authorized deployment to verify deployed app-code parity and current schema readiness.
4. In a separate authorized operator session, retrieve and validate the private GCU batch-19 manifest/CSV and compare six snapshot bodies. Record a human decision without automatic promotion.
5. Build and run the isolated read-only qualification command through the existing SafeSourceHttpRetriever. It must write only a private execution report, not source retrieval/snapshot/evidence database rows. Use the report's actual HTTP status/content type/final URL/raw hash and in-memory extraction outcomes to decide the next qualification gate.
6. Qualify HEC university and campus directories plus relevant provincial sources. Activate profiles only after all individual source gates pass and a separate explicit authorization is given.
7. Execute a bounded 5–10 identity-only pilot into private review staging, then inspect duplicate and campus outcomes before any human approval/import decision.

**Phase 5A remains open. Phase 5B remains in qualification/preparation, not completed. No production source profile activation, candidate import, promotion or publication is authorized by this report.**


## Phase 5B.1 execution update — 2026-10-30: isolated read-only qualification capability

- Reconciled PR #931: all three PR checks (Test #41005951390, Laravel CI #41005951466, Project Controller #41005951469) completed successfully on head `423c68657b099b633e153a7196924bd3fd86067c`. PR #931 merged at `b16144c73592ba4217d7d533a410ca91fef263a7`; this is the verified `main` baseline for Phase 5B.1.
- Confirmed PRs #926–#930 merged. PR #930 direct-service changed-snapshot and conflicting-official-name tests are present on main; its Test and Laravel CI each passed 739 tests / 3,332 assertions.
- Added `config/institute_source_qualification.php` with staged inventories: the ten named HEC identity candidates, HEC recognized campuses, and five provincial/ICT source leads. This inventory is separate from monitoring profiles and does not activate or modify them.
- Added `institutes:qualify-official-sources` with bounded timeout/connect-timeout/delay options. It calls the existing `SafeSourceHttpRetriever`, records validated final URL, HTTP status, content type, raw SHA-256, response bytes, retrieval duration and timestamp, and invokes the existing deterministic extractor in memory. Reports contain no raw source body and are written only to private local storage.
- Added `SafeSourceHttpRetriever::getWithMetadata()`; the existing `get()` API remains compatible and delegates to the same guarded redirect/DNS-pinned retrieval path.
- The command does not call Eloquent models, database services, bootstrap, discovery, monitoring, snapshot/evidence, import, approval, or canonical-write services. Automated regression tests assert unchanged counts across existing discovery/source/canonical tables and verify HTTP fingerprint/extraction output, wrong content type handling and unsafe URL blocking.
- HEC campus extraction currently returns labels only. Parent-university relationships are explicitly marked `HUMAN_REVIEW_REQUIRED`; no campus is asserted as an independent institution.
- The five provincial/ICT endpoints have live-retrieval inventory entries, but no dedicated deterministic directory extractor exists for those formats yet. Even after a successful GET, those sources are classified BLOCKED pending a source-specific extractor and authority/coverage rules; no guessed university extraction is permitted.
- **Live execution not yet performed:** this connected session can inspect and modify GitHub repository content and CI, but it does not provide a checked-out application runtime or authenticated Hostinger shell to execute the new Laravel command against official endpoints. The new command must first pass CI; a bounded live run remains an operator/runtime action. No actual live HTTP tuples are claimed in this update.
- Current Phase 5B.1 counts before live execution: sources configured 16 (10 HEC identity pages + 1 HEC campus directory + 5 provincial/ICT directories); live retrieval successes 0; retrieval failures 0 observed by this command (not run); extraction successes 0; identity-qualified sources 0; campus-parent conflicts 0 measured (parent mapping remains explicitly unverified); pilot-review-ready institutions 0; blocked sources 0 measured by live run. These are NOT live outcomes.
- No database writes, source/snapshot/evidence/import rows, profile activation, production discovery, migration, deployment, approval, promotion, publication or scheduler change occurred.
- Phase 5B.1 remains IN PROGRESS until the implementation PR checks pass and a bounded live execution captures actual per-source evidence. Phase 5A remains OPEN.


## Phase 5B.2 post-merge verification — 2026-10-30

- PR #932 is confirmed merged as `87df688b05fc2be85ed69389858a251074e9dc23`. Current main subsequently advanced with PR #933, merged as `4c8b731eb8c5708c809322714d587e780add6ebd`.
- PR #933 corrected the Hostinger parity diagnostics and added read-only checks for the qualification command and private report storage. Its Test run #41008742271 and Laravel CI #41008742366 passed on exact PR head `2eaf413162f4770cc612dfe3ee10ad97868e0238`. The post-merge Test, Laravel CI, frontend build, Project Controller, command orchestrator and Public Site Monitor workflows were started on #933 merge SHA; the final Test/Laravel CI and Hostinger diagnostic status should be recorded from their completed run IDs.
- Post-merge Hostinger diagnostics #41008883135 reached the server and reported `APPLICATION_CODE_MATCH`: deployed revision and expected application revision both `87df688b05fc2be85ed69389858a251074e9dc23`. This is a read-only code-parity result, not evidence that a different version was deployed.
- The same diagnostic reported `qualification_command=NOT_AVAILABLE`. Therefore the production checkout does not contain the PR #932 qualification command. No qualification HTTP request was executed.
- Hostinger PHP is `/opt/alt/php85/usr/bin/php`, version `8.5.12`. Existing review-package storage is outside the public root and contains 12 private files. Cron listing remains unreadable to the SSH user (exit 127); schedule presence/execution is unverified.
- The first private-report path check exposed a path-contract defect: the application's `local` disk root is already `storage/app/private`, while the qualification command prepended another `private/` directory. The check consequently examined a nonexistent `storage/app/private/private/source-qualification` path and reported non-writable. PR #934 is being prepared to write relative to the private disk root and to validate the correct directory path. No directories or files were created during diagnostics.
- Actual live HEC qualification: **NOT RUN**. All ten configured HEC sources have no live HTTP status/content-type/final-URL/hash tuples from this phase. Do not count fixture tests as live retrieval. No source is identity-qualified or pilot-review-ready.
- No database rows, snapshots, evidence, batches, import rows, identity decisions, approvals, promotions, publications, source activations, migrations, deployments or scheduler changes were performed.
- The deployment workflow remains manual and defaults to `confirm_deployment=false`. Production deployment is not authorized by this task. A separate explicit deployment authorization is required before deploying PR #932 code to Hostinger; after an authorized deployment, rerun read-only parity/command/storage diagnostics, then execute the bounded HEC qualification command only if command availability and private report storage permissions are proven.
- Phase 5B.2 remains BLOCKED pending production deployment authorization, qualification command availability, and a writable private report directory. Phase 5A remains OPEN.
