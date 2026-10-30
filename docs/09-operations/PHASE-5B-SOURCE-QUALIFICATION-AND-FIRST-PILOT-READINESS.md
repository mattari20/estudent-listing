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


## Phase 5B.2 execution closeout — 2026-10-30

- PR #932 qualification implementation merged at `87df688b05fc2be85ed69389858a251074e9dc23`.
- PR #933 Hostinger parity/readiness diagnostics merged at `4c8b731eb8c5708c809322714d587e780add6ebd`. Test #41008742271 and Laravel CI #41008742366 passed on exact PR head `2eaf413162f4770cc612dfe3ee10ad97868e0238`.
- PR #934 corrected the private-disk path contract and merged at `5d6570b87fcd4e2f5a18f2cd90c8b4bc2409135d`. Test #41009088437, Laravel CI #41009088435 and Project Controller #41009088436 passed on exact head `3eeb386d8b48c659c3e784288376a4b031032c28`.
- Hostinger diagnostic #41008883135, run after PR #933 but before #934, verified application parity at deployed/expected `87df688b05fc2be85ed69389858a251074e9dc23`; reported the qualification command unavailable and exposed the duplicated private path.
- Fresh Hostinger diagnostic #41009230720, after PR #934 merge, reported: deployed checkout `4c8b731eb8c5708c809322714d587e780add6ebd`; expected application revision `5d6570b87fcd4e2f5a18f2cd90c8b4bc2409135d`; application parity mismatch. This is expected code drift because PR #934 contains an application command path change and production is not deployed to that revision.
- Latest diagnostic still reports `qualification_command=NOT_AVAILABLE`; the PR #932 command is unavailable in production. It therefore did not execute the HEC qualification command.
- Corrected storage check resolves local-disk target `storage/app/private/source-qualification`; `directory_exists=false`, `directory_writable=false` because it does not yet exist, `parent_directory_writable=true`, `inside_public_root=false`, `mutation_performed=false`. The configured local disk root is `storage/app/private`; after an authorized deployment, the first private write can create the missing `source-qualification` child below that writable root. No probe file or directory was created by diagnostics.
- PHP remains `/opt/alt/php85/usr/bin/php`, version `8.5.12`. Scheduler crontab listing remains unreadable to the diagnostic SSH user; cron execution remains unverified.
- **Live HEC qualification was not run.** Ten configured institution URLs were not requested by this execution. Live retrieval successes/failures, response tuples, extraction successes, identity-qualified sources and pilot-ready institutions are all NOT MEASURED (not zero-source outcomes).
- PR #934 and PR #932 are merged into GitHub main, but production is currently behind the app-code revision. The existing controlled deployment workflow is manual and defaults `confirm_deployment=false`. This task did not include explicit production deployment authorization, so no deploy was started.
- No production database reads proceeded past the parity gate for the automated integrity audit; no Source, snapshot, evidence, batch, row, decision, canonical record, approval, promotion or publication was changed. No profile was activated; all 20 added HEC profiles remain disabled.
- **Phase 5B.2 status: BLOCKED pending explicit production deployment authorization, controlled deployment, fresh read-only parity/command/storage/schema checks, and then bounded live HEC qualification.** Phase 5A remains OPEN.


## Execution update — 2026-10-30: Phase 5B.8 production Artisan registration diagnosis

