# Phase 1A — Read-only Production Evidence Acquisition Plan

**Prepared:** 2026-10-31  
**Repository:** `mattari20/estudent-listing`  
**Production branch:** `main`  
**Application:** `https://estudent.pk/institutes/`  
**Repository baseline reviewed:** `b878416826bdf62c26ad27d040f254461d3cf126`  
**Strategy dependency:** PR #958, branch `strategy/step5-master-completion-2026-10-31`, head `3bcae18604c9ff3dd2f3f9d93cb9a07d506716f3`  
**Status:** PLAN ONLY — NO PRODUCTION ACCESS OR COMMAND EXECUTION AUTHORIZED

> This plan defines a controlled evidence collection sequence. It does not authorize SSH, production Artisan, database access, workflow dispatch, Hostinger UI changes, source retrieval, or any production operation. All commands below are examples for a future separately authorized session; none were executed while preparing this document.

## 1. Purpose and boundaries

Phase 1A establishes a trustworthy, current production-operational baseline before any source discovery or write-capable automation is resumed. The required result is an evidence package that separates:

- current directly observed facts;
- repository configuration and code facts;
- historical diagnostic evidence;
- facts that remain unverified;
- decisions that require a separate authorization.

The goal is to prove the actual deployed code/runtime, the database schema and migration ledger, scheduler and queue ownership, automation run state, and monitoring/backup posture. It is not a remediation, freeze, resume, migration, source qualification, data collection or deployment phase.

### 1.1 Hard restrictions

The following remain prohibited in Phase 1A unless a distinct future authorization explicitly changes a specific restriction:

- No production Artisan command or PHP application bootstrap.
- No SSH session to Hostinger.
- No production database connection or query.
- No workflow dispatch, rerun, disable, enable, cancellation or auto-merge change.
- No Hostinger cron, Supervisor, queue, environment or application configuration changes.
- No source retrieval, qualification, discovery, monitoring batch, candidate staging or import.
- No mutation or disposition of GCU candidate rows #348–353 or batches #19–23.
- No activation of the additional 20 HEC identity profiles.
- No migrations, deploy, cache/config/route clear, restart or queue operation.
- No approval, identity resolution, canonical promotion, reversal or publication.
- No overwriting, deleting, re-probing or repeating a successful private-storage diagnostic artifact/probe.

A read-only *intent* is not enough. Any mechanism that writes a report, temporary file, Git metadata, private repository commit, queue record or other state must be classified as a write to that system, even if the production database is not changed.

## 2. Evidence collection protocol

### 2.1 Evidence labels

| Label | Meaning |
|---|---|
| VERIFIED — CURRENT | Directly observed during the authorized Phase 1A evidence window, with timestamp and source |
| VERIFIED — REPOSITORY | Confirmed in a specific Git commit/file; does not prove live production state |
| HISTORICAL | A previous report/run; useful context but not current acceptance |
| INFERRED | A conclusion derived from multiple facts; retain the reasoning |
| UNVERIFIED | No current direct evidence |
| BLOCKED | Evidence cannot safely be acquired or prerequisites are absent |
| NOT APPLICABLE | Explicitly justified, not silently omitted |

Every report item must include observation time (UTC and PKT), source/method, exact revision or run ID where applicable, result, limitations and evidence reference. Redact credentials, connection strings, tokens, personal data, database row values and sensitive source content.

### 2.2 Evidence integrity

- Capture exact commit SHAs, workflow run IDs, check names, status/conclusion, timestamps and URLs.
- Preserve original diagnostic artifacts. Do not replace an existing path or report.
- Store sanitized evidence only in an approved private location. Never upload production diagnostics to a public PR artifact.
- Record checksums for exported evidence where a file is intentionally retained.
- Use a single acquisition window; if an observation is stale or the deployed revision changes during collection, mark the affected sections BLOCKED and stop.
- Do not treat a green CI run as production evidence.
- Do not infer a successful cron/worker from a workflow definition or a configured queue driver.

### 2.3 Report destination and retention

**Proposed report:** `mattari20/institutes-private-reports/reports/phase-1a-production-readiness-YYYYMMDDTHHMMSSZ.json`, with a human-readable sanitized summary at `reports/phase-1a-production-readiness-YYYYMMDDTHHMMSSZ.md`.

