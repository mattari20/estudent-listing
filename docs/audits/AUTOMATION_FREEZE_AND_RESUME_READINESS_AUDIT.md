# Institutes Directory — Automation Freeze and Resume Readiness Audit

**Audit date:** 2026-10-31  
**Repository:** `mattari20/estudent-listing`  
**Production branch:** `main`  
**Production URL:** https://estudent.pk/institutes/  
**Step:** 4 of the five-step finalization sequence  
**Scope:** repository-defined automation, its documented triggers and source-code side effects, plus a reversible freeze/resume proposal. This is an audit only.

## 1. Executive summary

### 1.1 Starting state

- Current `main` SHA: `b878416826bdf62c26ad27d040f254461d3cf126`.
- This is exactly the Step 3 audited SHA. There are **zero commits on main since the Step 3 baseline**.
- PRs #953, #954, #955 and #956 remain open and unmerged. PRs #911, #913 and #899 are also open with older base revisions.
- The main branch contains **35 GitHub Actions workflow definitions**.
- Eight workflows declare scheduled triggers.
- The repository defines two hourly Laravel scheduled commands in `bootstrap/app.php`.
- The actual Hostinger cron entry that may invoke Laravel's scheduler is **not verified**. No Hostinger SSH session or hPanel inspection was performed.
- The GitHub workflow activation/enabled state and a complete live in-progress run list could not be independently retrieved through the available read-only GitHub connection. YAML proves a trigger is configured in the repository; it does not prove that GitHub currently has that workflow enabled or that a run is active.

### 1.2 What should be preserved

Preserve normal GitHub CI/test validation, isolated MySQL migration validation, documentation validation, repository inspections, the fail-closed project controller/orchestrator, security/access controls, backups, audit history and private evidence retention.

Preserve approved read-only diagnostics only where their production filesystem effects are understood. Several workflows called “read-only” still write private reports, temporary files or Git metadata. In particular, the daily integrity audit, candidate audit, record-level audit and reset inventory fetch Git history inside the deployed Hostinger checkout. They are not filesystem-read-only.

### 1.3 What should be paused only after separate explicit authorization

The highest-priority future freeze target is the **GitHub scheduled governed discovery workflow** and, if present, the Hostinger cron entry that runs `php artisan schedule:run`. These are two independent execution planes for the same `institutes:bootstrap-agent` and `institutes:monitor-changes` commands.

Also consider temporarily pausing automated production diagnostics that mutate deployed Git metadata until their parity fetch is moved to an isolated temporary repository. Pause the daily offering-gap database preflight if its actual database grants cannot be proven SELECT-only.

No pause, disable, cancellation, dispatch, rerun or configuration change was performed in this audit.

### 1.4 Most important risks