- Current `main` after PR #940: `45fa7b817a62c1f9fb06257b0f23df06fd6ff956`. PRs #936–#940 are merged; PR #940 changed only `.github/workflows/hostinger-readonly-diagnostics.yml`.
- PR #940 exact-head Test run #41023475399 (workflow run #2323) and Laravel CI run #41023475538 (workflow run #2091) both passed. PR #940 merged as `45fa7b817a62c1f9fb06257b0f23df06fd6ff956`. No application deployment is required for this workflow-only change.
- Previous production Hostinger diagnostics run #41018560655 established: PHP executable `/opt/alt/php85/usr/bin/php`; native `artisan list --raw --no-ansi` exit 0; `institutes:qualify-official-sources` was NOT_LISTED; command-specific help was not run; Composer `class_exists` check returned AVAILABLE (exit 0).
- The same previous run's private storage probe passed: directory exists and is writable; `inside_public_root=false`; temporary probe created, read back and deleted; no persistent application data or database mutation. This probe is not repeated by PR #940.
- Deployed `bootstrap/app.php` was inspected on current main. It uses `Application::configure(...)->withCommands([__DIR__.'/../app/Console/Commands'])`. The command source on main declares `App\\Console\\Commands\\QualifyOfficialInstituteSources` extending `Illuminate\\Console\\Command`. These are source facts, not a substitute for runtime kernel evidence.
- PR #940 replaces the earlier unreliable heredoc diagnostic with a PHP 8.5 `-r` inspection, captures stdout/stderr separately, preserves exit status, emits versioned/timestamped JSON, validates required fields using the same resolved PHP executable, records the actual Artisan registry names and class reflection metadata, and cleans temporary output files. It does not run HEC requests or repeat the storage probe.
- **Production kernel inspection remains PENDING.** The connector can retrieve PR-triggered workflow runs but does not expose a list of push-triggered workflow runs. Therefore the post-merge Hostinger run ID and its output could not be retrieved in this session. Do not claim the new runtime inspection succeeded or infer a root cause from source inspection alone.
- No command help was available in the previous run because the command was absent from the native registry. Do not run qualification or HEC retrieval until a fresh production report proves registration and bounded help options.
- No application-code changes, deployment, Composer rewrite, migration, SQL write, normal discovery, source activation, snapshots/evidence, import batches/rows, human identity decisions, approvals, promotions, publications, GCU row changes (#348–353), or HEC profile activation occurred. All 20 additional HEC profiles remain disabled.
- Next gate: inspect the latest **Hostinger Read-only Diagnostics** push run on `main` after PR #940. Require `qualification_kernel_inspection_json=VALID` and review its JSON for framework version, kernel class/bootstrap, configured command directory, file readability, Composer reflection, parent class, and `artisan_command_registered`. Then compare that runtime registry with native `artisan list`; do not change application code unless the report proves an application defect.


## Execution update — 2026-10-30: Phase 5B.9 temporary-script diagnostic and partial production evidence

- Current `main` at the time of this update: `60c54ebe68faf054e530a13c1bd36e3048fbb31e`. PR #940 merged at `45fa7b817a62c1f9fb06257b0f23df06fd6ff956`; PR #941 merged at `169cefa1febdec669c06d9318d408e442d513c50`; PR #942 merged at `60c54ebe68faf054e530a13c1bd36e3048fbb31e`.
- Run #41023923217 logs confirmed the previous multiline `php -r "$KERNEL_CODE"` failed with exit 255 and emitted no JSON. Independent checks on that production run: PHP 8.5.12; native Artisan list exit 0; qualification command NOT_LISTED; Composer autoload AVAILABLE.
- PR #942 replaced the multiline shell-variable PHP construction with a fixed temporary PHP script under a unique `/tmp/estudent-kernel.*` directory, invoked directly with `/opt/alt/php85/usr/bin/php`. Output and stderr are separate; JSON validation uses the same executable; temporary files are removed on exit/signals; diagnostic output is versioned/timestamped. PR #942 exact-head Test #41034152117 (run #2332) and Laravel CI #41034152267 (run #2100) passed. This was workflow-only; no production application deployment required.
- First post-merge Hostinger run #41034289943 reached production using PHP 8.5.12. Native Artisan list exit 0 and reported NOT_LISTED; Composer autoload exit 0 and AVAILABLE. The temporary-file kernel inspector exited 31, wrote a JSON file, and had empty stderr. It did not produce a valid report because its own PHP script treated the absent qualification command as a diagnostic process failure. The workflow's cleanup trap then removed the validation stderr file before its hash was emitted. Therefore no definitive kernel registry report was obtained yet; this is a diagnostic control-flow defect, not proof of a Laravel runtime failure.
- Workflow-only follow-up PR #943 changes the inspector to emit the runtime report with exit 0 when bootstrap/kernel inspection completed even if the command remains absent; the shell continues to fail if the script process fails, the report is missing, or JSON validation fails. Exact-head Test #41034456194 (run #2334) and Laravel CI #41034456260 (run #2102) passed. PR #943 is open at head `2e0325488f650f63609a2e0f1f8697de8cf3e9c6`; merge and post-merge Hostinger run are still pending at this checkpoint.
- Do not infer a registration root cause from the incomplete #41034289943 report. Production status remains: native Artisan does not list the command; Composer autoload resolves the class; actual kernel class, runtime discovery path and registry report are still not captured.
- Prior private storage verification from run #41018560655 remains successful (outside public root, directory writable, temporary write/read/delete passed). Not repeated.
- Command source signature remains `institutes:qualify-official-sources` with `--stage=hec-universities|hec-campuses|provincial`, `--timeout`, `--connect-timeout`, `--delay-ms`, and optional `--output`. This is source inspection only; production help has not yet been verified. The enabled GCU profile appears within the HEC universities stage configuration, so do not execute the entire stage as a GCU-only request.
- GCU qualification remains BLOCKED pending valid runtime JSON, command help, safe bounded source-scope execution path and private output confirmation. No HEC retrieval was executed.
- No application-code changes, deployments, Composer writes, migrations, SQL writes, normal discovery, source activation, snapshots/evidence, imports, human identity decisions, approvals, promotions, publications, or changes to GCU historical rows #348–353. All 20 additional HEC profiles remain disabled. The separate `institution_field_approvals` schema warning remains outside this task.


## Phase 5B.10 execution checkpoint — 2026-10-30

### Production Artisan discrepancy — resolved

- PR #944 merged: `89d7599d862438fecad3ad0a06a21789fdb3578f`. It replaces `Artisan::has()` with `array_key_exists()` against the already retrieved registry and captures native raw command listing.
- PR #945 merged: `16f848d0c1670e43d7b1afe846bbd5d2fb42e189`. It changes native-list matching to compare the first whitespace-delimited command-name column rather than requiring the entire padded command/description line to equal the command name.
- Production Hostinger run #41046403222 completed successfully. PHP 8.5.12; native `artisan list --raw --no-ansi` exit 0; exact command first-column match; command AVAILABLE; native `artisan help institutes:qualify-official-sources --no-ansi` exit 0; all existing options `stage`, `timeout`, `connect-timeout`, `delay-ms`, and `output` present. Kernel JSON valid; `artisan_command_registered=true`; no diagnostic PHP error; Laravel 13.33.0; registry and kernel-loaded counts both 157; command class extends `Illuminate\\Console\\Command`; deployed application-code parity matched.
- Root cause was solely false-negative diagnostic matching: Symfony/Laravel native raw listing emits the command name in a padded first column followed by a description. Previous `grep -Fxq` tested the entire line. No application registration repair was needed.

### GCU-only filter implementation

- PR #946 merged: `b93e88aca7c645e43603b9c32d6447c76d83e913`.
- Adds `--source=<configured-id>` to `institutes:qualify-official-sources`. The ID is matched within the selected stage and exactly one matching configured entry is required; invalid or ambiguous IDs exit before any HTTP request or report write. The private report records the selected `source_filter`.
- Existing configured GCU source ID is `gcu-lahore`, mapped to profile `gcu-lahore-institution-identity` and the official HEC GCU Lahore identity page. The 10-entry `hec-universities` stage is not a GCU-only operation without this filter.
- Regression tests cover selecting only GCU from a multi-source fixture, a single HTTP request to the GCU fixture URL, unchanged counts for the relevant database tables, false database/discovery mutation markers in the report, and invalid-ID rejection with no HTTP request or report write.
- Exact-head Test run #41046964231 passed: 744 tests, 3,372 assertions. Exact-head Laravel CI run #41046964133 passed, including formatting checks.

### Production deployment gate — still pending

- Post-merge Hostinger run #41047170267 executed the read-only diagnostics successfully through kernel inspection but correctly failed its final application-code parity gate. Expected application-code revision: `b93e88aca7c645e43603b9c32d6447c76d83e913`. Deployed application-code revision: `16f848d0c1670e43d7b1afe846bbd5d2fb42e189`. The deployed code therefore does not yet contain `--source`; the production help verified by run #41046403222 is the pre-filter help and must not be treated as verification of the new option.
- No live GCU request was sent. The GCU-only filter has passed CI, but production deployment and post-deployment verification are still required.
- Deployment must use the existing [Controlled Hostinger Code Deployment workflow](https://github.com/mattari20/estudent-listing/actions/workflows/controlled-hostinger-code-deploy.yml), manually dispatched with `confirm_deployment=true`. After successful deployment, repeat native `artisan list`, run native `artisan help` and verify `--source` is present before invoking qualification.
- Planned one-source command after those gates:
  ```bash
  /opt/alt/php85/usr/bin/php artisan institutes:qualify-official-sources \
    --stage=hec-universities \
    --source=gcu-lahore \
    --timeout=15 \
    --connect-timeout=5 \
    --delay-ms=0 \
    --output=source-qualification/gcu-lahore-phase-5b10-20261030.json \
    --no-ansi
  ```
- The report destination remains on the already verified private local disk under `storage/app/private/source-qualification/`. Prior Hostinger run #41018560655 established that this location is outside the public root and writable; the private write/read/delete probe is deliberately not repeated. No report has been generated by this Phase 5B.10 task yet.
- The GitHub connection can inspect and update repository files and workflow runs, but does not expose a workflow-dispatch action. Therefore the controlled deployment has not been started from this session. Do not claim deployment, new-option production help, or the first GCU report until the manually confirmed workflow and subsequent Hostinger verification complete.

### Safety state

- No HEC request or GCU qualification run was executed in Phase 5B.10.
- No database writes, migrations, SQL repairs, discovery rows, source snapshots/evidence, imports, identity decisions, approvals, promotions, publications, or changes to historical GCU rows 348–353 occurred.
- All 20 additional HEC profiles remain disabled.
- The separate `institution_field_approvals` schema warning remains an independent tracked blocker and was not repaired.
- **GCU pilot status: BLOCKED pending controlled production deployment, production `--source` help verification, and the first one-source GCU report.**


## Phase 5B.14 — governed one-time GCU qualification runner (2026-10-30)

- Added dedicated manual workflow `.github/workflows/hostinger-gcu-single-source-qualification.yml` on branch `feat/phase5b14-gcu-qualified-runner-20261030`. It is `workflow_dispatch` only, takes one required boolean confirmation defaulting false, has read-only repository permissions, a dedicated non-cancelling concurrency group, and bounded job/SSH timeouts.
- Scope is hard-coded: Hostinger Institutes directory `/home/u537436044/domains/estudent.pk/public_html/institutes`; PHP `/opt/alt/php85/usr/bin/php`; stage `hec-universities`; source `gcu-lahore`; report `storage/app/private/source-qualification/gcu-lahore-phase-5b14-20261030.json`. No user-supplied shell, paths, stage, source or URL values are accepted.
- The SSH step is gated by explicit `confirm_gcu_qualification=true`; before the source request it verifies Artisan exists, the production checkout is a Git worktree, application-code parity against the approved revision resolved from main, native command help and all required options, ten stage entries, exactly one GCU ID/profile mapping, exact configured HEC URL, command source invariants, local disk root, private/public path separation and absence of an existing fixed report.
- The live command appears once, with fixed options and no retry loop or alternate source. Its output is captured; the report must be non-empty, valid JSON, filtered to `gcu-lahore`, and contain exactly one matching result before upload. The report remains in private storage with restrictive file permissions; an artifact copy is uploaded as `gcu-lahore-qualification-report` for seven days.
- Workflow development/CI does not trigger this workflow and has made no HEC/GCU request. The report path is unique to this runner; it refuses to overwrite it. The artifact upload step runs only after explicit confirmation.
- Validation caveat: do not treat a successful extraction as final identity qualification. The current qualification command reports source retrieval/extraction; canonical/staged identity cross-check and human review remain separate.
- This workflow is a controlled execution mechanism, not proof a live request has happened. Actual qualification requires a separate manual dispatch after review/merge, with confirmation true.
- Phase 5B.14 implementation is pending exact-head workflow validation, Laravel CI/Test, review and merge. Phase 5B.13 code safety audit remains the prerequisite baseline; no new production execution occurred.