This is a proposed destination only. Before writing any report, confirm the private repository path, access controls, encryption/access policy and an approved retention period. The existing private-report workflow currently writes timestamped data-gap reports under `reports/data-gap-report-<timestamp>.json`; that is a different report contract and must not be overwritten or repurposed. The private Git repository's actual retention policy was not established by this audit. Do not claim indefinite or time-limited retention until verified.

The existing Hostinger diagnostic workflow has a separate protected application path, `storage/app/private/diagnostics/phase-5b18-artisan-runtime.json`, and explicitly refuses to overwrite it. Do not probe for, replace or recreate this file. If a prior report is available through an already authorized private evidence route, preserve its original timestamp and classify it as historical unless its generation time falls within the approved Phase 1A window.

## 3. Evidence checklist — nine workstreams

### 3.1 Deployed application revision and code parity

**Questions to answer**
- What exact Git commit is checked out by the deployed Institutes application?
- What is the latest main commit that changes application/runtime code (not docs-only commits)?
- Do tracked application paths match the intended application-code revision?
- Are there tracked, staged or untracked local changes? Are there deployment files not represented by the repository commit?
- Is the deployed checkout's Git metadata being modified by diagnostics?

**Exact evidence required**
- Deployed `git rev-parse HEAD` (commit SHA only).
- Expected application-code SHA derived from a reviewed main revision and the agreed application path set: `app bootstrap config database resources routes composer.json composer.lock public artisan`.
- Read-only comparison result for those paths between deployed checkout and target commit.
- `git status --short` and tracked diff summaries, without file contents or secrets.
- Confirmation that parity comparison used a disposable temporary repository, not `.git` in the live checkout.
- Timestamp, project path identifier and PHP/Git executable versions.

**Existing mechanism**
- `.github/workflows/hostinger-readonly-diagnostics.yml` has a scheduled six-hour trigger, main-push trigger and manual dispatch. Its source resolves the expected application revision from application paths, uses a disposable temporary parity repository for fetch, and performs a Hostinger SSH diagnostic.
- `.github/workflows/automated-live-integrity-audit.yml` also compares deployed code but fetches missing objects into the deployed checkout's `.git`; do not use it as the Phase 1A parity mechanism until that side effect is removed.
- `.github/workflows/scheduled-institute-discovery.yml` performs fetch/unshallow operations in the deployed checkout and is a write-capable discovery workflow; do not run it for this evidence task.

**Read-only classification**
- Git revision/status/diff commands are intended read-only for application files, but Git fetch into the live checkout mutates `.git` metadata.
- The Hostinger diagnostic uses a disposable temp Git repository, but creates/deletes temporary directories and writes a protected runtime report. It is therefore **not strictly read-only at the filesystem level**.
- Any SSH access is privileged production access and requires explicit authorization.

**Authorization**
- Separate explicit authorization for one bounded SSH read-only session, exact project path, exact command allowlist, time window and named operator.
- Explicit approval that a new diagnostic output path may be written in private storage; otherwise do not execute a report-writing diagnostic.
- No permission to fetch into or modify deployed `.git`.

**Expected evidence location / retention**
- Sanitized revision/parity summary in the proposed Phase 1A private report.
- Raw diagnostic output, if approved, remains private and access-controlled; retain only according to the confirmed private-report policy.
- Do not overwrite the existing Phase 5B.18 report path.

**Stop conditions**
- Expected SHA cannot be derived unambiguously.
- Deployed HEAD or application tree differs from target or local drift is present.
- The only available method fetches into deployed `.git`.
- Existing report destination is occupied or report retention/access policy is unknown.
- Project path, SSH identity or deployed checkout cannot be established safely.

### 3.2 PHP and Laravel runtime

**Questions to answer**
- Which PHP binary/version is used by the deployed web and CLI contexts?
- Which Laravel/framework version and application environment are reported?
- Are CLI and web PHP versions/configurations consistent where relevant?
- Are required PHP extensions and Composer dependencies available?

**Exact evidence required**
- PHP CLI binary path and `php -v` output.
- PHP CLI module list limited to extension names/versions; no environment dumps.
- Laravel version from `php artisan --version` only if separately approved.
- `composer show laravel/framework --no-interaction` or locked version evidence from repository metadata; distinguish deployed vendor state from lockfile.
- Sanitized environment name only; never expose `.env`, APP_KEY, database credentials, tokens or full `phpinfo()`.