1. **Duplicate execution risk:** GitHub Actions invokes `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly. Laravel schedules the same two commands hourly. GitHub concurrency and Laravel `withoutOverlapping(55)` are separate locks; no shared cross-runner lock is demonstrated.
2. **Live deployed Git metadata mutation:** several SSH workflows execute `git fetch` or `git fetch --unshallow` in the deployed checkout. This mutates `.git` refs/objects/shallow metadata even if application files and database rows are not changed.
3. **Real production DB/file writes from scheduled discovery:** bootstrap updates/creates source records and monitoring policies. Monitoring writes retrieval/snapshot/observation/evidence/intelligence/outcome records, may stage candidate batches and review packages, and may enqueue re-verification records. These are not canonical institution writes, but they are production persistence.
4. **Additional trigger chains:** a successful official-source refresh migration can automatically trigger scheduled discovery; successful deployment or migration can trigger official-source refresh readiness. Main pushes can trigger public monitoring, project orchestration, asset builds, CI and selected production diagnostics.
5. **Unknown Hostinger cron and worker state:** the repository does not prove whether Hostinger currently invokes Laravel's scheduler or runs a queue worker. Do not assume either absent or active.

**Overall decision:** a freeze plan is ready for owner review, but the freeze itself has not occurred. The first safe action after separate authorization must identify any active run and the actual Hostinger scheduler entry before pausing anything.

## 2. Exact starting revision, PRs and evidence boundary

### 2.1 Main revision

`main` was read at:

`b878416826bdf62c26ad27d040f254461d3cf126`

Commit: `fix: isolate Phase 5B.18 runtime diagnostic cleanup (#952)`  
Commit date returned by GitHub: `2026-10-31T06:51:27Z`.

A second read of `main` returned the same SHA. Therefore the compare range from the Step 3 audited SHA `b878416826bdf62c26ad27d040f254461d3cf126` to current `main` is zero commits.

### 2.2 Audit and diagnostic PRs

| PR | URL | Head SHA | State | Merge status | Notes |
|---|---|---|---|---|---|
| #953 | https://github.com/mattari20/estudent-listing/pull/953 | `f0031ae38c7e3650ef2c449316450be666c465f1` | Open | Unmerged; mergeable | Phase 5B.29 runtime failure-evidence repair; still not in main |
| #954 | https://github.com/mattari20/estudent-listing/pull/954 | `c23c52807cf881a17206721315789a7db98096e5` | Open | Unmerged; mergeable | Step 1 inventory |
| #955 | https://github.com/mattari20/estudent-listing/pull/955 | `ffc2dd06659f1bac9227d5ddb3a2213e55776896` | Open | Unmerged; mergeable | Step 2 technical audit |
| #956 | https://github.com/mattari20/estudent-listing/pull/956 | `1f0d9f814e427378c1fab136103ff990915b7097` | Open | Unmerged; mergeable | Step 3 data pipeline/AI audit |
| #913 | https://github.com/mattari20/estudent-listing/pull/913 | `20325bc49c6166ae8de529846230465770967475` | Open | Unmerged; older base | Hostinger diagnostic blocker documentation |
| #911 | https://github.com/mattari20/estudent-listing/pull/911 | `28f4a14a1d441436512ea3e3a32402588b9bbbc8` | Open | Unmerged; older base | Hostinger parity-fetch isolation proposal |
| #899 | https://github.com/mattari20/estudent-listing/pull/899 | `40e6c67aec8c975dc538d47d3889ab47669726f3` | Open | Unmerged; older base | Empty-directory diagnostics documentation |

No existing PR was modified, merged or closed. The audit PRs are not included in main.

### 2.3 Workflow-run evidence limitations

The available GitHub commit-runs action returns PR-triggered runs only. A query for the current main SHA returned an empty list; this is **not evidence that no main/scheduled runs exist**. The broader Actions run-list endpoint and workflow-enabled-state endpoint were not available through the current read-only connector route. Accordingly:

- Current active/running run IDs: **not independently verified**.
- Current latest run ID for each scheduled workflow: **not independently verified**.
- GitHub workflow enabled/disabled state: **not independently verified**.
- No run was dispatched, rerun, cancelled or modified.

Historical IDs present in existing project records (not current-state proof):
- Offering-gap reconciliation Run #11: [40283314304](https://github.com/mattari20/estudent-listing/actions/runs/40283314304).
- Offering-gap source verification Run #9: [40306177644](https://github.com/mattari20/estudent-listing/actions/runs/40306177644).
- Historical scheduled live integrity audit: [40295796191](https://github.com/mattari20/estudent-listing/actions/runs/40295796191).

These runs are cited as historical evidence only; their old results must not be represented as the present runtime state.

## 3. Complete GitHub Actions inventory

The recursive `main` tree contains 35 files under `.github/workflows/`. Every workflow below is classified by its primary purpose. A workflow may also have secondary effects described in the notes.

**Status terminology:** “Configured; execution unverified” means its trigger exists in the audited YAML, but current GitHub enabled state and recent execution were not independently verified. “Manual-only configured” means only a manual trigger is present in the audited file; it does not prove GitHub activation state.

| # | Workflow / exact path | Trigger(s) in YAML | Environment and principal action | Production/external side effects and classification | Status / key caveat |
|---:|---|---|---|---|---|
| 1 | **Scheduled Governed Institute Discovery** — `.github/workflows/scheduled-institute-discovery.yml` | Hourly `15 * * * *`; manual dispatch; `workflow_run` after successful Controlled Official-Source Refresh Migration | GitHub runner → SSH → Hostinger; runs `institutes:bootstrap-agent` and `institutes:monitor-changes` | External official-source retrieval; source/policy upserts; retrieval runs, snapshots, observations, evidence, assertions, alerts, outcomes; may stage candidate batches and private review packages; queues re-verification. No direct canonical promotion/publication. Also fetches/unshallows Git history in deployed checkout. **External retrieval + production DB/files + candidate staging + deployed Git metadata write.** | Configured scheduled/triggered; current run and enabled state unverified. **Highest-priority future pause candidate.** |
| 2 | **Automated Live Data Integrity Audit** — `.github/workflows/automated-live-integrity-audit.yml` | Daily `17 3 * * *`; manual dispatch; push to main on application-path changes | GitHub runner → SSH → Hostinger; runs `institutes:data-integrity-report` | Intended DB read/report; performs Git fetch in deployed checkout for parity. No canonical DB write intended. **Production diagnostics + deployed Git metadata write.** | Configured; recent run unverified. Pause until parity fetch is isolated or explicitly accept the metadata side effect. |
| 3 | **Build Frontend Assets** — `.github/workflows/build-frontend-assets.yml` | Every push to main; manual dispatch | GitHub runner; npm install/build; commits generated `public/build` assets back to main | Writes a new repository commit with `contents: write`; can trigger further main-push workflows, including itself once if generated assets change. Does not itself deploy to Hostinger. **Repository mutation/build.** | Configured; run state unverified. Not a production deployment, but not read-only CI. |
| 4 | **Controlled Discovery Outcome Migration** — `.github/workflows/controlled-discovery-outcome-migration.yml` | Manual dispatch; confirmation flags default false | GitHub runner → SSH → Hostinger; preflight, optionally one migration | Production schema write only if explicit migration confirmation passes; optional handling of an empty partial table is also guarded by a separate flag. **Production migration.** | Manual-only configured; keep gated; do not disable routinely or dispatch without approval. |
| 5 | **Controlled Hostinger Code Deployment** — `.github/workflows/controlled-hostinger-code-deploy.yml` | Manual dispatch; `confirm_deployment` defaults false | GitHub runner → SSH → Hostinger; parity/preflight and optionally deploys approved application code | Production filesystem/application-code writes only when confirmation is true. It also performs Git fetch/parity work in the deployed checkout. **Deployment + deployed Git metadata write.** | Manual-only configured; preserve the controlled gate; never treat default false as proof of current production state. |
| 6 | **Controlled Import Batch Duplicate Rows Migration** — `.github/workflows/controlled-import-batch-duplicate-rows-migration.yml` | Manual dispatch; confirmation defaults false | GitHub runner → SSH → Hostinger; guarded one-file migration | Production schema write only after explicit confirmation and preflight. **Production migration.** | Manual-only configured; preserve gate. |
| 7 | **Controlled Institution Field Approvals Migration** — `.github/workflows/controlled-institution-field-approvals-migration.yml` | Push to main when this workflow file changes; manual dispatch with confirmation default false | GitHub runner → SSH → Hostinger; preflight and optionally one migration | A matching push can automatically start the preflight path; migration requires manual dispatch with confirmation. Production schema write only after explicit confirmation. Parity logic fetches Git history in deployed checkout. **Production diagnostic + conditional migration + Git metadata write.** | Configured; push-triggered preflight is an automatic trigger worth retaining in freeze map. |
| 8 | **Controlled Official-Source Refresh Migration** — `.github/workflows/controlled-official-source-refresh-migration.yml` | Manual dispatch; index-repair and migration confirmations default false | GitHub runner → SSH → Hostinger; read-only schema preflight, optionally repair two indexes or reconcile migration ledger | Production schema/index/ledger writes only with the corresponding explicit confirmation. Successful completion can trigger workflows #1 and #24 through `workflow_run`. **Conditional production migration + downstream trigger.** | Manual-only configured; preserve gate. Downstream chain must be considered if ever executed. |
| 9 | **Controlled Pakistan Geography Seed** — `.github/workflows/controlled-pakistan-geography-seed.yml` | Manual dispatch; `confirm_seed` defaults to `no` | GitHub runner → SSH → Hostinger; preflight and optionally seed eight canonical geography records | Canonical geography DB write only with explicit `yes`. **Production canonical seed.** | Manual-only configured; keep gated; do not dispatch. |
| 10 | **Diagnose Hostinger Laravel Path** — `.github/workflows/diagnose-hostinger-artisan-path.yml` | Manual dispatch | GitHub runner → SSH → Hostinger; inspects home/domain directories and searches for `artisan` | Filesystem reads only by intended script; no DB or source calls. **Production runtime diagnostic.** | Manual-only configured; no recent execution verified. |
| 11 | **Read-only Discovery Runtime Readiness** — `.github/workflows/discovery-runtime-readiness.yml` | Manual dispatch | GitHub runner → SSH → Hostinger; PHP/Laravel, migration status, route and integrity checks | DB/schema reads and temporary report files; no migration command. Inspect exact script before future use; a report in `/tmp` is a filesystem write. **Production runtime diagnostic.** | Manual-only configured; no recent execution verified. |
| 12 | **Hostinger Phase 5B.18 Runtime Diagnostic Delivery** — `.github/workflows/hostinger-artisan-runtime-only.yml` | Manual dispatch | GitHub runner → SSH/SCP → Hostinger; delivers a pinned diagnostic script, runs it once, retrieves report/stderr and performs scoped cleanup | Writes temporary private run files, a private runtime report and logs; isolated parity repository in `/tmp`; cleanup is conditional on artifact preservation. It is not a no-filesystem-write operation. **Production runtime/filesystem diagnostic.** | Manual-only configured; pinned diagnostic revision is historical; PR #953 repairs a separate workflow path but is not merged. |
| 13 | **Hostinger GCU Single-Source Qualification** — `.github/workflows/hostinger-gcu-single-source-qualification.yml` | Manual dispatch; explicit confirmation boolean defaults false | GitHub runner → SSH/SCP → Hostinger; fixed single-source GCU qualification command | Contacts the configured HEC source; writes a private qualification report on Hostinger and downloads a bounded copy to a runner artifact. Qualification tests assert no DB writes, but this workflow has not been dispatched in this audit. **External source retrieval + private report write.** | Manual-only configured; not a background schedule. Preserve as gated capability; no source request made. |
| 14 | **Hostinger Read-only Diagnostics** — `.github/workflows/hostinger-readonly-diagnostics.yml` | Every push to main; every six hours `17 */6 * * *`; manual dispatch | GitHub runner → SSH → Hostinger; runtime/Artisan diagnostics and parity checks | Writes a private diagnostics report and temporary files; parity fetch uses a disposable temporary Git repository rather than deployed `.git`. No intended DB writes. **Production runtime/filesystem diagnostic.** | Configured; recent run unverified. Group A preservation candidate if report-path/retention contract remains safe. |
| 15 | **Hostinger Read-only Offering Gap Audit** — `.github/workflows/hostinger-readonly-offering-gap-audit.yml` | Manual dispatch | GitHub runner → SSH → Hostinger; runs `institutes:offering-gap-preflight` and `institutes:offering-gap-reconcile` | Writes private JSON reports on Hostinger and retrieves them to a runner artifact; commands are designed as DB read-only. No migrations or canonical writes intended. **Production diagnostic + private report write.** | Manual-only configured; no current report inspected or storage probe repeated. |
| 16 | **Institutes Protected Diagnostics Verification** — `.github/workflows/institutes-diagnostics-verify.yml` | Manual dispatch | GitHub runner; calls protected diagnostics endpoint and validates report contracts | Reads protected report data; no production DB/file mutation indicated. **Protected diagnostics validation.** | Manual-only configured; token and endpoint state not inspected. |
| 17 | **Retrieve Institutes Production Diagnostics (Private)** — `.github/workflows/institutes-private-diagnostics-report.yml` | Daily `0 4 * * *`; manual dispatch | GitHub runner; fetches protected diagnostics and stores report in a private repository | Reads production diagnostic endpoint; writes retained evidence to private repository via private-report token. Not a production DB write. **Audit/evidence retention.** | Configured; preserve as evidence-retention automation; current latest run unverified. |
| 18 | **Institutes Public Site Monitor** — `.github/workflows/institutes-public-monitor.yml` | Daily `30 3 * * *`; every push to main; manual dispatch | GitHub runner; HTTP smoke tests against public directory routes | Public HTTP requests and runner artifact only; no production DB write. Concurrency uses `cancel-in-progress: true`, so a newer monitor can cancel a stale prior monitor. **Public-site diagnostic.** | Configured; preserve. Recent run unverified. |
| 19 | **Institution Import Preflight** — `.github/workflows/institution-import-preflight.yml` | PR paths; push to main on data-quality script/test paths | GitHub runner; Python duplicate-preflight tests | Repository test only; no production connection or import. **CI/repository validation.** | Configured; preserve. |
| 20 | **Isolated MySQL Approval Migration Validation** — `.github/workflows/isolated-mysql-approval-migration-validation.yml` | PR paths; manual dispatch | GitHub runner with disposable MySQL 8.4; runs migrations/tests against isolated DB | Creates/destroys isolated CI schema; no Hostinger production connection. **CI migration validation.** | Configured; preserve. |
| 21 | **Laravel CI** — `.github/workflows/laravel-ci.yml` | Push to main/feature branch; PR to main | GitHub runner; Composer, SQLite migrations and Laravel tests | Local CI DB only. **CI/test.** | Configured; preserve. |
| 22 | **Read-only offering-gap preflight** — `.github/workflows/offering-gap-preflight.yml` | Daily `0 2 * * *`; push on relevant code/register changes; manual dispatch | GitHub runner in `offering-gap-readonly-audit` environment; runs two Artisan report commands against the configured DB connection | Intended DB reads and runner artifacts; actual DB grants are not established by this audit. Prior project notes say the Hostinger grants check was skipped due shared-hosting limitations. **Production data audit; read-only intent, grants unverified.** | Configured; pause scheduled run until SELECT-only credentials/grants are evidenced. |
| 23 | **Official source verification for offering gaps** — `.github/workflows/offering-gap-source-verification.yml` | Push to main on verifier/register paths; manual dispatch; weekly Monday `23 4 * * 1` | GitHub runner; runs `institutes:verify-offering-gap-sources` | Contacts official-source URLs from the runner and uploads a report artifact; no Hostinger DB connection is apparent in the workflow. **External source retrieval / evidence report.** | Configured; preserve only within accepted source access policy; no source was contacted in this audit. |
| 24 | **Official Source Refresh Readiness (Read-only)** — `.github/workflows/official-source-refresh-readiness.yml` | Manual dispatch; `workflow_run` after Controlled Hostinger Code Deployment or Controlled Official-Source Refresh Migration | GitHub runner → SSH → Hostinger; verifies application/schema/report readiness after a successful upstream workflow | DB/schema and filesystem reads; may create diagnostic report/temp files; parity logic can fetch Git history in the deployed checkout. No migration itself. **Post-deployment production diagnostic + deployed Git metadata write.** | Configured; dependent workflow. Keep manual/automatic readiness checks only after parity side effect is removed or explicitly accepted. |
| 25 | **Institutes Directory Command Orchestrator** — `.github/workflows/project-command-orchestrator.yml` | Manual dispatch; exact `issue_comment` commands; every push to main; selected PR changes | GitHub runner; fail-closed command interpreter, local controller tests, GitHub inventory | Does not deploy or run production Artisan. On a recognized issue/PR comment with a target issue, it can create/update a state comment and upload artifacts. Push events use Continue but do not specify an issue target. **Project governance automation / GitHub metadata write.** | Configured; preserve, while understanding issue-comment writes. It does not autonomously create/merge code PRs in the inspected implementation. |
| 26 | **Institutes Directory Project Controller** — `.github/workflows/project-controller.yml` | Daily `31 4 * * *`; manual dispatch; selected PR/push paths | GitHub runner; unit tests and read-only GitHub inventory | Reads PR/workflow state and uploads a review-package artifact. Script explicitly does not dispatch workflows, merge PRs, write production or edit tracker. **Repository inspection/report.** | Configured; preserve. Current run unverified. |
| 27 | **Public Browser Runtime Diagnostics** — `.github/workflows/public-browser-runtime-diagnostics.yml` | Manual dispatch; selected PR/push paths | GitHub runner with browser/Chromium; checks public page/search/pagination/JSON errors | Sends HTTP requests to public production pages; browser artifacts/reports only. No DB write. **Public-site diagnostic.** | Configured; preserve if public smoke checks are desired. |
| 28 | **Read-only Reset Inventory** — `.github/workflows/readonly-reset-inventory.yml` | Manual dispatch; push when this workflow file changes | GitHub runner → SSH → Hostinger; schema/row-count inventory | DB reads and report output; Git fetch may occur inside deployed checkout to verify revision. **Production diagnostic + deployed Git metadata write.** | Configured; no current run verified. Pause until parity fetch is isolated if triggered automatically. |
| 29 | **Step 3 Discovery Candidate Audit** — `.github/workflows/step3-candidate-audit.yml` | Manual dispatch; push when this workflow file changes | GitHub runner → SSH → Hostinger; reads current GCU batch/rows and canonical slugs | DB reads; fetches main into deployed checkout if target commit absent. No intended DB write. **Production data audit + deployed Git metadata write.** | Configured; current protected export remains unavailable; pause push trigger until Git fetch is isolated. |
| 30 | **Secure Existing Institutes Administrator Recovery** — `.github/workflows/secure-institutes-admin-recovery.yml` | Manual dispatch with exact confirmation string and target identity inputs | GitHub runner → SSH → Hostinger; recovers/reset password for an existing admin account | Production user credential write if explicit confirmation and identity checks pass. **Security/admin recovery write.** | Manual-only configured; preserve security recovery capability, never dispatch without a separately reviewed recovery request. |
| 31 | **Step 3 Evidence Register Validation** — `.github/workflows/step3-evidence-register-validation.yml` | PR paths; push to main on register/validator paths; manual dispatch | GitHub runner; Python CSV/register validation | Repository validation only. **CI/documentation validation.** | Configured; preserve. |
| 32 | **Step 3 Record-Level Remediation Audit** — `.github/workflows/step3-record-level-remediation-audit.yml` | Manual dispatch; push on workflow/command paths | GitHub runner → SSH → Hostinger; runs `institutes:data-gap-record-audit` | Intended DB read-only report; fetches Git history in deployed checkout if required. **Production audit + deployed Git metadata write.** | Configured; no current run verified; isolate parity fetch before push-triggered use. |
| 33 | **Test** — `.github/workflows/test.yml` | PR to main/feature branch; push to main | GitHub runner; SQLite migrations, PHP tests and static workflow contract tests | Local CI DB only. **CI/test.** | Configured; preserve. |
| 34 | **Verify UVAS Public Directory** — `.github/workflows/verify-uvas-public-directory.yml` | PR paths; push to `ops/audit-next-institution-catalog` | GitHub runner → SSH → Hostinger; reads active institution slugs and checks public directory/data-gap report | DB reads, public HTTP checks and a temporary `/tmp/uvas-profile-slugs.txt` file on Hostinger. No intended canonical write. **Production/public diagnostic + temporary filesystem write.** | Configured on a non-main operations branch/PR path; no current branch/PR run verified. |
| 35 | **Audit Next Institution Catalog** — `.github/workflows/audit-next-institution-catalog.yml` | PR paths; push to `ops/audit-next-institution-catalog` | GitHub runner → SSH → Hostinger; reads active institutions/campuses/programmes and data-gap report | DB reads and public diagnostics; no intended DB write. **Production data audit.** | Configured on a non-main operations branch/PR path; no current run verified. |

### 3.1 Workflow count by primary purpose

These are mutually exclusive primary-purpose counts for the 35 workflow files above; secondary effects are called out in the matrix.

| Primary classification | Count |
|---|---:|
| CI, repository validation, build and project governance | 12 |
| Production runtime/data diagnostics and audits | 13 |
| External-source qualification/retrieval and governed discovery | 3 |
| Explicit production deployment, migration, seed or account-recovery workflows | 7 |
| **Total** | **35** |

Additional trigger counts:
- **8** workflow files declare a `schedule` event.
- **2** workflow files declare a `workflow_run` dependency.
- **At least 20** workflow definitions contain Hostinger SSH/SCP production interaction; some are diagnostics, some are gated writes. This is a source-level path count, not a statement that all are enabled or recently run.
- **No workflow was dispatched or rerun** during this audit.

## 4. Laravel scheduler, commands, queues and application automation

### 4.1 Scheduler definitions

Exact file: `bootstrap/app.php`.

The application registers:

```php
$schedule->command('institutes:bootstrap-agent')
    ->hourly()
    ->withoutOverlapping(55);

$schedule->command('institutes:monitor-changes')
    ->hourly()
    ->withoutOverlapping(55);
```

`routes/console.php` contains only the Laravel `inspire` example command; it does not add another schedule.

The two scheduled commands are:

| Command | Source file | What it does | Production side effects |
|---|---|---|---|
| `institutes:bootstrap-agent` | `app/Console/Commands/BootstrapInstituteAgent.php` | Iterates configured profiles; skips disabled profiles; checks canonical parent; registers/updates source; ensures monitoring policy; optionally performs first discovery if no populated/current-version batch exists | `Source::updateOrCreate`; policy disable/updateOrCreate; may retrieve official source, create snapshot/evidence/batch/rows/assertions/identity signals and private review package |
| `institutes:monitor-changes` | `app/Console/Commands/MonitorSourceChanges.php` | Runs `SourceMonitoringBatchService` for due active policies unless `--dry-run` | Retrieves external source; writes retrieval run/snapshot/observation; links assertions, projects events, queues re-verification; records discovery outcome; on changed matching source may stage a new candidate batch and review package; updates monitoring policy run state |

The Laravel `withoutOverlapping(55)` lock protects each Laravel scheduled task from another overlapping Laravel scheduler invocation when the configured cache lock backend is functioning. It does **not** coordinate with a GitHub runner directly invoking the same Artisan commands over SSH.

### 4.2 Bootstrap writes source and policy records

`BootstrapInstituteAgent::handle()`:
- Defaults to every profile in `config('institute_agent.profiles')` if no profile option is supplied.
- Calls `disableForProfile()` for profiles whose monitoring config is disabled.
- Disables a policy if a required canonical parent institution is missing.
- For enabled profiles, calls `Source::updateOrCreate()` with `status=active`.
- Calls `DiscoveryMonitoringPolicyService::ensure()`, which can disable an old policy when the source URL changes and upsert the current policy as active/disabled based on configuration.
- If not `--no-discovery`, performs initial discovery if the latest batch does not already contain rows and is not a current-version empty batch.

Therefore bootstrap is a **production DB-writing command**, even when it decides not to perform a new candidate extraction.

### 4.3 Monitoring and change-triggered discovery writes

`SourceMonitoringBatchService::run()`:
1. Loads due active monitoring policies.
2. Selects the policy's source or all active sources when no source ID is assigned.
3. Runs `SourceMonitoringPipeline` for each source.
4. Records failures and raises monitoring alerts/audit events.
5. On successful retrieval, obtains the source-change observation and processes it through `SourceChangeDiscoveryService`.
6. Records a discovery outcome for successful no-change/baseline observations too.
7. Updates policy success/failure state and `last_run_at`.

On changed observations, `SourceChangeIntelligenceService` links affected assertions, projects source-change events and enqueues re-verification. `SourceChangeDiscoveryService` then matches the observation's source URL to a configured profile, verifies source identity, checks `auto_discovery`, and invokes the institution identity or programme discovery service. Candidate batches and review packages can therefore be created without a separate manual command.

### 4.4 Canonical writes, approvals and publication boundary

The inspected scheduled path does **not** call the canonical promotion or publication services. It stages candidate data and creates evidence/review metadata.

- No scheduled workflow invokes a candidate approve/reject/identity-decision endpoint.
- No scheduled workflow invokes import promotion or public publication.
- `OfficialSourceCanonicalRefreshService` has canonical write methods, including an approved institution-description proposal path, but this service is not called by the scheduled bootstrap/monitoring chain inspected here.
- Explicit controlled migrations, geography seed, code deployment and administrator recovery are separate manual workflows with confirmation gates.
- The existence of a gated canonical refresh service does not mean the monitoring scheduler can automatically publish data.

**Boundary:** automatic source registration, policy updates, snapshots, observations, evidence, assertions, monitoring alerts, candidate rows and private review packages are real production writes; canonical institution/programme publication remains a separate governed stage in the inspected automation path.

### 4.5 Queue workers and retry behavior

- `config/queue.php` defaults `QUEUE_CONNECTION` to `database`, with a database queue table and `failed_jobs` table; environment configuration can override this.
- The repository tree contains no `app/Jobs/` classes or `Console/Kernel.php` scheduler file.
- The inspected workflows and `bootstrap/app.php` do not schedule `queue:work` or `queue:listen`.
- `DataReverificationQueue` is an application-domain table populated through `ReverificationQueueService`; it is not proof that Laravel queued jobs are being processed.
- No current Hostinger queue worker process, supervisor service, cron worker, retry configuration or failed-job state was inspected.

**Status:** queue-worker execution is unknown. Do not infer that queued re-verification work is being consumed, or that it is not being consumed.

### 4.6 Other manual Artisan commands with potential side effects

| Command | Source file | Guard / risk |
|---|---|---|
| `institutes:discover` | `RunInstituteDiscoveryAgent.php` | Explicit configured profile; stages non-canonical candidates; optional `--ai` can invoke paid AI if enabled |
| `institutes:refresh-discovery` | `RefreshInstituteDiscoveryBatch.php` | Requires `--confirm-new-batch` and a valid `--supersedes-batch-id`; preserves old batch; creates new staging batch |
| `institutes:discovery-review-package` | `CreateInstituteDiscoveryReviewPackage.php` | Creates/reuses private CSV/manifest package |
| `institutes:monitor-sources` | `MonitorInstituteSources.php` | Reads active freshness policies/evidence and may enqueue reverification queue records; not in the two scheduler definitions |
| `institutes:prune-discovery-review-packages` | `PruneInstituteDiscoveryReviewPackages.php` | Dry-run by default; `--apply` deletes eligible private package directories older than retention cutoff. No scheduled invocation found. |
| `institutes:qualify-official-sources` | `QualifyOfficialInstituteSources.php` | External source retrieval and report output; dedicated manual GCU workflow; no DB writes by qualification command contract |
| `institutes:seed-pakistan-geography` | `SeedPakistanGeography.php` | Canonical geography seed; gated workflow only |
| `institutes:data-integrity-report`, `institutes:data-gap-record-audit`, offering-gap reports | Respective command files | Intended read-only DB reports; some workflows also write private reports or mutate deployed Git metadata |

No Artisan command was executed during this audit.

## 5. Trigger and execution dependency map

### 5.1 Primary scheduled paths

```text
GitHub Actions schedule (hourly at minute 15)
  └─ scheduled-institute-discovery.yml
       ├─ resolve approved application-code revision
       ├─ SSH to Hostinger and verify parity/schema
       ├─ institutes:bootstrap-agent
       │    ├─ Source upsert
       │    ├─ monitoring policy upsert/disable
       │    └─ initial discovery if required
       └─ institutes:monitor-changes
            ├─ external retrieval
            ├─ retrieval run / snapshot / observation
            ├─ source-change intelligence / alerts / re-verification queue
            ├─ changed-source candidate discovery
            ├─ evidence / assertions / batch / candidate rows
            └─ private review package / run outcome

Hostinger cron (actual entry UNKNOWN)
  └─ possible php artisan schedule:run
       ├─ Laravel hourly institutes:bootstrap-agent
       └─ Laravel hourly institutes:monitor-changes
            (same command pair as GitHub workflow; no shared cross-runner lock proven)
```

### 5.2 Workflow-run dependencies

```text
Controlled Official-Source Refresh Migration
  └─ on successful workflow_run
       ├─ scheduled-institute-discovery.yml
       │    └─ validates schema handoff and then may run discovery/monitoring
       └─ official-source-refresh-readiness.yml
            └─ runs post-migration readiness diagnostics

Controlled Hostinger Code Deployment
  └─ on successful workflow_run
       └─ official-source-refresh-readiness.yml
            └─ post-deployment readiness diagnostics
```

A freeze that only removes the hourly cron expression from `scheduled-institute-discovery.yml` would **not** block its `workflow_run` trigger or manual dispatch. The entire workflow must be disabled or all automatic trigger paths must be gated for a complete pause.

### 5.3 Main push / PR trigger effects

- Every push to main can trigger the Command Orchestrator and Public Site Monitor; the frontend asset workflow also runs on every main push.
- Frontend asset generation may commit `public/build` back to main, creating a second push event. That can trigger the asset workflow once more (normally no new asset diff), public monitoring, tests, and path-matched diagnostics.
- Application-path pushes can trigger the automated live integrity audit.
- A push changing the institution-field-approval migration workflow file triggers its preflight workflow; the actual migration still requires a manual dispatch with confirmation.
- Pushes to selected Step 3 audit workflow/command paths can trigger Hostinger read-only audit workflows.
- The older catalog workflows trigger on their operations branch or matching PR paths, not ordinary main pushes.

### 5.4 Cross-runner lock assessment

| Lock / concurrency mechanism | Scope | What it protects | What it does not protect |
|---|---|---|---|
| GitHub `concurrency.group: scheduled-governed-institute-discovery` | GitHub Actions runs of that workflow | Prevents overlapping runs of that same GitHub workflow; `cancel-in-progress: false` queues/retains rather than cancels prior run | Laravel scheduler, manual Artisan SSH sessions, other workflows, Hostinger cron |
| Laravel `withoutOverlapping(55)` | Laravel scheduler/cache lock | Prevents overlapping Laravel scheduled invocation of each named task if cache locks are working | GitHub Actions direct SSH Artisan calls; separate scheduler implementation; independent commands |
| Per-source DB row lock in discovery service | Database transaction for candidate staging | Serializes staging for a source at service boundary | Does not serialize all monitoring retrievals, source upserts, policy updates, or two different commands end-to-end |
| Other GitHub workflow concurrency groups | Each individual workflow group | Prevent same workflow from overlapping | No global lock across different workflow names |

**Finding:** no shared database/Redis distributed lock is demonstrated between GitHub Actions and the Hostinger Laravel scheduler. The scheduled GitHub workflow invokes the same two Artisan commands directly and does not acquire Laravel scheduler's `withoutOverlapping` mutex. Duplicate/overlapping work remains possible if Hostinger's scheduler is active at the same time.

## 6. Production write-risk assessment

| Operation | Trigger path | Production write? | Risk / consequence | Canonical effect |
|---|---|---:|---|---|
| Source registry upsert | Bootstrap agent | Yes | Changes source name/type/authority/status and can reactivate source | No direct canonical write |
| Monitoring policy ensure/disable | Bootstrap agent | Yes | Activates/disables source monitoring policies; stale URL policy can be disabled | No |
| Source retrieval run | Monitor changes | Yes | Records attempt/status/error and retrieval metadata | No |
| Snapshot capture | Monitor changes / discovery | Yes | Persists fetched source content and hash | No |
| Change observation/intelligence | Monitor changes | Yes | Persists change state, assertion links, events and alert records | No |
| Re-verification queue | Monitoring | Yes | Creates/updates domain queue records | No |
| Candidate staging | Bootstrap initial discovery / changed-source monitoring | Yes | Creates batch, import rows, evidence, assertions, identity signals and conflicts | No direct promotion |
| Review package | Discovery | Yes, private filesystem | Writes CSV/manifest under private local storage | No |
| Discovery outcome ledger | Monitor changes | Yes | Records baseline/no-change/changed/failed outcomes | No |
| Canonical refresh | Separate approved service | Yes if explicitly invoked | Applies an approved, evidence-bound field proposal | Yes, but not in scheduled discovery chain |
| Candidate approval/promotion/publication | Separate governed routes/services | Yes if explicitly invoked | Changes canonical lifecycle/public visibility | Yes, but no automatic trigger found in scheduled chain |
| Controlled deployment | Manual confirmed workflow | Yes, filesystem | Replaces deployed application code | Indirectly changes runtime behavior |
| Controlled migrations | Manual confirmed workflows | Yes, schema/ledger | Changes production schema or migration ledger | Not canonical rows directly |
| Geography seed | Manual confirmed workflow | Yes, canonical rows | Creates eight geography records | Canonical data write |
| Admin recovery | Manual confirmed workflow | Yes, user credential | Resets existing administrator credential | Security-sensitive account write |
| Diagnostic report workflows | Scheduled/manual | Often private file writes | Creates report/temp files; some Git fetch operations mutate deployed `.git` | Usually no canonical write |

The most consequential production write path is scheduled discovery/monitoring: it is non-canonical but can grow source, evidence, snapshot, observation, candidate, review-package and queue records. The fact that it cannot directly publish canonical records does not make it a read-only diagnostic.

## 7. Proposed reversible freeze plan — NOT EXECUTED

### 7.1 Group A — Preserve

| Process | Recommendation | Conditions / side effects |
|---|---|---|
| `laravel-ci.yml`, `test.yml`, `isolated-mysql-approval-migration-validation.yml` | Preserve | CI databases are isolated; continue ordinary PR validation |
| `step3-evidence-register-validation.yml`, `institution-import-preflight.yml` | Preserve | Repository-only validation |
| Project Controller | Preserve | Read-only GitHub inventory and artifact; no dispatch/merge/tracker write |
| Command Orchestrator | Preserve with awareness | Fail-closed supported checks; issue/PR comment state may be created/updated for explicit recognized commands |
| Public Site Monitor / Public Browser Runtime Diagnostics | Preserve | Public HTTP reads and runner artifacts; no DB writes |
| Hostinger Read-only Diagnostics | Preserve conditionally | It writes a private diagnostics report and temporary files, but parity Git fetch is isolated; retain only if report-path/retention behavior remains understood |
| Private Production Diagnostics Report | Preserve | Evidence-retention automation writes to private repository; protect tokens and access |
| Security protections, access controls, backups, audit logs, retention and branch protection | Never disable in routine freeze | Preserve evidence, recovery and repository governance |

### 7.2 Group B — Pause only after separate explicit authorization

#### B1. Scheduled discovery and monitoring

**GitHub workflow:** `.github/workflows/scheduled-institute-discovery.yml`

- **Future pause method:** disable the entire workflow so that schedule, manual dispatch and `workflow_run` triggers are all blocked. Example, only after approval:
  ```bash
  gh workflow disable scheduled-institute-discovery.yml --repo mattari20/estudent-listing
  ```
- **Expected effect:** no new GitHub-runner invocation of bootstrap/monitoring; no migration-success handoff into this workflow.
- **Does not affect:** Laravel scheduler, any existing in-progress GitHub run, manually started SSH process, queue worker, other monitoring/audit workflows.
- **In-progress runs:** inspect active runs first. Disabling a workflow does not guarantee cancellation of a run already started. Do not cancel an active run without separately assessing its stage and partial writes.
- **Verification:** `gh workflow list --repo mattari20/estudent-listing` should show the workflow disabled; inspect recent run list and wait for any active run to finish or be separately dispositioned.
- **Restore method:** after resume gates pass and explicit approval, run:
  ```bash
  gh workflow enable scheduled-institute-discovery.yml --repo mattari20/estudent-listing
  ```
  Verify enabled state and the next scheduled/manual event before considering it resumed.
- **Risk of pausing:** source monitoring becomes stale; new official-source changes are not captured; alerts/candidates are delayed.
- **Risk of leaving active:** duplicate runner execution, ongoing production writes, source retrieval and candidate/review-package growth while engineering controls remain unresolved.

#### B2. Hostinger Laravel scheduler — only if the cron entry exists

**Repository schedule:** `bootstrap/app.php`, two hourly tasks.

- **Future pause method:** first obtain a current read-only screenshot/export of Hostinger Cron Jobs showing the exact command, frequency, timezone and target directory. If it invokes `php artisan schedule:run` for this app, use hPanel to pause/disable that specific cron entry without deleting it. Preserve its exact original command and schedule for restoration.
- **Expected effect:** prevents Laravel's scheduler from launching the two hourly tasks.
- **Does not affect:** GitHub Actions scheduled workflow, already-running Artisan processes, manually invoked commands or other Hostinger cron jobs.
- **Verification:** confirm the specific entry is disabled in hPanel and retain evidence. Do not infer pause from a missing/blank listing without a fresh inspection.
- **Restore method:** re-enable the same cron entry with the exact recorded command, schedule and timezone after the resume gates pass.
- **Risk of pausing:** any other Laravel scheduled task later added to this app would also stop; monitoring becomes stale.
- **Risk of leaving active:** duplicate execution with GitHub's direct SSH command path if both run concurrently.
- **Current status:** no exact Hostinger cron entry is known. **Do not attempt a pause until it is identified.**

#### B3. Diagnostics that mutate deployed Git metadata

Temporarily disable or remove their automatic triggers only after explicit approval, until parity fetches use an isolated temporary repository:

| Workflow | Trigger to control | Reason | Restore condition |
|---|---|---|---|
| `automated-live-integrity-audit.yml` | Daily schedule and application-path push trigger (or temporarily disable workflow) | Fetches origin history in deployed checkout | Git parity fetch moved to isolated temp repo and regression-tested |
| `step3-candidate-audit.yml` | Push trigger; manual use also blocked until fixed | Fetches main in deployed checkout | Isolated parity implementation; current protected data access approved |
| `step3-record-level-remediation-audit.yml` | Push trigger; manual use also blocked until fixed | Fetches main in deployed checkout | Isolated parity implementation and report review process approved |
| `readonly-reset-inventory.yml` | Push trigger; manual use also blocked until fixed | May fetch target/main in deployed checkout | Isolated parity implementation |
| `official-source-refresh-readiness.yml` | `workflow_run` and manual trigger as needed | Post-deployment readiness parity may fetch in deployed checkout | Isolated parity implementation or documented approval of exact metadata effect |

For each workflow, disabling the whole workflow with `gh workflow disable <file> --repo mattari20/estudent-listing` blocks every trigger, including manual use and dependent `workflow_run` events. Re-enable with `gh workflow enable <file> --repo mattari20/estudent-listing` after the specific fix and verification. Do not pause CI, backups or private evidence retention as a substitute.

#### B4. Offering-gap database preflight

**Workflow:** `.github/workflows/offering-gap-preflight.yml`

- **Recommendation:** pause its daily schedule until the actual DB connection grants are independently evidenced as SELECT-only.
- **Reason:** command names and report contracts express read-only intent, but the historical Hostinger grant check was skipped; this audit did not inspect the live account grants.
- **Does not affect:** repository register validation, manual source verification or Hostinger's separate manual report workflow.
- **Verification/restore:** capture approved evidence of SELECT-only DB grants and confirm report command mutation flags remain false, then restore the schedule.
- **Risk of pausing:** offering-gap status becomes stale.
- **Risk of leaving active:** an unverified credential could have broader privileges than the workflow assumes.

#### B5. Other scheduled read-only source/report checks

- `offering-gap-source-verification.yml` contacts official source URLs from GitHub runners; it does not appear to connect to the production DB. Preserve only if the source access policy and weekly retrieval load are accepted. If external retrieval itself must stop, pause this workflow's schedule separately after approval.
- `institutes-private-diagnostics-report.yml` is evidence retention; preserve by default. Pause only if private report handling/access or token safety is compromised.
- `hostinger-readonly-diagnostics.yml` is a six-hourly report writer; preserve by default if report retention/path is accepted. It is not a no-write process.
- `project-controller.yml` is a daily GitHub inventory; preserve.
- `institutes-public-monitor.yml` is a daily public HTTP monitor; preserve.

### 7.3 Group C — Never disable in a routine freeze

- Database backups and essential hosting safeguards.
- Security protections, access controls, authentication and branch protection.
- Audit logs, provenance, private evidence retention and incident records.
- Required repository protection and CI/test checks.
- The ability to perform a controlled, separately authorized security recovery or migration. Do not execute these workflows during a freeze; retain their guarded definitions.

A routine freeze is not permission to remove safeguards, delete evidence or disable repository governance.

### 7.4 Explicit no-action rule for this audit

The commands above are examples for a future approved freeze only. They were **not run**. No workflow state, schedule, trigger, cron entry, queue worker or running job was changed.

## 8. Resume-readiness gates

Automation must resume by category, not as one global switch. Each gate needs current evidence tied to an exact deployed revision.

| Gate | Required evidence before resuming production discovery |
|---|---|
| Production revision/runtime | Exact deployed application-code revision matches accepted main revision; PHP/Laravel version and Artisan command registration verified |
| Database schema/ledger | Current read-only schema/index/ledger report matches migrations; approval, source-refresh and discovery-outcome tables are present and structurally equivalent |
| Scheduler ownership | Confirm Hostinger cron command/schedule/timezone; select one authoritative scheduler or implement a shared database/Redis lock used by both execution planes |
| Git parity isolation | All production parity checks use disposable temporary repositories, never deployed `.git` metadata |
| Source qualification | Each enabled source has accepted authority, jurisdiction, access policy, retrieval/content-type/hash and extractor evidence |
| Candidate/evidence integrity | Current candidate rows have source/snapshot/hash/evidence lineage, quality and duplicate checks; no missing or ungrounded evidence |
| Human governance | Identity conflicts, field proposals, reviewer separation, approval evidence freshness, promotion and publication controls pass |
| Queue/recovery | Identify actual queue worker/custom re-verification consumer; test idempotency, failure recovery, retry limits and dead-letter/failed-job handling |
| Monitoring | One runner only per source/policy; alerts, no-change outcomes, failure states and report retention verified |
| Production write scope | Explicit authorization separately names source registration, monitoring, candidate staging, review-package writes, canonical promotion and publication; no implicit permission inheritance |
| Cost/access | AI remains disabled unless separately approved; source terms, rate limits, timeout/size limits and credentials are verified |
| Active-run handling | No unfinished run or queued task is left in an unknown partial state before resumption |

### 8.1 Staged resume order

1. **Repository/CI only:** preserve and verify Test, Laravel CI, isolated MySQL and documentation checks.
2. **One approved read-only runtime diagnostic:** use isolated parity fetch; verify exact deployed revision, runtime and schema without repeating successful storage probes.
3. **Scheduler ownership:** verify Hostinger cron and choose one scheduler or a shared lock. Do not resume discovery before this.
4. **Single-source, read-only qualification:** only after explicit approval, qualify one source with fixed scope and retain a bounded private report.
5. **Monitoring without candidate staging:** if technically separable, first verify retrieval/observation behavior in a no-staging mode; otherwise do not enable a live monitor until candidate staging is explicitly authorized.
6. **GCU candidate staging pilot:** one profile/source at a time; preserve existing candidate history; verify idempotency and review package; no approval/promotion/publication.
7. **Human review and governed approval:** review row-level evidence and conflicts; separate reviewer from proposer.
8. **Canonical promotion/publication:** only after a record-level approved plan and separate explicit authorization.
9. **Expand one source at a time:** never enable all 20 disabled HEC profiles or national sources in one change. Each source must pass qualification, parser, duplicate and governance gates.
10. **AI last and separately:** keep AI disabled until provider/model compatibility, excerpt grounding, cost ceilings and review controls are accepted.

## 9. Unverified hosting and external configuration

| Item | Status | Evidence gap / safe next evidence |
|---|---|---|
| Hostinger Cron Jobs | **UNVERIFIED** | No current hPanel screenshot/export or authorized read-only command output. Obtain exact entry command, frequency, timezone, enabled state and last run before any pause decision. |
| Hostinger queue worker / Supervisor | **UNVERIFIED** | No live process/service/cron evidence. Repository has no app Jobs directory or queue worker workflow. Verify with authorized read-only hosting evidence. |
| GitHub Actions workflow enabled state | **UNVERIFIED** | Workflow YAML exists; current enabled/disabled state was not returned by the available GitHub read route. |
| Current active GitHub runs | **UNVERIFIED** | No complete current active-run list available through the connector. Inspect before any future pause; disabling a workflow does not stop an already-running job. |
| Current latest run IDs per workflow | **UNVERIFIED** | Current main/schedule run listing not available. Historical IDs are recorded in §2.3 only. |
| Current production app revision/runtime | **UNVERIFIED in Step 4** | No SSH/Hostinger diagnostics were run. Step 3 also recorded this as needing a fresh report. |
| Actual DB grants for offering-gap audit | **UNVERIFIED** | Prior reports skipped Hostinger grants check; no DB permission query performed. |
| Current queue depth / failed jobs | **UNVERIFIED** | No production DB or worker inspection performed. |
| Current source policy rows and due schedule | **UNVERIFIED** | No production DB query performed. |
| External source access/rate limits | **UNVERIFIED** | No HEC/GCU/provincial source was contacted. |
| Hostinger backups and retention | **UNVERIFIED** | No hosting backup settings inspected. Do not pause backup safeguards. |

## 10. Outstanding blockers and recommended next actions

### P0 — Before any freeze is attempted

1. Obtain current Hostinger Cron Jobs evidence and identify whether `schedule:run` exists for this project.
2. Check current GitHub active runs and running SSH commands; do not assume disabling a workflow stops an in-progress execution.
3. Decide whether the intended freeze covers only new scheduled runs or also manual/triggered diagnostic workflows.
4. Keep the GitHub scheduled discovery workflow and Laravel scheduler as separate pause controls.
5. Do not touch the custom re-verification queue or queue worker until its actual consumer is identified.

### P1 — Before resuming discovery

6. Resolve the duplicate scheduler design with one authoritative scheduler or shared cross-runner lock.
7. Move Git parity fetches out of deployed checkout in automated integrity/candidate/record-level/reset/readiness workflows.
8. Establish SELECT-only database grants for scheduled offering-gap preflight or keep its schedule paused.
9. Verify current production schema and deployed revision with a fresh approved read-only report.
10. Retrieve current candidate/evidence/review-package state through the protected authorized path; do not infer it from historical counts.
11. Validate that automatic discovery continues to stage only and never approves/promotes/publishes.

### P2 — Governance and scale

12. Document one automation owner and one source/policy execution cadence.
13. Add a cross-runner lock and idempotency key to the end-to-end scheduled run, not only candidate staging.
14. Add run IDs and per-source outcomes to a single operational ledger.
15. Define queue-worker ownership, retries, alerting and recovery.
16. Define explicit per-category authorizations for source retrieval, candidate staging, canonical promotion and publication.

## 11. Validation and completion criteria

- The report is a documentation-only file on a dedicated audit branch.
- Markdown headings, code fences and tables were manually checked for structural closure before PR creation.
- No production Artisan command, source request, SSH operation, database read/write, migration, deployment or Hostinger cron inspection was performed.
- No workflow was dispatched, rerun, cancelled, disabled or enabled.
- No current private diagnostics report was opened, overwritten or deleted.
- No existing PR was modified, merged or closed.
- Exact PR head SHA and exact-head CI results will be recorded after the documentation PR is created and its automatically triggered PR checks are visible.
- A passing documentation PR CI is not production readiness and does not mean the freeze has occurred.

## 12. Safety confirmation

During Step 4:

- No production SSH session was opened.
- No Hostinger Cron Jobs setting was inspected or changed.
- No production database query or write was performed.
- No migrations, imports, discovery, source qualification or monitoring commands were run.
- No external source was contacted.
- No source profile was enabled or modified; all 20 additional HEC profiles remain untouched and disabled.
- No historical GCU candidate rows #348–353 or batches #19–23 were modified.
- No candidate was approved, rejected, identity-resolved, promoted, reversed or published.
- No deployment, cache clear, cron change, queue operation or private-storage probe was performed.
- No GitHub workflow was paused, disabled, cancelled, dispatched or rerun.
- No existing PR was modified, merged or closed.
- No private diagnostic report was opened, overwritten or deleted.
- No application code, production configuration, database, source profile or existing automation was changed.
- Only this documentation report is proposed for addition through a new audit PR.

**Step 4 status:** REPORT DOCUMENTED; AWAITING USER REVIEW.  
**Freeze status:** NOT EXECUTED.  
**Step 5 status:** NOT STARTED.  
**Stop condition:** do not execute this freeze or begin Step 5 without explicit user approval.