**Existing mechanism**
- Hostinger read-only diagnostics selects `/opt/alt/php85/usr/bin/php` and runs `artisan env` after its runtime diagnostic script.
- Repository `composer.lock` provides the intended dependency version, not proof of deployed vendor state.

**Read-only classification**
- `php -v`, `php -m` and reading lockfile metadata are normally read-only.
- `artisan --version` and `artisan env` bootstrap Laravel. They are not intended data mutations, but service providers/bootstrap hooks may execute; treat them as **potentially side-effecting until reviewed**.
- `phpinfo()` and full environment output are prohibited due to secret disclosure risk.

**Authorization**
- Explicit approval for the exact PHP binary and the specific version/module commands.
- Separate approval for any Artisan bootstrap command; do not infer this from SSH permission.

**Expected evidence location / retention**
- Sanitized version/module summary in the Phase 1A private report.
- Do not retain full environment/config dumps. Retain only exact versions and required extension names.

**Stop conditions**
- PHP binary differs from the approved path or unexpected CLI/web mismatch appears.
- Laravel bootstrap emits warnings/errors or attempts unexpected writes/network access.
- Output includes secrets, environment values or personal data.
- Runtime version is unsupported or cannot be verified.

### 3.3 Artisan command registration and relevant command help

**Questions to answer**
- Are the required commands registered in the deployed revision?
- Does deployed command help match the reviewed source contract?
- Are command signatures/options and dry-run/confirmation gates as expected?
- Is the diagnostic invoking only list/help operations rather than a command that performs work?

**Exact evidence required**
- Bounded `php artisan list --raw` output filtered to named Institutes commands.
- `php artisan help` output for only the approved diagnostic/read-only commands, including exact signatures/options.
- Registration evidence for:
  - `institutes:data-integrity-report`
  - `institutes:offering-gap-preflight`
  - `institutes:offering-gap-reconcile`
  - `institutes:bootstrap-agent`
  - `institutes:monitor-changes`
  - `institutes:discover` and `institutes:refresh-discovery` (registration only; never execute)
- Explicit classification of every command as read-only, filesystem-writing, database-writing, source-contacting or canonical-writing based on source inspection.

**Existing mechanism**
- Hostinger read-only diagnostics contains command registration/help inspection and uses bounded temporary output files. It also runs `artisan route:list` and `artisan schedule:list`.
- `bootstrap/app.php` registers `app/Console/Commands` and defines two hourly scheduler commands.
- Source inspection of command classes is safer than executing commands.

**Read-only classification**
- Static repository inspection is read-only.
- Artisan list/help commands do not intentionally invoke command handlers, but boot the deployed Laravel application; classify as **conditionally read-only / bootstrap side effects not independently excluded**.
- Temporary output files are filesystem writes on the runner/host.
- Never execute a command merely to learn its signature.

**Authorization**
- Explicit per-command authorization for Artisan list/help bootstrap, including the exact command names and output limits.
- No authorization for any command handler, scheduler run, discovery, monitoring, qualification, preflight or reconciliation command execution.

**Expected evidence location / retention**
- Sanitized command names/signatures and source references in Phase 1A private report.
- Do not store full help output if it includes environment-specific paths or sensitive values.

**Stop conditions**
- A command appears to execute a handler, call a source, write data or alter cache/storage.
- Required command is missing or signature differs from reviewed source.
- Output contains credentials, private paths not required for acceptance, or sensitive arguments.
- Artisan bootstrap fails or causes unexpected side effects.

### 3.4 Database schema, required tables, indexes and migration ledger

**Questions to answer**
- Are required tables and indexes present with expected definitions?
- Do column types, nullability, defaults, indexes and constraints match migration contracts?
- Are relevant migration ledger entries present and consistent?
- Are queue tables present if the database queue driver is in use?
- Are schema readiness claims supported without reading candidate or institution content?

**Exact evidence required**
- Database engine/version and selected database identifier (redacted).
- Table existence for the specific required schema objects only.
- Column metadata: name, type, nullable, default, key/extra fields.
- Index metadata: index name, uniqueness, ordered columns.
- Foreign key metadata only for the selected tables.
- Exact migration names/batch numbers for the relevant migrations, including official source refresh and `institution_field_approvals` where present.
- Queue table presence and counts only if later approved; never fetch job payloads or failed exception content.
- Explicit proof of database principal grants restricted to SELECT on the necessary metadata/tables.

**Existing mechanism**
- Hostinger diagnostics includes PHP/Laravel schema checks for selected tables and migration entries. It may bootstrap the application and connect to production DB.
- `.github/workflows/offering-gap-preflight.yml` runs read-only-intent Artisan preflight/reconciliation commands against DB secrets, but actual SELECT-only grants are unverified and the workflow has schedule, push and manual triggers. **Do not dispatch or rely on it until grants are independently established.**
- Historical Step 1/2 reports identify earlier schema/ledger blockers; those are historical, not current schema evidence.

**Read-only classification**
- SQL `SELECT` against `information_schema`, `SHOW` metadata and bounded migration-ledger reads are read-only at the SQL statement level.
- The actual credential's grants are currently unverified. Do not call a connection read-only based solely on a workflow name, username or command contract.
- Laravel schema facades and Artisan bootstrap may run application bootstrap code. Prefer a dedicated DB account with SELECT-only grants and a bounded query allowlist.
- No migrations, schema changes, inserts, updates, deletes, locks or transactions that write are permitted.

**Authorization**
- Separate explicit authorization for a dedicated SELECT-only production DB credential and the exact metadata query allowlist.
- Credential owner must prove grants before connection. If the available credential cannot be proven SELECT-only, do not connect.
- No approval for row-level production data retrieval.

**Expected evidence location / retention**
- Sanitized schema metadata and migration names/batches only in the private Phase 1A report.
- Do not retain table contents, candidate values, credentials, exception payloads or personal data.
- Confirm retention policy before report creation.

**Stop conditions**
- SELECT-only grants cannot be independently proven.
- Query requires broad table scans, sensitive row content or elevated privileges.
- Required table/index/ledger mismatch is found; stop and report it—do not repair.
- Schema report unexpectedly contains personal data or production records.
- Any proposed command would run a migration, repair, import, source refresh or write.

### 3.5 Hostinger cron configuration and scheduler ownership

**Questions to answer**
- Is a Hostinger cron entry invoking Laravel `schedule:run` or any Institutes Artisan command?
- What exact command, frequency, timezone, enabled state, last-run state and owner are configured?
- Is there more than one scheduler entry or duplicate direct command?
- Does Hostinger hPanel show a scheduled task that invokes the same two hourly commands defined in Laravel?

**Exact evidence required**
- Read-only hPanel Cron Jobs screenshot/export showing full command (secrets redacted), schedule fields, timezone, enabled/disabled state and last-run/error metadata if available.
- Whether cron invokes `php artisan schedule:run`, `schedule:work`, or direct `institutes:bootstrap-agent` / `institutes:monitor-changes`.
- PHP binary and working directory, with credentials/path tokens redacted where needed.
- Hostinger plan limitations on process inspection and cron history.
- Cross-reference with Laravel scheduler definitions in the exact deployed revision.

**Existing mechanism**
- `bootstrap/app.php` schedules `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly with `withoutOverlapping(55)`.
- Step 4 found that GitHub `scheduled-institute-discovery.yml` also invokes these commands hourly at minute 15, as well as through manual and `workflow_run` triggers.
- No verified current Hostinger Cron Jobs screenshot/export was available in the audit evidence.

**Read-only classification**
- Viewing hPanel cron configuration is read-only if no Save/Edit/Delete action is taken.
- Do not open an edit form if it risks changing values; do not click save, toggle, pause, delete or test-run.
- Static source inspection is read-only. No `schedule:run`, `schedule:work` or Artisan scheduler execution is permitted.

**Authorization**
- User must provide the current hPanel screenshot/export or separately authorize a named operator to view the Cron Jobs page without edits.
- No authorization for changing, disabling, enabling or manually running cron.

**Expected evidence location / retention**
- Redacted screenshot/export and a normalized cron inventory in the private Phase 1A evidence package.
- Retain only the redacted copy under the approved private retention policy.

**Stop conditions**
- Full command or timezone cannot be established.
- Cron entry appears to run source retrieval, monitoring, imports or unknown commands.
- hPanel shows duplicate or overlapping entries.
- Any UI step would save, toggle, execute or alter a cron job.
- Do not choose a scheduler owner until all configured execution paths are accounted for.

### 3.6 Queue worker, failed jobs and recovery arrangements

**Questions to answer**
- Which queue connection is deployed and which worker consumes it?
- Is there an active Supervisor/daemon/cron worker, or is queue mode synchronous?
- Are `jobs` and `failed_jobs` tables present?
- What retry-after, timeout, tries, backoff and restart behavior apply?
- How are failed jobs inspected and recovered without exposing payloads or replaying work?

**Exact evidence required**
- Deployed `QUEUE_CONNECTION` value only; never print the full environment file.
- Worker process/service definition, command, user, working directory, restart policy and last restart time.
- Supervisor configuration metadata if applicable (secrets/arguments redacted).
- Queue table existence, counts, oldest queued timestamp and failed-job counts using approved aggregate SELECTs only.
- Retry/timeout settings from deployed config and exact commit.
- Recovery runbook and proof that retry/replay is a separately approved operation.
- Confirmation whether any external cron or hosting service runs `queue:work`.

**Existing mechanism**
- Repository `config/queue.php` defaults to the database driver and names `jobs` and `failed_jobs`.
- Step 4 found no `app/Jobs/` classes and no `Console/Kernel.php`; this does not prove no worker exists in hosting.
- No current Hostinger worker/Supervisor evidence was available.
- The `DataReverificationQueue` domain table is not evidence of a Laravel queue worker.

**Read-only classification**
- Reading config source and hPanel process/service definitions is read-only.
- Process listing is read-only but may be unavailable on shared hosting.
- Aggregate SELECT counts can be read-only only with proven SELECT-only grants.
- Never run `queue:work`, `queue:listen`, `queue:retry`, `queue:forget`, `queue:restart`, `horizon:terminate` or any replay/cleanup command in Phase 1A.

**Authorization**
- Separate permission to inspect the hPanel process/cron/Supervisor view.
- Separate permission for SELECT-only aggregate queue counts after grants are proven.
- No permission to start, stop, restart, retry, clear or consume jobs.

**Expected evidence location / retention**
- Sanitized worker inventory and aggregate counts in private Phase 1A report.
- Do not retain job payloads, exception traces containing secrets or failed-job serialized data.

**Stop conditions**
- Worker ownership/consumer cannot be identified.
- Worker command is executing or could execute production jobs during inspection.
- SELECT-only grants are not proven.
- A failed job requires replay, cleanup or mutation to understand.
- Any process control action is presented as necessary.

### 3.7 GitHub workflow enabled state and active runs

**Questions to answer**
- Which production-affecting workflows are currently enabled?
- Which workflows are currently running, queued, waiting for approval or recently completed?
- Are any active runs invoking SSH, Artisan, production DB access, source retrieval, deployment or private report writes?
- Do current workflow definitions match the audited main SHA?

**Exact evidence required**
- Repository workflow inventory with workflow ID, filename, state (active/disabled), triggers and latest run ID/time/status/conclusion.
- Complete active run list across all workflows, including queued, in-progress, waiting and pending deployments.
- For each relevant run: event, branch, head SHA, actor, start time, job name, environment approval status and cancellation state.
- Workflow YAML revision and relevant environment protection/deployment rules.
- Exact run logs only where necessary, with secrets and production content redacted.

**Existing mechanism**
- GitHub Actions UI provides workflow enabled state and current run inventory.
- GitHub connector `fetch_commit_workflow_runs` returns PR-triggered runs for a supplied commit and only the first page; it is not a complete repository-wide active-run inventory and does not expose workflow enabled state.
- Step 4 inventoried 35 workflow files and eight scheduled definitions, but current enabled state and complete active runs were explicitly unverified.
- PR checks in this document are CI evidence only; they are not a complete production workflow inventory.

**Read-only classification**
- Viewing GitHub Actions pages, workflow metadata and run summaries is read-only.
- Opening logs is read-only but may expose sensitive data; inspect only sanitized job summaries unless an exact log section is necessary.
- Do not use buttons for rerun, cancel, disable, enable, approve deployment or dispatch.

**Authorization**
- GitHub read-only inspection is permitted for the named repository and workflow/run metadata.
- Any log access containing production data requires a separate approval and redaction plan.
- No workflow state or run action is authorized.

**Expected evidence location / retention**
- Workflow/run inventory with links and sanitized metadata in Phase 1A private report.
- Preserve existing run artifacts under their existing retention policy; do not download sensitive artifacts unless separately approved.

**Stop conditions**
- Active production-affecting run is found: record it and stop before any proposed freeze/resume action.
- Workflow state or complete run list cannot be established.
- A run requires approval or action to reveal more information.
- Any UI/API operation would mutate run/workflow state.

### 3.8 GitHub/Laravel duplicate execution risk

**Questions to answer**
- Are GitHub scheduled and Hostinger/Laravel scheduler paths both active?
- Do they invoke the same commands or overlapping source/data operations?
- Is there a genuinely shared lock across runners, and is its backend configured identically?
- Are operations idempotent under simultaneous start, timeout, retry and partial failure?

**Exact evidence required**
- Deployed `bootstrap/app.php` scheduler definition and cache/lock backend configuration (no secret values).
- GitHub workflow trigger map and exact Artisan invocations.
- Hostinger cron inventory from §3.5.
- Workflow concurrency group and Laravel `withoutOverlapping(55)` semantics.
- Proof of a shared database/Redis lock key and atomic acquisition/release behavior, or a documented single-scheduler owner.
- Idempotency keys/unique constraints and tests for overlapping invocations.
- Current active run inventory from §3.7.

**Existing mechanism**
- Static review of `bootstrap/app.php`, `config/cache.php`, queue config and `.github/workflows/scheduled-institute-discovery.yml`.
- Step 4 found separate GitHub workflow concurrency and Laravel scheduler locks; no shared cross-runner lock was demonstrated.
- The scheduled GitHub workflow invokes `institutes:bootstrap-agent` and `institutes:monitor-changes`, which are also Laravel scheduled commands.

**Read-only classification**
- Static source/config inspection is read-only.
- Reading hPanel cron and GitHub run metadata is read-only.
- Do not launch parallel runs or conduct concurrency tests against production. Any concurrency test must be isolated in CI/staging.

**Authorization**
- No production execution authorization needed for static repository analysis and read-only metadata review.
- Separate approval required for any production scheduler change or test; not part of Phase 1A.

**Expected evidence location / retention**
- Trigger/dependency graph and lock assessment in Phase 1A private report.
- Test evidence should come from isolated CI/staging and be referenced by commit/run ID.

**Stop conditions**
- More than one active execution plane is found without a shared lock.
- Lock backend or shared key cannot be verified.
- Active run overlap is detected.
- Idempotency is not proven. Mark BLOCKED; do not pause or alter either plane.

### 3.9 Production monitoring and backup evidence

**Questions to answer**
- Which public monitor and private diagnostic report mechanisms are configured?
- What are their latest successful/failed run IDs, timestamps and artifact retention?
- Does public monitoring test only HTTP availability or also production revision/data integrity?
- Are Hostinger backups enabled, what is their frequency/retention, and is a restore test recorded?
- Are private reports retained and access-controlled?

**Exact evidence required**
- Public monitor workflow state and latest run ID/conclusion; sanitized public HTTP summary.
- Private diagnostics workflow latest run and timestamp; report filename/checksum in the private report repository (metadata only until authorized to inspect contents).
- Hostinger backup settings screenshot/export: schedule, backup scope, retention, storage destination, last successful backup.
- Latest successful restore-test record and restore scope, if one exists.
- Incident/escalation owner and monitoring cadence.
- Evidence retention/access policy for private diagnostics.

**Existing mechanism**
- `.github/workflows/institutes-public-monitor.yml` runs public HTTP checks and uploads `institutes-public-health-report` with a 14-day artifact retention. It explicitly does not query production DB or prove deployment commit.
- `.github/workflows/institutes-private-diagnostics-report.yml` runs daily and retrieves the authenticated diagnostics endpoint, then writes timestamped reports under `mattari20/institutes-private-reports/reports/data-gap-report-<timestamp>.json`. It does not publish a public artifact. This is a private-repository write, not a production DB write.
- `.github/workflows/hostinger-readonly-diagnostics.yml` writes a protected report under production private storage and temporary files; do not repeat successful storage probes or overwrite its existing output.
- No current Hostinger backup/retention or restore evidence was established in Step 4.

**Read-only classification**
- Public monitor HTTP GET checks are read-only to production data, but they create runner files/artifacts and may retry requests.
- Private diagnostics GET reads a protected production endpoint but writes a new private repository file/commit.
- Viewing existing run summaries, artifact metadata and Hostinger backup settings is read-only.
- A restore test is not read-only: it can overwrite data/environment. Do not run one in Phase 1A.

**Authorization**
- Read-only inspection of existing workflow runs and report metadata is allowed.
- Access to private report contents requires separate explicit authorization and must avoid opening/repeating previously successful private-storage probes.
- User-provided redacted Hostinger backup screenshots are preferred; otherwise authorize a named operator to view settings only.
- No backup configuration change, restore, report creation or endpoint request is authorized by this plan.

**Expected evidence location / retention**
- Sanitized monitor/backup metadata in Phase 1A private report.
- Existing public monitor artifact retention is 14 days per workflow YAML.
- Private report retention is not verified; confirm the private repository's retention policy before creating any new report.
- Backup evidence remains in Hostinger's existing backup interface; retain a redacted evidence copy only under the approved private policy.

**Stop conditions**
- Latest monitoring result is missing/stale or its scope is misunderstood as deployment proof.
- Private diagnostic endpoint/report access would repeat a successful probe or expose sensitive report contents.
- Backup retention or last-success evidence is unavailable.
- Restore testing is proposed without a separate recovery plan and explicit authorization.
- Any monitoring/backup UI action would change settings or run a test.

## 4. Consolidated evidence matrix and execution order

| Order | Evidence group | Preferred source | Read-only confidence | Current status from audits | Gate |
|---:|---|---|---|---|---|
| 1 | GitHub workflow states and active runs | GitHub Actions UI/API read-only | High for metadata | UNVERIFIED complete inventory | Required before any operational pause/resume decision |
| 2 | Deployed revision and parity | Approved one-shot Hostinger diagnostic using isolated temp Git repo | Conditional; writes private/temp files | UNVERIFIED current; prior diagnostic delivery failure noted | Required before DB/runtime acceptance |
| 3 | PHP/Laravel runtime | Allowlisted PHP version/module commands; approved Artisan version only | PHP commands high; Artisan conditional | UNVERIFIED current | Required before Artisan command review |
| 4 | Artisan registration/help | Static source first; bounded Artisan list/help only if approved | Conditional due bootstrap | Partial repository facts; deployed registration unverified | Required before any later diagnostic command selection |
| 5 | Schema/ledger/indexes | SELECT-only DB principal and bounded metadata queries | High only after grants proven | UNVERIFIED current | Required before any DB-backed operation |
| 6 | Hostinger cron/scheduler | User-provided redacted hPanel evidence | High if view-only | UNVERIFIED | Required before choosing scheduler owner |
| 7 | Queue/failed-job/recovery | hPanel worker metadata + SELECT-only aggregate counts | Conditional | UNVERIFIED | Required before any queue operation |
| 8 | Cross-runner locking | Static code/config + active-run and cron evidence | High for static facts; operational proof conditional | Duplicate execution risk confirmed at source level; active state unverified | Required before source automation resume |
| 9 | Monitoring/backups | GitHub run metadata + redacted hPanel backup evidence | High for metadata only | Monitoring configured; backup/retention unverified | Required before production readiness sign-off |

Recommended order is deliberately read-only and fail-closed. If any prerequisite fails, stop that branch of evidence collection rather than broaden access or substitute an unverified credential.

## 5. Authorization register for any future execution

The following authorizations are **not granted by this document**. They must be requested and approved individually before execution:

| Authorization ID | Exact scope requested later | Explicit exclusions |
|---|---|---|
| AUTH-01 | Read-only GitHub Actions metadata review for all workflows and active runs in `mattari20/estudent-listing` | No dispatch, rerun, cancel, approve, enable, disable, merge or auto-merge |
| AUTH-02 | One bounded Hostinger SSH session for approved revision/status and PHP version commands at the exact Institutes project path | No Artisan, DB, cron, queue, source or file writes unless separately authorized |
| AUTH-03 | Bounded Artisan bootstrap for named list/help/version commands after source review | No command handler, schedule execution, discovery, monitoring, import, qualification or write command |
| AUTH-04 | Production DB metadata SELECT using a credential whose SELECT-only grants are proven before connection | No data-row retrieval, writes, migration, lock/write transaction or queue payload |
| AUTH-05 | Read-only Hostinger hPanel viewing of Cron Jobs, worker/process and backup settings | No save, edit, toggle, run, pause, delete, restart or restore |
| AUTH-06 | Creation of a new sanitized Phase 1A report in the named private repository path after retention/access approval | No public artifact, overwrite, deletion or repeat of successful private-storage probes |

A single general statement such as “continue Phase 1A” must not be interpreted as authorization for all six categories. The user must explicitly name which authorization IDs are approved. Any new command or access path requires renewed approval.

## 6. Stop/go decision rules

Phase 1A evidence acquisition is complete only when all required evidence groups have a current status and source reference. A group may be BLOCKED/UNVERIFIED, but the report must explain why and list the exact missing evidence.

**GO to a separate Phase 1B design/recommendation review only if:**
- deployed application revision and application-code parity are established;
- PHP/Laravel runtime and Artisan command registration are verified;
- schema/index/ledger metadata is verified through a proven SELECT-only path;
- Hostinger cron and queue worker ownership are known, or explicitly confirmed absent;
- GitHub active runs and enabled workflow state are inventoried;
- cross-runner duplicate execution risk has a controlled owner/shared lock proposal;
- monitoring and backup evidence/retention are recorded;
- no safety restriction was breached and every unresolved issue is listed.

**STOP / BLOCKED if:**
- production identity, project path, revision, schema or credentials are uncertain;
- SELECT-only DB access cannot be proven;
- active production runs or duplicate scheduler execution could overlap;
- diagnostic output path is occupied, private retention is unknown or report contains sensitive data;
- any command has unreviewed side effects;
- evidence requires a prohibited production write or source operation.

Phase 1A completion does **not** authorize a freeze, resume, migration, deployment, source qualification, source activation, discovery, candidate staging or canonical change. Each is a separate future decision.

## 7. Required final Phase 1A evidence report format

The eventual evidence report must contain:

1. Executive status and exact observation window.
2. Repository/main SHA and relevant workflow YAML SHAs.
3. Five evidence-state labels: current, repository, historical, inferred, unverified/blocked.
4. Deployed revision/parity result and method.
5. PHP/Laravel runtime and Artisan registration result.
6. Schema/table/index/migration ledger result with grant evidence.
7. Hostinger cron inventory and scheduler ownership.
8. Queue worker, retry, failed-job aggregate and recovery status.
9. GitHub workflow enabled/active run inventory.
10. Cross-runner lock/idempotency assessment.
11. Monitoring, backup and retention evidence.
12. Stop conditions encountered, limitations and unresolved blockers.
13. Explicit decision: **PASS**, **PASS WITH BLOCKERS**, or **NOT YET** for evidence acquisition only.
14. A separate statement that no write, migration, import, source retrieval, approval, promotion or publication was performed.

## 8. Current status and next action

| Item | Status |
|---|---|
| Phase 1A plan authored | COMPLETE |
| GitHub PR metadata/check review for #954–#958 | COMPLETE for current connector-visible evidence |
| Auto-merge state per individual PR | UNVERIFIED; repository setting allows auto-merge, but connector does not expose each PR's auto-merge field |
| Hostinger revision/runtime | UNVERIFIED |
| Production schema/ledger | UNVERIFIED |
| Hostinger cron/worker | UNVERIFIED |
| Current complete workflow enabled/active inventory | UNVERIFIED |
| Backups/retention/restore | UNVERIFIED |
| Production command/SSH/DB access | NOT PERFORMED |
| Phase 1A execution | NOT STARTED |

**Next action:** review the documentation PR and approve only the specific AUTH IDs needed for a later read-only evidence session. Do not start source automation or a production freeze as a side effect of Phase 1A.
