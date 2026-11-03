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


---

## Phase 1A-05B evidence addendum — owner-supplied Hostinger screenshots (2026-11-02)

This addendum records owner-supplied hPanel/File Manager screenshots received on 2026-11-02. It supplements, and does not replace, the evidence requirements or authorization boundaries above.

### Observed screenshot facts

- Backup interface: automated backups labelled Weekly; latest displayed website backup `2026-11-01 18:05`; next displayed backup `2026-11-08`; website backup described as including website and database; separate Files and Database backup views; database choices include `u537436044_Institutes` and `u537436044_cvbuilder`; previous displayed weekly dates include 2026-10-28, 2026-10-21, 2026-10-14 and 2026-10-07.
- Backup exclusions warning: backup-plugin archives, cache and database export files are excluded. Manual backups are shown as locked.
- Hosting plan display: server `server1278`, server location Singapore, backup location India (Mumbai), 25 GB disk, 2048 MB RAM, 1 CPU core, 40 PHP workers, 80 maximum processes, website IP `185.232.14.237`.
- Cron Jobs page capture: Create a New Cron Job form shown; no saved-job rows visible in that captured view. This is not a complete saved-cron inventory and does not establish that no jobs exist.
- File Manager capture: `public_html/institutes/storage/app/private` and visible child directories `diagnostics`, `institute-discovery-review-packages`, `phase2-offering-gap-audits`, `source-qualification`.

### Evidence classification and limits

All facts above are **owner-supplied screenshot evidence**, not independently verified Hostinger API data. Screenshot capture timestamps/timezone were not supplied. Hosting plan allocations are not live resource/process measurements. The backup screenshots do not establish complete file inclusion, retention duration, separately verified storage location, recoverability or a successful restore test. The Cron Jobs capture does not establish the complete saved-job inventory. The File Manager capture does not establish web access protection, private-report access controls or retention. No directory/report was opened.

### AUTH-05 status after this addendum

| Evidence item | Status |
|---|---|
| Backup frequency / displayed latest and next dates | OWNER-SUPPLIED SCREENSHOT EVIDENCE |
| Backup scope description and separate Files/Database views | OWNER-SUPPLIED SCREENSHOT EVIDENCE |
| Backup retention duration | UNVERIFIED |
| Successful restore test | UNVERIFIED |
| Saved Hostinger cron entries/schedules | UNVERIFIED |
| Laravel scheduler invocation | UNVERIFIED |
| Persistent queue worker/process configuration | UNVERIFIED |
| Queue worker status / failed-job aggregates | UNVERIFIED |
| Private storage directory names | OWNER-SUPPLIED SCREENSHOT EVIDENCE |
| Private report access controls and retention | UNVERIFIED |

P1A-05 remains **PARTIAL**. P1A-08 remains **PARTIAL**. P1A-09 remains **NOT STARTED**. Phase 1A is not complete.

No AUTH-02, AUTH-03, AUTH-04 or expanded AUTH-05 operation is authorized by this addendum. No production terminal, Artisan, DB, cron, queue, backup, private-report or automation action was performed.


---

## Phase 1A Joint Evidence Completion Batch — six workstreams (2026-11-02)

The consolidated evidence matrix, exact run references, scheduler assessment, AUTH-04 SQL, remaining authorization requests and Phase 1B design-only recommendation are recorded in the corresponding dated section of `docs/PROJECT_MASTER_TRACKER.md`. That tracker section is the single detailed report for this batch; this plan addendum records the gate result and evidence-source boundaries.

### Verified baseline

- PR #965 merged at `26a315bae463a1551338bed351cfc10eb7357abd`; live `main` independently verified at the same SHA.
- PR #965 exact-head Test #41454822626, Laravel CI #41454822743 and Project Controller #41454822336 succeeded.
- Current-main push Test #41462213594, Laravel CI #41462213601 and Project Controller #41462213858 succeeded.
- Compare from the previously inspected AUTH-02 production SHA `9fb1d2bef215b14aba02b2fdd91bf1422828303d` to current main contains documentation-only changes. This does not refresh the production SHA/runtime observation.

### Six-workstream gate result

| Workstream | Status | Evidence boundary |
|---|---|---|
| P1A-05C Hostinger cron | PARTIAL | Owner screenshot does not show saved entries; no complete cron inventory |
| P1A-06 Queue worker | UNVERIFIED | Queue defaults are repository facts; no deployed worker/process or failed-job aggregate evidence |
| P1A-01D GitHub state | PARTIAL | 35 workflow files/eight scheduled definitions and run metadata inspected; enabled state unavailable through the connector |
| P1A-02/03 runtime | PARTIAL | AUTH-02 terminal evidence remains valid for its historical observation; no new production command; AUTH-03 not authorized |
| P1A-04 schema | NOT AUTHORIZED | Migration contracts reviewed; historical discrepancies remain; no production DB query; exact SELECT request in tracker |
| P1A-08 backup/private storage/monitoring | PARTIAL | Owner screenshots and public/private monitor metadata recorded; retention/restore/private controls unresolved |

### Scheduler and monitoring decision

Two independent hourly execution paths remain visible in repository source: Laravel scheduler commands `institutes:bootstrap-agent` and `institutes:monitor-changes`, and GitHub `scheduled-institute-discovery.yml` invoking the same commands. GitHub concurrency does not establish a shared Laravel scheduler lock. Actual Hostinger cron invocation and simultaneous execution remain unverified. Do not change scheduler ownership in Phase 1A.

### Outstanding gates

- Obtain complete saved Hostinger cron list and view-only worker/process configuration; this is within the previously described AUTH-05 view-only scope, not an expansion.
- Obtain GitHub Actions UI evidence for enabled/disabled state of all 35 workflows.
- Obtain explicit separate AUTH-03 approval for the exact Artisan list/help/schedule-list commands.
- Obtain explicit separate AUTH-04 approval for the bounded metadata-only SELECT statements recorded in the tracker, after SELECT-only grants are proven.
- Any failed-job aggregate requires a separately named SELECT-only approval; no payload/exception fields.
- Backup retention, successful restore-test evidence and private report access/retention must be supplied or remain unverified. No restore or private report access is authorized.
- AUTH-06 is not requested or executed; no report file was written to the private repository.

**Phase 1A evidence decision: NOT YET COMPLETE.** No completion percentage is calculated because there is no approved weighting model and several critical gates remain unverified or unauthorized. Phase 1B may begin with design-only planning, but no production or source operation is authorized.

No production access, Artisan, database, cron, queue, backup, private report, workflow state or source operation was performed in this batch. Preserve GCU rows #348–353 and batches #19–23; keep the 20 additional HEC profiles and AI extraction disabled.


---

## Phase 1A-05C — Hostinger cron and worker evidence update (2026-11-03)

**Baseline:** live `main` verified at `cb0ad9c88fbdbd92825f9ef4da18c2f0571acac4`; PR #966 confirmed merged. The matching detailed evidence record is appended to `docs/PROJECT_MASTER_TRACKER.md` under “Phase 1A-05C — Hostinger cron and worker screenshot evidence (2026-11-03)”.

### Owner-supplied screenshot observations

- **Cron Jobs page:** screenshot for `estudent.pk` at Websites → Advanced → Cron Jobs shows the “Create a New Cron Job” form and no saved cron-job rows in the visible capture. Classification: `NO_SAVED_CRON_ENTRIES_VISIBLE_IN_OWNER_SUPPLIED_SCREENSHOT`. This does not establish that no cron jobs exist. Saved commands, PHP executable paths, schedules, timezone, enabled state and last/next execution remain unknown.
- **Website dashboard:** screenshot displays deployment Completed, repository `estudent-listing`, branch `main`, abbreviated commit `cb0ad9c`, root `public_html/institutes`, and resource usage. No persistent queue-worker or process-manager configuration is visible. Classification: `NO_WORKER_MANAGEMENT_INTERFACE_VISIBLE_IN_OWNER_SUPPLIED_DASHBOARD_SCREENSHOT`. This does not establish that no worker process exists or reveal its runtime state.

These are owner-supplied UI observations, not direct production server/process evidence. No credentials or secrets are included.

### Updated gate assessment

| Gate | Status | Evidence and limitation |
|---|---|---|
| P1A-05C — Hostinger cron inventory | **PARTIAL** | Cron Jobs page screenshot supplied; no saved rows visible. Actual saved cron configuration and execution state remain unverified. |
| P1A-06 — Persistent queue worker | **UNVERIFIED** | No worker/process management interface visible in the supplied dashboard screenshot. Existence and runtime status of a persistent Laravel queue worker remain unknown. |
| P1A-07 — Scheduler ownership | **PARTIAL** | Laravel and GitHub schedules remain repository facts; Hostinger cron configuration is unknown, so cross-runner ownership/overlap remains unresolved. |

Preserve these repository facts: Laravel schedules `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly; GitHub separately invokes them through `scheduled-institute-discovery.yml`; GitHub concurrency and Laravel `withoutOverlapping(55)` are separate mechanisms; Hostinger PHP worker allocation does not prove a Laravel queue worker; queue defaults are database driver and `default` queue. GCU rows #348–353 and batches #19–23 remain preserved; the 20 additional HEC profiles and AI extraction remain disabled.

### Outstanding evidence / authorization gates

- Complete redacted saved Cron Jobs inventory with command, PHP executable, schedule, timezone, enabled state and last/next run where visible.
- View-only worker/process/Supervisor evidence or evidence that the relevant management feature is unavailable; a missing UI interface does not prove absence of a process.
- GitHub workflow enabled/disabled state for all 35 workflows remains outstanding.
- AUTH-03 Artisan registration/help/schedule inspection remains not authorized; AUTH-04 metadata-only database inspection remains not authorized and requires proven SELECT-only grants. No expansion of AUTH-05 view-only scope.
- Production runtime/schema, backup retention and restore evidence, private-report access/retention remain unresolved as recorded in the tracker.

**Phase 1A remains incomplete.** This update is documentation-only. No production SSH, Artisan, Composer, SQL/database, hPanel operation, cron/queue operation, backup, private report, workflow action, deployment, configuration change, source retrieval/discovery, extraction, staging, approval, import, canonical promotion or publication was performed.


---

## Phase 1A-06 — Owner evidence batch and authorization map (2026-11-03)

The detailed owner evidence matrix, PR overlap review and nine-gate reconciliation are recorded in the matching Phase 1A-06 section of `docs/PROJECT_MASTER_TRACKER.md`. This addendum is the execution checklist; it does not grant new authorization or duplicate the Phase 1A-05D failure analysis.

### Owner-supplied evidence packet

| Item | Exact owner evidence | Source / evidence type | Authorization and limitation |
|---|---|---|---|
| A. GitHub workflow state | Complete inventory of all 35 workflow IDs/names/paths and active/disabled states; all queued, in-progress and waiting runs with run ID, workflow, event, branch, SHA, actor and start time. Paginate all results. | Repository Actions tab or owner/admin read-only GitHub API/CLI export. | Existing AUTH-01 read-only metadata scope. No enable/disable, dispatch, rerun, cancel or approval. Current connector did not expose a complete workflow-state inventory. |
| B. Hostinger cron | Complete saved Cron Jobs list: full command (secrets redacted), working directory, PHP binary, schedule/timezone, enabled state and last/next run/error fields where available. | Owner hPanel → Websites → estudent.pk → Advanced → Cron Jobs; full list screenshot/export. | Existing AUTH-05 view-only scope. Do not edit, save, toggle or test-run. Earlier create-form screenshot does not prove the list is empty. |
| C. Queue worker | Worker/service/Supervisor definition, command (sensitive args redacted), manager, process count/status, persistence, restart policy and last restart/heartbeat; if no UI feature, provider confirmation of feature limits. | Owner hPanel process/worker view or Hostinger support response. | Existing AUTH-05 view-only scope. No SSH/process listing/queue command/start/stop/restart/retry. Absence of UI does not prove no worker exists. |
| D. Scheduler ownership | Reconcile Hostinger cron list, GitHub workflow states/runs, deployed Laravel schedule definitions, lock backend/key and idempotency. Identify direct `schedule:run` or duplicate direct command entries. | Combined owner Hostinger evidence, GitHub metadata and static repository files. | Static/GitHub read-only review and AUTH-05 view-only evidence. Artisan `schedule:list` requires separate AUTH-03 approval; no production overlap test. |
| E. Backup retention/restore | Schedule, retention duration/restore-point count, scope/exclusions, storage destination, last successful backup, available restore points, and any existing restore-test record/date/scope/outcome. If none exists, say so. | Owner hPanel → Backups screenshots/export. | Existing AUTH-05 view-only scope. No restore or test restore; that is a write-capable operation requiring a separate recovery plan and explicit authorization. |
| F. Private report access/retention | Private repository identity/visibility, authorized users/teams and roles, relevant branch/ruleset protections, report retention/deletion policy and evidence of configured retention. Metadata only. | Owner/admin redacted GitHub Settings evidence and written policy. | No private report content, credential, token, endpoint or artifact access. AUTH-06 is only for a future new report after access/retention approval; no report is to be created now. |
| G. Hostinger run failure | Sanitized final error excerpt from only run `41552845301`, job `125644442102`, step “Run diagnostics on Hostinger”: final error category/message, failing phase if named, timestamp and exit status if shown. | Supplied directly by repository owner, or separately authorized named reviewer. | No raw log retrieval, secret-bearing log inspection or private report access. No retry/dispatch/cancel/state change. Until supplied, root cause remains unresolved. |

### Repository facts relevant to scheduler reconciliation

- `bootstrap/app.php` schedules `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly with `withoutOverlapping(55)`.
- `.github/workflows/scheduled-institute-discovery.yml` schedules at minute 15 hourly and also has manual dispatch and a workflow-run handoff trigger; it invokes the same two commands.
- The GitHub concurrency group and Laravel cache lock are separate. Repository `config/cache.php` defaults to file cache, but production environment overrides and a shared cross-runner lock are not established.
- `config/queue.php` defaults to database queue and names `jobs` / `failed_jobs`; it does not prove the deployed connection or a running worker.
- No actual Hostinger cron invocation or duplicate execution has been established.

### Stop decision

Repository-only evidence is exhausted for the missing Hostinger UI facts, GitHub workflow enabled state (via the current connector), private repository access/retention policy and the actual failed-step error. The next safe action is to obtain owner-supplied items A–G above. Do not substitute repository defaults, workflow definitions or successful CI for live production evidence.

**Gate status:** P1A-01 PARTIAL; P1A-02 PARTIAL; P1A-03 NOT AUTHORIZED; P1A-04 NOT AUTHORIZED; P1A-05 PARTIAL; P1A-06 UNVERIFIED; P1A-07 PARTIAL; P1A-08 PARTIAL; P1A-09 BLOCKED. Phase 1A remains incomplete.

No Hostinger/production access, private report or raw log access, workflow action, deployment, migration, cron/queue/backup operation, restore, source operation, candidate staging/approval/import, canonical promotion or publication occurred. Preserve GCU rows #348–353 and batches #19–23; keep the 20 additional HEC profiles and AI extraction disabled.


---

## Phase 1A-06 — Historical workflow reconciliation (2026-11-03)

**Verified main SHA:** `d302ecfabda8b8dc950d5aebb58d5a835e018a4b`  
**Current workflow-file inventory:** 35 files under `.github/workflows/` at that exact tree.  
**Scope:** read-only GitHub run/workflow-branch/commit/PR metadata and static YAML inspection. No workflow state or production operation was changed.

### Method and interpretation

- Enumerated the 35 workflow YAML paths from the recursive Git tree at the verified main SHA.
- Reconciled the 29 targeted historical workflow records against the current path set and latest run metadata.
- For each record, inspected the latest run's job summary. The latest run for each of the 29 records was a completed `push` run on its dedicated `ops/*` branch; each had one primary job and its named operational step concluded `success`.
- Each run's `pull_requests` array was empty. A commit-to-associated-PR lookup for each of the 29 exact head SHAs also returned an empty array. Therefore **no merged-PR association was found for any of these 29 run commits**.
- Commit comparisons against current main report `diverged` for all 29. The historical run commits are not ancestors of current main.
- All 29 corresponding branch names remain present in the repository branch search. The current tip SHA and presence of the historical YAML file at each branch tip were not inspected; a future push may retrigger a branch-scoped workflow if its YAML remains there.
- A successful job/step proves the workflow command returned success. It does not independently prove the exact production rows affected, their present state, or that a production change was subsequently reversed. No raw logs or production data were inspected.

### Record-by-record classification

All timestamps are UTC. `success` is the latest run conclusion and the primary job/operational step conclusion. The short SHA is the exact run head prefix; the full SHA is retained in the run metadata.

| # | Historical workflow path / workflow ID | Latest run / time | Event / branch | Head SHA | Job ID / result | Classification |
|---:|---|---|---|---|---|---|
| 1 | `complete-uvas-campuses-offerings.yml` / 392356405 | 39706380140 / 2026-10-22 01:33:33 | push / `ops/complete-uvas-campuses-offerings` | `9a3379356098ec1a95ebc78c9f779d91dcf22360` | 119587269133 / success — add source-verified UVAS campuses and offerings | **POTENTIALLY REDUNDANT** |
| 2 | `add-uvas-verified-campuses-and-offerings.yml` / 392355249 | 39706217486 / 2026-10-22 01:31:54 | push / `ops/add-uvas-verified-campuses-and-offerings` | `a3f4b3984a08a1902f340befd97f9e0c482c1758` | 119586753318 / success — add verified UVAS campus geography and offerings | **POTENTIALLY REDUNDANT** |
| 3 | `enrich-uvas-official-program-campus-catalog.yml` / 392354880 | 39706165044 / 2026-10-22 01:31:21 | push / `ops/enrich-uvas-official-program-campus-catalog` | `24c62f369e545f7b9c27537e8841fd1aee6031fd` | 119586584774 / success — add source-backed UVAS programs | **POTENTIALLY REDUNDANT** |
| 4 | `enrich-lums-official-undergraduate-programs.yml` / 392348473 | 39705183563 / 2026-10-22 01:21:16 | push / `ops/enrich-lums-official-undergraduate-programs` | `db0b2a3365fb7e8e1611a295f950b1facbb56a43` | 119583442010 / success — add source-verified LUMS programs and offerings | **POTENTIALLY REDUNDANT** |
| 5 | `audit-lums-uvas-program-gap.yml` / 392348037 | 39705123334 / 2026-10-22 01:20:39 | push / `ops/audit-lums-uvas-program-gap` | `8a73c39b68b65468347e35099e53ca80bbd7f0dd` | 119583250821 / success — read-only LUMS/UVAS program and offering audit | **HISTORICAL AND COMPLETED** |
| 6 | `fix-comsats-program-source.yml` / 392337430 | 39703402134 / 2026-10-22 01:03:08 | push / `ops/fix-comsats-program-source` | `6695ade6717b1a71118b65fdbce35ebe173f16e3` | 119577666649 / success — update official source URL on an existing program | **REQUIRES FURTHER INVESTIGATION** |
| 7 | `audit-program-source-coverage.yml` / 392337010 | 39703333320 / 2026-10-22 01:02:29 | push / `ops/audit-program-source-coverage` | `6efcb55b3c9137f6889de0262e5ab5e8d7989aeb` | 119577450563 / success — read-only source coverage/duplicate audit | **HISTORICAL AND COMPLETED** |
| 8 | `final-catalog-verification-v2.yml` / 392328550 | 39702542322 / 2026-10-22 00:54:46 | push / `ops/final-catalog-verification-v2` | `f77bb39e1e5de0c9aed4e749fceb0c16e9038516` | 119574928773 / success — verify public directory/catalog integrity | **HISTORICAL AND COMPLETED** |
| 9 | `read-final-catalog-state.yml` / 392331513 | 39702456630 / 2026-10-22 00:53:55 | push / `ops/read-final-catalog-state` | `bc51f7bf977e1e17d3796b1e95a6e4d7d54cbe2d` | 119574650874 / success — read-only catalog counts and intake state | **HISTORICAL AND COMPLETED** |
| 10 | `fix-lums-offering-and-2027-intake.yml` / 392330488 | 39702318782 / 2026-10-22 00:52:34 | push / `ops/fix-lums-offering-and-2027-intake` | `a98c9f61c8da92271207fea3b6ac7bd28149b960` | 119574209263 / success — guarded LUMS offering/intake creation or update | **REQUIRES FURTHER INVESTIGATION** |
| 11 | `read-lums-intake-dates.yml` / 392329264 | 39702250067 / 2026-10-22 00:51:53 | push / `ops/read-lums-intake-dates` | `5bcdc8468b793da844cd52d44a9751d68e9a5dfc` | 119573989173 / success — read-only current LUMS intake row | **HISTORICAL AND COMPLETED** |
| 12 | `add-verified-campus-offerings-and-lums-2027-intake.yml` / 392328047 | 39701971264 / 2026-10-22 00:49:09 | push / `ops/add-verified-campus-offerings-and-lums-2027-intake` | `b6830ffd3d7ded0673483235d56ad4bec852fe5e` | 119573090434 / success — add campus offerings and LUMS intake | **REQUIRES FURTHER INVESTIGATION** |
| 13 | `final-program-catalog-verification.yml` / 392327606 | 39701837317 / 2026-10-22 00:47:48 | push / `ops/final-program-catalog-verification` | `90ed5a8b09236c90384acffe954c045372623b0c` | 119572649260 / success — verify directory/catalog relationships | **HISTORICAL AND COMPLETED** |
| 14 | `fill-remaining-institution-program-catalog.yml` / 392327201 | 39701769637 / 2026-10-22 00:47:07 | push / `ops/fill-remaining-institution-program-catalog` | `4417d345736cda34a38672ce01c5475b8e493282` | 119572426182 / success — add source-backed programs and offerings | **POTENTIALLY REDUNDANT** |
| 15 | `expand-official-program-catalog-round-two.yml` / 392326634 | 39701678245 / 2026-10-22 00:46:12 | push / `ops/expand-official-program-catalog-round-two` | `ebc5ced1cd1ebab6d076e242b9d1a024ebf63430` | 119572126529 / success — add official programs and campus offerings | **POTENTIALLY REDUNDANT** |
| 16 | `expand-program-catalog-offerings-intakes.yml` / 392325916 | 39701562439 / 2026-10-22 00:45:05 | push / `ops/expand-program-catalog-offerings-intakes` | `24a0590ab9f38fd42aec877886537692b076cf69` | 119571746839 / success — source-backed program/offering enrichment | **POTENTIALLY REDUNDANT** |
| 17 | `final-public-directory-verification.yml` / 392319192 | 39700859671 / 2026-10-22 00:38:05 | push / `ops/final-public-directory-verification` | `d373c4dc119888532313ef51679d0278ca25bacf` | 119569457558 / success — verify active profiles and canonical health | **HISTORICAL AND COMPLETED** |
| 18 | `complete-remaining-institution-profiles.yml` / 392320151 | 39700735373 / 2026-10-22 00:36:50 | push / `ops/complete-remaining-institution-profiles` | `4d1cb85834d4099ecfd9922afde0c0a94afe6dec` | 119569046199 / success — fill official source descriptions and verification timestamps | **POTENTIALLY REDUNDANT** |
| 19 | `inspect-two-institution-identities.yml` / 392320640 | 39700690052 / 2026-10-22 00:36:21 | push / `ops/inspect-two-institution-identities` | `b8c06ace5eb26094174368748e18a3eb35d9e88c` | 119568893780 / success — read institution identities only | **HISTORICAL AND COMPLETED** |
| 20 | `add-officially-listed-programs.yml` / 392318751 | 39700366711 / 2026-10-22 00:33:06 | push / `ops/add-officially-listed-programs` | `8be2ad326cb67eb90157113a61b656b5c935ebaa` | 119567846192 / success — insert source-documented programs | **POTENTIALLY REDUNDANT** |
| 21 | `complete-campus-geography-and-profiles.yml` / 392317909 | 39700267248 / 2026-10-22 00:32:07 | push / `ops/complete-campus-geography-and-profiles` | `df56a332c9aeb0aaee4cd9e23b0bb1d4979049e0` | 119567523265 / success — guarded geography repair and campus enrichment | **REQUIRES FURTHER INVESTIGATION** |
| 22 | `audit-remaining-institutes-data.yml` / 392317172 | 39700077540 / 2026-10-22 00:30:18 | push / `ops/audit-remaining-institutes-data` | `d3e954b94359efd8a1c42890d983887aa0128791` | 119566911006 / success — read-only gap/public-route audit | **HISTORICAL AND COMPLETED** |
| 23 | `add-seven-verified-main-campuses.yml` / 392313978 | 39699709895 / 2026-10-22 00:26:37 | push / `ops/add-seven-verified-main-campuses` | `30ea90119547306f608aa1c4fcfb0ecc29c2df07` | 119565725838 / success — guarded transaction for seven verified campuses/geography | **REQUIRES FURTHER INVESTIGATION** |
| 24 | `read-only-campus-geography-audit.yml` / 392312947 | 39699408939 / 2026-10-22 00:23:35 | push / `ops/read-only-campus-geography-audit` | `8a3df3ab2ab83749c62975b24c42c3513d6dbdfd` | 119564698407 / success — read-only campus/geography inspection | **HISTORICAL AND COMPLETED** |
| 25 | `classify-seven-active-institutions.yml` / 392305791 | 39698133958 / 2026-10-22 00:11:03 | push / `ops/classify-seven-active-institutions` | `21d7bcea06d8f9cda8dc061a890880fc35a33f91` | 119560457865 / success — update allowlisted institution types over SSH | **POSSIBLE OUTSTANDING OPERATIONAL RISK** |
| 26 | `activate-seven-institutions-once.yml` / 392301788 | 39697594064 / 2026-10-22 00:05:57 | push / `ops/activate-seven-institutions-once` | `29840e65c28b2a8af35ac0173f9637cc01af86a7` | 119558655241 / success — activate exactly seven candidate institutions over SSH | **POSSIBLE OUTSTANDING OPERATIONAL RISK** |
| 27 | `guarded-retry-campus-row-24.yml` / 390046887 | 39355547117 / 2026-10-20 01:49:24 | push / `ops/retry-campus-row-24-normalized` | `a87f78f47924410ec8ba4934454b8b3a7003897e` | 118409362231 / success — normalize optional coordinates and promote row 24 | **POSSIBLE OUTSTANDING OPERATIONAL RISK** |
| 28 | `diagnose-campus-row-24.yml` / 390046115 | 39355483596 / 2026-10-20 01:48:40 | push / `ops/diagnose-campus-row-24-promotion` | `5ec3758d9a50fe4e89527b5b3b569f1e7b3b6027` | 118409158230 / success — read promotion failure details | **HISTORICAL AND COMPLETED** |
| 29 | `one-time-promote-campus-row-24.yml` / 390045801 | 39355440868 / 2026-10-20 01:48:12 | push / `ops/one-time-promote-campus-row-24` | `672ffc325390d17aa13b800a4d30035f5cf054ce` | 118409021751 / success — guarded promotion of campus row 24 | **POSSIBLE OUTSTANDING OPERATIONAL RISK** |

### High-risk workflow source review

- The seven-institution activation workflow contains a direct SSH PHP/Laravel transaction that updates institution status from `candidate` to `active` for exactly seven allowlisted IDs, then verifies the result.
- The paired classification workflow directly updates `institution_type` for those seven active institutions.
- The row-24 promotion workflow checks the expected batch/entity/status, parent institution, open conflicts, approved decision, geography and duplicate slug before invoking the promotion path.
- The row-24 retry workflow normalizes empty optional coordinates, writes an audit event, and proceeds through the guarded promotion path.
- The catalog/program workflows include direct SSH PHP transactions that insert or update programs, campuses, offerings, intake rows, source URLs, profile descriptions or geography.
- These are production-write-capable workflow definitions. Their job/step success does not provide the resulting row-level state. The current production state is **UNVERIFIED** because this task did not inspect production or private output.

### Current-main overlap and trigger review

- None of the 29 historical workflow paths exists among the 35 workflow files on current main.
- The current `scheduled-institute-discovery.yml` is a separate hourly schedule plus manual and successful-source-refresh `workflow_run` handoff. It invokes `institutes:bootstrap-agent` and `institutes:monitor-changes`; it is not a replacement for the historical one-off activation or campus-promotion scripts. Its concurrency group serializes that workflow only.
- Current `institution-import-preflight.yml` runs Python duplicate-preflight tests on relevant PR/push paths; it is not a production import executor.
- Current controlled migration workflows for discovery outcomes, duplicate-row schema, institution field approvals and official-source refresh are separate from the 29 historical catalog operations. Most expose manual confirmation inputs defaulting false.
- **Current-main migration trigger requiring attention:** `controlled-institution-field-approvals-migration.yml` declares a `push` trigger on main when that workflow file changes, and sets `CONFIRM_MIGRATION` to true for a push event. Source therefore permits the target migration to run automatically after a matching main push, rather than requiring the manual boolean input. This is a source-level trigger fact; this audit did not dispatch the workflow or establish a current/previous production migration outcome.
- The 29 historical branch refs still exist. Their current tip SHAs and whether each branch tip still contains its matching workflow YAML are **UNVERIFIED**. If a matching workflow file remains at a branch tip, a subsequent push to that branch may retrigger its production SSH job. No branch was changed or deleted.

### Classification totals and unresolved evidence

| Classification | Count |
|---|---:|
| Historical and completed | 11 |
| Potentially redundant | 9 |
| Requires further investigation | 5 |
| Possible outstanding operational risk | 4 |
| **Total** | **29** |

The potentially redundant group comprises repeated/overlapping one-off catalog enrichment workflows; this label does not mean their production writes were undone or harmless. The four explicit activation/promotion workflows are elevated to possible operational risk. Five targeted source/intake/geography/campus updates need row-level reconciliation before they can be called complete.

**Outstanding:** (1) determine current tips and YAML presence for the 29 retained branches; (2) obtain an explicitly authorized, sanitized production-state reconciliation for the affected institutions/campuses/programs/offerings/intakes; (3) review the automatic push-to-main trigger on the current institution-field-approvals migration workflow. No production read/write is authorized by this addendum.

Two additional older run-history paths, `application-validation.yml` and `dev-320-composer-lock-bootstrap.yml`, appeared outside the 29-record target set; they are legacy CI-only records, not included in the 29 operational workflow classifications.

### Safety confirmation

This addendum records GitHub metadata and static source facts only. No raw logs, production database, Hostinger, secrets or private diagnostic reports were accessed. No workflow was dispatched, rerun, cancelled, enabled, disabled or approved. No migration, import, approval, activation, campus promotion, source retrieval, deployment or production data operation was performed. No workflow file or branch was deleted/restored/modified. GCU rows #348–353 and batches #19–23 remain untouched; the 20 additional HEC profiles and AI extraction remain disabled.


---

## Phase 1A-08 — Retained historical branch and workflow re-trigger audit (2026-11-03)

**Verified main SHA:** 66e43452e7dd16d2902a07bc2533dd43185ed020  
**PR #971:** MERGED at 2026-11-03 02:20:39 UTC; merge commit 66e43452e7dd16d2902a07bc2533dd43185ed020.  
**Scope:** read-only GitHub refs, recursive Git trees, commit comparisons and static workflow source inspection. No branch or workflow state was changed.

### 1. Starting security gate

PR #971 is merged. The current main version of .github/workflows/controlled-institution-field-approvals-migration.yml contains only workflow_dispatch. Its confirm_migration input is required, boolean, and defaults to false. CONFIRM_MIGRATION is derived only from inputs.confirm_migration == true. The automatic push authorization path is absent.

### 2. Method and limits

- Re-read refs/heads/main before branch creation; the verified SHA was 66e43452e7dd16d2902a07bc2533dd43185ed020.
- Read all 29 retained ops/* refs directly and recorded each exact current tip SHA.
- Read the recursive Git tree at each tip. All 29 tree responses were complete (not truncated).
- Compared every tip against the verified main SHA. All 29 compare results were diverged.
- For each matching historical workflow file present at a branch tip, inspected the complete YAML and classified its trigger and source operations. The 26 retained matching workflow files each declare push on that exact ops/* branch. All 26 use appleboy/ssh-action to reach the Hostinger application.
- The three campus-row-24 historical workflow files are absent from their respective current branch-tip trees.
- The available read-only GitHub connector did not expose the per-workflow enabled/disabled state endpoint. Therefore, trigger and capability conclusions below are source-level configuration findings and actual execution on a future push is conditional on the workflow being enabled. Do not interpret file presence as proof of enabled state.
- No workflow runs, raw logs, secrets, private diagnostic reports, Hostinger or production state were accessed.

### 3. Complete 29-branch results

“Diverged +N/-M” means the branch has N commits not in main and main has M commits not in the branch. The trigger column records the YAML trigger at the current branch tip, not the historical run event. For the 26 present files, the push trigger is scoped to the exact branch shown.

| # | Retained branch | Exact current tip SHA | Historical workflow ID / file at tip | Current trigger | Source capability / classification | Comparison to main |
|---:|---|---|---|---|---|---|
| 1 | ops/complete-uvas-campuses-offerings | 9a3379356098ec1a95ebc78c9f779d91dcf22360 | 392356405 / complete-uvas-campuses-offerings.yml — present | push: exact branch | Hostinger SSH; writes geography, campuses and offerings — **3: production-write-capable** | diverged +1/-719 |
| 2 | ops/add-uvas-verified-campuses-and-offerings | a3f4b3984a08a1902f340befd97f9e0c482c1758 | 392355249 / add-uvas-verified-campuses-and-offerings.yml — present | push: exact branch | Hostinger SSH; reads geography schema/existing geography and campus counts; no writes — **2: read-only** | diverged +1/-719 |
| 3 | ops/enrich-uvas-official-program-campus-catalog | 24c62f369e545f7b9c27537e8841fd1aee6031fd | 392354880 / enrich-uvas-official-program-campus-catalog.yml — present | push: exact branch | Hostinger SSH; inserts programs and updates descriptions — **3: production-write-capable** | diverged +1/-719 |
| 4 | ops/enrich-lums-official-undergraduate-programs | db0b2a3365fb7e8e1611a295f950b1facbb56a43 | 392348473 / enrich-lums-official-undergraduate-programs.yml — present | push: exact branch | Hostinger SSH; inserts/updates programs and offerings — **3: production-write-capable** | diverged +1/-719 |
| 5 | ops/audit-lums-uvas-program-gap | 8a73c39b68b65468347e35099e53ca80bbd7f0dd | 392348037 / audit-lums-uvas-program-gap.yml — present | push: exact branch | Hostinger SSH; database/schema inspection and reports only — **2: read-only** | diverged +1/-719 |
| 6 | ops/fix-comsats-program-source | 6695ade6717b1a71118b65fdbce35ebe173f16e3 | 392337430 / fix-comsats-program-source.yml — present | push: exact branch | Hostinger SSH; updates an existing program source description — **3: production-write-capable** | diverged +1/-719 |
| 7 | ops/audit-program-source-coverage | 6efcb55b3c9137f6889de0262e5ab5e8d7989aeb | 392337010 / audit-program-source-coverage.yml — present | push: exact branch | Hostinger SSH; reads program/source coverage, duplicates, offerings and intakes — **2: read-only** | diverged +1/-719 |
| 8 | ops/final-catalog-verification-v2 | f77bb39e1e5de0c9aed4e749fceb0c16e9038516 | 392328550 / final-catalog-verification-v2.yml — present | push: exact branch | Hostinger SSH; catalogue counts and integrity verification only — **2: read-only** | diverged +4/-719 |
| 9 | ops/read-final-catalog-state | bc51f7bf977e1e17d3796b1e95a6e4d7d54cbe2d | 392331513 / read-final-catalog-state.yml — present | push: exact branch | Hostinger SSH; reads counts, programs, offerings and intakes — **2: read-only** | diverged +1/-719 |
| 10 | ops/fix-lums-offering-and-2027-intake | a98c9f61c8da92271207fea3b6ac7bd28149b960 | 392330488 / fix-lums-offering-and-2027-intake.yml — present | push: exact branch | Hostinger SSH; inserts/updates offering and Fall 2027 intake — **3: production-write-capable** | diverged +1/-719 |
| 11 | ops/read-lums-intake-dates | 5bcdc8468b793da844cd52d44a9751d68e9a5dfc | 392329264 / read-lums-intake-dates.yml — present | push: exact branch | Hostinger SSH; reads program, offering and intake records — **2: read-only** | diverged +3/-719 |
| 12 | ops/add-verified-campus-offerings-and-lums-2027-intake | b6830ffd3d7ded0673483235d56ad4bec852fe5e | 392328047 / add-verified-campus-offerings-and-lums-2027-intake.yml — present | push: exact branch | Hostinger SSH; inserts/updates offerings and LUMS intake — **3: production-write-capable** | diverged +3/-719 |
| 13 | ops/final-program-catalog-verification | 90ed5a8b09236c90384acffe954c045372623b0c | 392327606 / final-program-catalog-verification.yml — present | push: exact branch | Hostinger SSH; reads catalogue counts and integrity — **2: read-only** | diverged +1/-719 |
| 14 | ops/fill-remaining-institution-program-catalog | 4417d345736cda34a38672ce01c5475b8e493282 | 392327201 / fill-remaining-institution-program-catalog.yml — present | push: exact branch | Hostinger SSH; inserts source-backed programs and offerings — **3: production-write-capable** | diverged +1/-719 |
| 15 | ops/expand-official-program-catalog-round-two | ebc5ced1cd1ebab6d076e242b9d1a024ebf63430 | 392326634 / expand-official-program-catalog-round-two.yml — present | push: exact branch | Hostinger SSH; inserts official programs and offerings — **3: production-write-capable** | diverged +1/-719 |
| 16 | ops/expand-program-catalog-offerings-intakes | 24a0590ab9f38fd42aec877886537692b076cf69 | 392325916 / expand-program-catalog-offerings-intakes.yml — present | push: exact branch | Hostinger SSH; inserts programs, offerings and intake records — **3: production-write-capable** | diverged +1/-719 |
| 17 | ops/final-public-directory-verification | d373c4dc119888532313ef51679d0278ca25bacf | 392319192 / final-public-directory-verification.yml — present | push: exact branch | Hostinger SSH; public/catalogue verification and read-only report — **2: read-only** | diverged +4/-719 |
| 18 | ops/complete-remaining-institution-profiles | 4d1cb85834d4099ecfd9922afde0c0a94afe6dec | 392320151 / complete-remaining-institution-profiles.yml — present | push: exact branch | Hostinger SSH; updates institution type, description, verification timestamps and campus type — **3: production-write-capable** | diverged +3/-719 |
| 19 | ops/inspect-two-institution-identities | b8c06ace5eb26094174368748e18a3eb35d9e88c | 392320640 / inspect-two-institution-identities.yml — present | push: exact branch | Hostinger SSH; reads two institution identity records — **2: read-only** | diverged +1/-719 |
| 20 | ops/add-officially-listed-programs | 8be2ad326cb67eb90157113a61b656b5c935ebaa | 392318751 / add-officially-listed-programs.yml — present | push: exact branch | Hostinger SSH; inserts source-documented program records — **3: production-write-capable** | diverged +1/-719 |
| 21 | ops/complete-campus-geography-and-profiles | df56a332c9aeb0aaee4cd9e23b0bb1d4979049e0 | 392317909 / complete-campus-geography-and-profiles.yml — present | push: exact branch | Hostinger SSH; repairs geography links, creates campuses and updates institution profiles — **3: production-write-capable** | diverged +2/-719 |
| 22 | ops/audit-remaining-institutes-data | d3e954b94359efd8a1c42890d983887aa0128791 | 392317172 / audit-remaining-institutes-data.yml — present | push: exact branch | Hostinger SSH; reads institution, campus and program data — **2: read-only** | diverged +1/-719 |
| 23 | ops/add-seven-verified-main-campuses | 30ea90119547306f608aa1c4fcfb0ecc29c2df07 | 392313978 / add-seven-verified-main-campuses.yml — present | push: exact branch | Hostinger SSH; inserts geography and campus records — **3: production-write-capable** | diverged +4/-719 |
| 24 | ops/read-only-campus-geography-audit | 8a3df3ab2ab83749c62975b24c42c3513d6dbdfd | 392312947 / read-only-campus-geography-audit.yml — present | push: exact branch | Hostinger SSH; reads campus/geography state and reports — **2: read-only** | diverged +1/-719 |
| 25 | ops/classify-seven-active-institutions | 21d7bcea06d8f9cda8dc061a890880fc35a33f91 | 392305791 / classify-seven-active-institutions.yml — present | push: exact branch | Hostinger SSH; updates institution_type for seven allowlisted active institutions — **3: production-write-capable** | diverged +1/-719 |
| 26 | ops/activate-seven-institutions-once | 29840e65c28b2a8af35ac0173f9637cc01af86a7 | 392301788 / activate-seven-institutions-once.yml — present | push: exact branch | Hostinger SSH; changes seven allowlisted institutions from candidate to active — **3: production-write-capable** | diverged +1/-719 |
| 27 | ops/retry-campus-row-24-normalized | f91a9c0abca4f321dac8403db1900994854e1cca | 390046887 / guarded-retry-campus-row-24.yml — **absent at tip** | no matching workflow trigger | No matching YAML; no direct re-trigger through this historical workflow — **1: no matching workflow** | diverged +2/-754 |
| 28 | ops/diagnose-campus-row-24-promotion | 9b238eef848f794ae7dc860484098e72f82e19cd | 390046115 / diagnose-campus-row-24.yml — **absent at tip** | no matching workflow trigger | No matching YAML; no direct re-trigger through this historical workflow — **1: no matching workflow** | diverged +2/-754 |
| 29 | ops/one-time-promote-campus-row-24 | f9c012501994d02871055b3e2d5d20e3840cf8b8 | 390045801 / one-time-promote-campus-row-24.yml — **absent at tip** | no matching workflow trigger | No matching YAML; no direct re-trigger through this historical workflow — **1: no matching workflow** | diverged +2/-754 |

### 4. Classification totals

| Classification | Branch count |
|---|---:|
| 1 — No matching workflow; no direct workflow re-trigger risk | 3 |
| 2 — Workflow exists but is read-only | 11 |
| 3 — Workflow exists and is production-write-capable | **15** |
| 4 — Uncertain source classification | 0 |
| **Total** | **29** |

The 15 production-write-capable branches are: ops/complete-uvas-campuses-offerings; ops/enrich-uvas-official-program-campus-catalog; ops/enrich-lums-official-undergraduate-programs; ops/fix-comsats-program-source; ops/fix-lums-offering-and-2027-intake; ops/add-verified-campus-offerings-and-lums-2027-intake; ops/fill-remaining-institution-program-catalog; ops/expand-official-program-catalog-round-two; ops/expand-program-catalog-offerings-intakes; ops/complete-remaining-institution-profiles; ops/add-officially-listed-programs; ops/complete-campus-geography-and-profiles; ops/add-seven-verified-main-campuses; ops/classify-seven-active-institutions; ops/activate-seven-institutions-once.

### 5. High-risk workflow paths and operation classes

All 15 paths below remain in their respective retained branch tips and have exact-branch push triggers:

- ops/complete-uvas-campuses-offerings — .github/workflows/complete-uvas-campuses-offerings.yml — creates geography/campus records and program offerings.
- ops/enrich-uvas-official-program-campus-catalog — .github/workflows/enrich-uvas-official-program-campus-catalog.yml — creates program records and updates program descriptions.
- ops/enrich-lums-official-undergraduate-programs — .github/workflows/enrich-lums-official-undergraduate-programs.yml — creates/updates programs and offerings.
- ops/fix-comsats-program-source — .github/workflows/fix-comsats-program-source.yml — updates an existing program source description.
- ops/fix-lums-offering-and-2027-intake — .github/workflows/fix-lums-offering-and-2027-intake.yml — creates/updates an offering and Fall 2027 intake.
- ops/add-verified-campus-offerings-and-lums-2027-intake — .github/workflows/add-verified-campus-offerings-and-lums-2027-intake.yml — creates/updates offerings and a LUMS intake.
- ops/fill-remaining-institution-program-catalog — .github/workflows/fill-remaining-institution-program-catalog.yml — creates source-backed programs and offerings.
- ops/expand-official-program-catalog-round-two — .github/workflows/expand-official-program-catalog-round-two.yml — creates official programs and offerings.
- ops/expand-program-catalog-offerings-intakes — .github/workflows/expand-program-catalog-offerings-intakes.yml — creates programs, offerings and intake records.
- ops/complete-remaining-institution-profiles — .github/workflows/complete-remaining-institution-profiles.yml — updates institution type, description and verification fields, plus a campus field.
- ops/add-officially-listed-programs — .github/workflows/add-officially-listed-programs.yml — creates program records.
- ops/complete-campus-geography-and-profiles — .github/workflows/complete-campus-geography-and-profiles.yml — updates campus geography, creates campuses and updates institution profile fields.
- ops/add-seven-verified-main-campuses — .github/workflows/add-seven-verified-main-campuses.yml — creates geography and campus records.
- ops/classify-seven-active-institutions — .github/workflows/classify-seven-active-institutions.yml — updates institution_type for seven allowlisted active institutions.
- ops/activate-seven-institutions-once — .github/workflows/activate-seven-institutions-once.yml — changes seven allowlisted candidate institutions to active.

The most direct activation risk is branch ops/activate-seven-institutions-once. Branch ops/classify-seven-active-institutions changes types but does not activate institutions. The other write-capable branches perform canonical catalogue, campus/geography or profile writes.

### 6. Other workflow files present on retained branches

The common workflow inventory was also inspected. No additional common workflow was found whose push trigger targets an ops/* branch:

- .github/workflows/hostinger-readonly-diagnostics.yml — push is restricted to main; also workflow_dispatch. It uses Hostinger SSH and performs read-only diagnostics. An ops/* push does not match its push branch filter.
- .github/workflows/institutes-public-monitor.yml — push is restricted to main; also manual and scheduled. It performs public HTTP smoke checks, not production database writes.
- .github/workflows/institution-import-preflight.yml — push is restricted to main and selected paths; pull_request runs Python duplicate-preflight tests. It is not a production import executor.
- .github/workflows/laravel-ci.yml and .github/workflows/test.yml — push and pull_request are restricted to main. Artisan migration commands in CI operate on the runner's local test database, not Hostinger production.
- .github/workflows/build-frontend-assets.yml — push is restricted to main; also manual.
- .github/workflows/controlled-pakistan-geography-seed.yml — workflow_dispatch only. It can run a production geography seed after an explicit confirm_seed=yes input; default is no. It is not push-triggered.
- .github/workflows/secure-institutes-admin-recovery.yml — workflow_dispatch only. It can reset the existing administrator password after its explicit confirmation and identity guards; it is not push-triggered.
- .github/workflows/diagnose-hostinger-artisan-path.yml and .github/workflows/institutes-diagnostics-verify.yml — manual-only.
- .github/workflows/institutes-private-diagnostics-report.yml — manual/scheduled; no push trigger.

The three row-24 workflows from historical records (guarded-retry-campus-row-24.yml, diagnose-campus-row-24.yml and one-time-promote-campus-row-24.yml) are absent from their current branch tips. No retained branch-tip workflow was found that currently executes campus-row-24 promotion. No branch-specific workflow among the 26 present historical files contains an Artisan migration command. No retained branch-tip workflow was found that performs a formal institution import/approval/promotion pipeline operation; the production-write workflows listed above write canonical tables directly.

### 7. Containment recommendations (no action taken)

- **15 production-write-capable branches:** treat as frozen/no-push until a separately authorized containment decision is made. Do not add commits, rebase, merge or otherwise push to these branches while their exact-branch push workflows remain in the branch tip. The least disruptive future containment is to prevent accidental pushes and, under separate authorization, disable or remove only the identified branch-scoped workflow triggers after preserving the evidence. No such change is authorized or performed here.
- **11 read-only branches:** avoid unnecessary pushes as well. Their workflows use Hostinger SSH and can query production records or emit diagnostics even though source inspection found no write operation. Keep them unchanged pending an owner decision.
- **3 branches with absent matching YAML:** no containment is needed for the historical workflow path itself. Do not restore those YAML files. Their other common workflows do not have ops/* push triggers.
- **Manual-only shared workflows:** do not dispatch the geography seed or administrator recovery workflows as part of this audit. They are separate manual authorization paths, not push re-trigger paths.

### 8. Remaining uncertainty and production-state boundary

- Per-workflow enabled/disabled state was not verifiable through the available read-only GitHub connector. The 26 present workflow files have push triggers configured for their exact retained branch, so they are potential re-trigger paths if enabled.
- A successful historical run does not establish present production row state, whether a prior write was reversed, or whether the operation is still needed. No production database or Hostinger evidence was acquired.
- No migration or formal import/promotion operation was found in the 26 branch-specific workflow files. The local test CI migration is isolated from production. Manual-only geography seed and administrator recovery remain separate write-capable workflows.
- The three absent campus-row-24 workflow files remove their direct branch-tip re-trigger path, but the historical production effects of their successful runs remain unverified.
- All 29 branches are deeply behind and diverged from main. Do not merge or rebase them as a cleanup shortcut.

### 9. Safety confirmation and stop decision

This audit used GitHub metadata, branch refs, commit comparisons, recursive trees and static YAML source only. No branch was pushed, deleted, renamed or modified. No workflow was dispatched, rerun, cancelled, enabled, disabled or approved. No raw logs, secrets, private diagnostic reports, Hostinger, production database, Artisan command, migration, import, approval, activation, campus promotion, deployment or data write was accessed or performed.

GCU rows #348–353 and batches #19–23 were not accessed or changed. The additional 20 HEC profiles remain disabled; AI extraction remains disabled; no dummy production data was created.

**Decision: STOP after this repository-only audit.** The next safe action is a separately authorized containment decision for the 15 production-write-capable retained branches, beginning with ops/activate-seven-institutions-once. Do not begin production remediation or row-level reconciliation under this audit authorization.


---

## Phase 1A-09 — Safe containment plan for retained production-write workflows (2026-11-03)

**Verified current main SHA:** a67f9f8e2e2d1080cf061df6d228160eb408e137  
**PR #971:** merged; security correction retained.  
**PR #972:** merged; Phase 1A-08 branch audit retained.  
**Current migration workflow:** .github/workflows/controlled-institution-field-approvals-migration.yml remains workflow_dispatch-only; required boolean confirm_migration defaults false; CONFIRM_MIGRATION derives exclusively from inputs.confirm_migration == true.

### 1. Baseline and branch-tip revalidation

The Phase 1A-08 audit document was reread on current main and still contains all 29 branch rows. The 15 production-write-capable branch refs were re-read. All 15 branch-tip SHAs are unchanged from Phase 1A-08. Each corresponding workflow YAML was fetched from its current branch and still has a push trigger scoped to that exact branch. All 15 current workflow files have no workflow_dispatch, schedule or workflow_call trigger.

### 2. Risk-ranked inventory of the 15 branches

Risk ranking is based on the type and potential reach of the workflow's production writes, not on a claim that the operation is currently enabled or that a historical write remains in production.

| Rank | Branch | Current tip SHA | Workflow ID | Exact workflow path | Production-write capability | Latest observed run |
|---:|---|---|---:|---|---|---|
| 1 — Critical | ops/activate-seven-institutions-once | 29840e65c28b2a8af35ac0173f9637cc01af86a7 | 392301788 | .github/workflows/activate-seven-institutions-once.yml | Directly changes seven allowlisted candidate institutions to active | 39697594064 — completed, success |
| 2 — High | ops/classify-seven-active-institutions | 21d7bcea06d8f9cda8dc061a890880fc35a33f91 | 392305791 | .github/workflows/classify-seven-active-institutions.yml | Updates institution_type for seven allowlisted institutions; not itself the active-status transition | 39698133958 — completed, success |
| 3 — High | ops/complete-campus-geography-and-profiles | df56a332c9aeb0aaee4cd9e23b0bb1d4979049e0 | 392317909 | .github/workflows/complete-campus-geography-and-profiles.yml | Creates campus records and updates campus geography and institution profile fields | 39700220568 — completed, success |
| 4 — High | ops/add-seven-verified-main-campuses | 30ea90119547306f608aa1c4fcfb0ecc29c2df07 | 392313978 | .github/workflows/add-seven-verified-main-campuses.yml | Creates geography and campus records | 39699709895 — completed, success |
| 5 — High | ops/complete-uvas-campuses-offerings | 9a3379356098ec1a95ebc78c9f779d91dcf22360 | 392356405 | .github/workflows/complete-uvas-campuses-offerings.yml | Creates geography/campus records and program offerings | 39706380140 — completed, success |
| 6 — High | ops/complete-remaining-institution-profiles | 4d1cb85834d4099ecfd9922afde0c0a94afe6dec | 392320151 | .github/workflows/complete-remaining-institution-profiles.yml | Updates institution type, description, verification fields and campus type | 39700735373 — completed, success |
| 7 — Elevated | ops/enrich-uvas-official-program-campus-catalog | 24c62f369e545f7b9c27537e8841fd1aee6031fd | 392354880 | .github/workflows/enrich-uvas-official-program-campus-catalog.yml | Inserts programs and updates descriptions | 39706165044 — completed, success |
| 8 — Elevated | ops/enrich-lums-official-undergraduate-programs | db0b2a3365fb7e8e1611a295f950b1facbb56a43 | 392348473 | .github/workflows/enrich-lums-official-undergraduate-programs.yml | Inserts/updates programs and offerings | 39705183563 — completed, success |
| 9 — Elevated | ops/fix-lums-offering-and-2027-intake | a98c9f61c8da92271207fea3b6ac7bd28149b960 | 392330488 | .github/workflows/fix-lums-offering-and-2027-intake.yml | Inserts/updates an offering and Fall 2027 intake | 39702318782 — completed, success |
| 10 — Elevated | ops/add-verified-campus-offerings-and-lums-2027-intake | b6830ffd3d7ded0673483235d56ad4bec852fe5e | 392328047 | .github/workflows/add-verified-campus-offerings-and-lums-2027-intake.yml | Inserts/updates offerings and LUMS intake | 39701971264 — completed, success |
| 11 — Elevated | ops/fill-remaining-institution-program-catalog | 4417d345736cda34a38672ce01c5475b8e493282 | 392327201 | .github/workflows/fill-remaining-institution-program-catalog.yml | Inserts source-backed programs and offerings | 39701769637 — completed, success |
| 12 — Elevated | ops/expand-official-program-catalog-round-two | ebc5ced1cd1ebab6d076e242b9d1a024ebf63430 | 392326634 | .github/workflows/expand-official-program-catalog-round-two.yml | Inserts official programs and offerings | 39701678245 — completed, success |
| 13 — Elevated | ops/expand-program-catalog-offerings-intakes | 24a0590ab9f38fd42aec877886537692b076cf69 | 392325916 | .github/workflows/expand-program-catalog-offerings-intakes.yml | Inserts programs, offerings and intake records | 39701562439 — completed, success |
| 14 — Elevated | ops/add-officially-listed-programs | 8be2ad326cb67eb90157113a61b656b5c935ebaa | 392318751 | .github/workflows/add-officially-listed-programs.yml | Inserts source-documented program records | 39700366711 — completed, success |
| 15 — Elevated | ops/fix-comsats-program-source | 6695ade6717b1a71118b65fdbce35ebe173f16e3 | 392337430 | .github/workflows/fix-comsats-program-source.yml | Updates an existing program source description | 39703402134 — completed, success |

### 3. Current workflow triggers, dispatchability and run state

- All 15 current YAML files have only a push trigger scoped to the exact branch name in the table.
- None of the 15 YAML files declares workflow_dispatch. Consequently, these files are not manually dispatchable in their current form.
- None declares schedule or workflow_call.
- A branch-filtered Actions runs query was made for each of the 15 branches. No run was queued, in_progress, waiting, requested or pending at inspection time.
- The latest observed run for each branch was a completed successful push run on 2026-10-22. The table records the latest run ID. Some branches have more than one historical run; this does not change the current no-active-run result.
- This run-state check is a point-in-time observation, not a guarantee against a new run after a later push.

### 4. Verified GitHub containment mechanism

**Preferred mechanism: disable the exact workflow by its workflow ID, one at a time.**

GitHub's official REST documentation states that the disable-workflow endpoint sets the selected workflow's state to disabled_manually. The workflow can be selected by workflow ID or filename. The endpoint requires Actions repository write permission. This is a workflow-level operation, not a branch deletion or repository-wide Actions shutdown.

Documented effects:
- A disabled workflow is stopped from being triggered; its workflow file remains in Git.
- Disabling does not cancel an existing run. Any already-running job must be handled separately. No active runs were found in this audit.
- While disabled, existing runs or jobs cannot be rerun; the workflow must first be re-enabled.
- The 15 YAML files have no workflow_dispatch event. Even if a workflow were enabled, its current YAML does not offer manual dispatch.
- The disable operation targets one workflow identity/ID. It should not disable unrelated workflow IDs. It applies to that workflow resource across refs where the same workflow identity is present, not only to one branch push event.

Official references:
- GitHub REST API — workflow listing, workflow metadata, disable, dispatch and enable endpoints: https://docs.github.com/en/rest/actions/workflows
- GitHub Actions — disabling and enabling workflows: https://docs.github.com/en/actions/how-tos/manage-workflow-runs/disable-and-enable-workflows

**Important verification gap:** the connected read-only GitHub interface returned the branch refs, YAML files and workflow run history, but rejected the GET workflow metadata endpoint used to read each workflow's current state. Therefore the current state (active vs disabled_manually) for all 15 IDs is unknown. The historical run IDs establish the workflow IDs, but are not a substitute for current workflow metadata. Before any future disable request, an authorized operator must inspect each exact workflow ID's current path and state in GitHub's Actions UI or the documented GET /repos/{owner}/{repo}/actions/workflows/{workflow_id} endpoint. Do not assume any workflow is currently enabled.

### 5. Comparison of alternatives

| Mechanism | Effect and scope | Preservation / side effects | Assessment |
|---|---|---|---|
| Disable exact workflow ID | Stops that workflow from being triggered while disabled | Keeps branch, commits and YAML evidence. Does not cancel existing runs. Does not affect unrelated workflow IDs. | **Preferred; least disruptive.** |
| Add ruleset for exact 15 branch names | Can restrict branch updates, preventing ordinary pushes if configured correctly | Repository settings change; may block legitimate maintenance and require carefully controlled bypass policy. Current active ruleset only targets refs/heads/main. | Secondary guard only if workflow disable is unavailable or owner requires defense-in-depth. |
| Classic branch protection | May restrict direct updates to selected branches | Existing per-branch protection state could not be verified with the connected read-only interface; administrators/bypass actors may affect effectiveness. | Not established as currently available; inspect separately before relying on it. |
| Edit/remove push trigger in workflow YAML | Changes the workflow definition on that branch | Requires a commit/push to the risky branch. That push itself is the event being contained; the operation also changes historical branch evidence. | Do not use as first containment step. |
| Rename branch | Changes the ref name and may affect branch filters, PR references, automation and integrations | Disruptive to references and could create confusing redirects or stale references. | Not recommended. |
| Delete branch | Removes the named branch ref and stops ordinary pushes to that ref | Loses the stable named reference to the exact tip; may affect PRs, collaborators and evidence access. Recreating the name could restore trigger risk. | Not recommended; conflicts with evidence-preservation objective. |
| Disable all GitHub Actions or change repository-wide permissions | Broadly suppresses Actions | Affects unrelated CI, monitors, and safety workflows across the repository. | Reject; disproportionate. |

The active repository ruleset inventory returned one ruleset named main, targeting only refs/heads/main. No active repository ruleset targeting ops/* was found. This does not establish the absence of classic branch protection; that separate state remains unverified.

### 6. Per-branch recommendation

For each of the 15 branches in the risk-ranked table, the recommended containment is to disable only its corresponding workflow ID, after verifying the workflow metadata path and current state. Preserve the branch ref, tip SHA, workflow YAML and run history. If already disabled_manually, leave it disabled and record the state; do not toggle it.

The first candidate is workflow ID 392301788, .github/workflows/activate-seven-institutions-once.yml on ops/activate-seven-institutions-once. This is the highest priority because the workflow directly transitions seven allowlisted institutions from candidate to active. Do not push to this branch, edit its YAML, rename it or delete it as part of containment. Disabling this workflow would prevent future triggers from this workflow identity but would not reverse any previous activation or establish current database status.

### 7. Staged implementation plan and approval gates

**Stage 0 — Owner authorization (required before changes)**
- Obtain explicit approval to disable the 15 listed workflow IDs.
- Confirm the approved list, priority order and acceptable interruption of any future legitimate use.
- Confirm no production database or Hostinger remediation is included.

**Stage 1 — Metadata verification (read-only)**
- For each ID, GET workflow metadata and verify ID, path, state and repository identity.
- Verify the path matches the workflow file listed in this plan.
- If ID/path mismatch, missing workflow, or unexpected state occurs, stop for that item and do not guess.
- Recheck active runs immediately before disabling.

**Stage 2 — Controlled workflow-only containment (requires separate explicit approval)**
- Disable workflow ID 392301788 first.
- Read back its metadata and confirm state disabled_manually.
- Confirm branch tip SHA and YAML blob remain unchanged.
- Confirm unrelated workflows remain active.
- After owner review of the first item, repeat one-by-one for the remaining 14 IDs.
- Do not cancel active runs automatically. If a run appears before containment, stop and seek separate run-specific approval.

**Stage 3 — Verification and evidence**
- Re-read all 15 workflow metadata records and verify disabled_manually.
- Re-read all 15 branch tips and workflow YAML blob SHAs to prove preservation.
- Query branch-filtered runs and confirm no new run was started by containment.
- Record the exact timestamp, operator, workflow ID, before/after state and verification evidence in the audit document.
- Do not re-enable workflows without separate explicit approval and a reviewed execution plan.

### 8. Facts, assumptions and unresolved questions

**Verified facts**
- Current main SHA is a67f9f8e2e2d1080cf061df6d228160eb408e137.
- PR #971 and PR #972 are merged.
- The current migration workflow remains manual-only and requires explicit confirmation defaulting false.
- All 15 retained branch tips match Phase 1A-08 SHAs.
- All 15 workflow files remain present at their current branch tips, with exact-branch push triggers and no workflow_dispatch.
- No active run was found on any of the 15 branches at the time of inspection.
- The only active repository ruleset inspected targets main.
- GitHub officially documents workflow-level disable behavior and its effect on triggers and existing runs.

**Assumptions used only for planning**
- Disabling each exact workflow ID is the least disruptive way to suppress that workflow's triggers while retaining its branch and source evidence.
- The listed workflow IDs continue to identify the corresponding unique workflow paths. This must be checked against live metadata before action.
- The operation is expected not to affect unrelated workflow IDs; read-back verification is required after each future disable.

**Unresolved**
- Current active/disabled_manually state for each of the 15 workflow IDs; the connected read-only interface could not fetch workflow metadata.
- Classic per-branch protection state for the 15 branches.
- Whether the seven historical institution activations or any other prior writes remain in production. No production state was accessed.
- Whether any branch tip has external references outside GitHub PRs and workflow records.

### 9. Safety and stop decision

This phase performed GitHub read-only inspection and documentation planning only. No workflow was disabled, enabled, dispatched, rerun, cancelled or approved. No branch was modified, renamed or deleted. No repository settings, rulesets, secrets, variables or permissions were changed. No Hostinger, production database, raw logs, private reports, Artisan, migration, import, approval, activation, campus promotion, deployment or production data write was accessed or performed.

GCU rows #348–353 and batches #19–23 remain untouched. The additional 20 HEC profiles remain disabled; AI extraction remains disabled; no dummy production data was created.

**Decision: STOP after preparing this plan.** The next action is an owner approval for a separate containment phase. Do not implement the plan under Phase 1A-09 authorization.

---

## Phase 1A-14 — Owner-authorized containment recorded (2026-11-03)

**Repository:** `mattari20/estudent-listing`  
**Main baseline before this documentation change:** `d75d40e7289cfbef2848538e2753c1b2b9c1df19`  
**Verification date supplied by repository owner:** 2026-11-03  
**Change scope:** documentation only.

### 1. Owner-authorized workflow containment evidence

The repository owner supplied the following evidence summary for actions completed on 2026-11-03 using an authenticated GitHub CLI session:

1. All 15 listed workflows were queried before containment and each returned state `active`.
2. The owner explicitly authorized disabling these 15 workflow IDs individually.
3. Each workflow was disabled through GitHub's workflow API.
4. Each workflow was queried again and returned state `disabled_manually`.
5. A repository Actions-run query was repeated after containment. The five requested execution status categories each returned zero:
   - `queued`: 0
   - `in_progress`: 0
   - `waiting`: 0
   - `requested`: 0
   - `pending`: 0

**Evidence provenance:** these before/after API results and the execution query counts are recorded here from the owner-supplied verification evidence for this phase. The connected GitHub repository interface used for this documentation PR does not independently expose workflow metadata state. The workflow names below were checked against the `name:` declarations in the corresponding retained branch YAML files; IDs, final states, timestamps and pre-disable states are attributed to the owner's API read-back. No state is inferred from YAML or historical run records.

**Verification timestamp precision:** the owner supplied the verification date (2026-11-03), but not the exact UTC/PKT time or individual `updated_at` values. Those timestamps are not fabricated or inferred.

### 2. Complete workflow containment register

All 15 records below have owner-reported before-state `active`, owner-reported final state `disabled_manually`, and matching ID/path identity according to the owner's workflow metadata read-back. Display names were cross-checked against the corresponding YAML `name:` declaration on each retained branch.

| Workflow ID | Exact workflow name | Retained branch | Workflow file | Owner-reported final state | Identity |
|---:|---|---|---|---|---|
| 392301788 | One-time Activate Seven Candidate Institutions | `ops/activate-seven-institutions-once` | `.github/workflows/activate-seven-institutions-once.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392305791 | Classify Seven Active Institutions | `ops/classify-seven-active-institutions` | `.github/workflows/classify-seven-active-institutions.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392317909 | Complete Campus Geography and Profile Data | `ops/complete-campus-geography-and-profiles` | `.github/workflows/complete-campus-geography-and-profiles.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392313978 | Add Seven Verified Main Campuses | `ops/add-seven-verified-main-campuses` | `.github/workflows/add-seven-verified-main-campuses.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392356405 | Complete UVAS Campuses and Offerings | `ops/complete-uvas-campuses-offerings` | `.github/workflows/complete-uvas-campuses-offerings.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392320151 | Complete Remaining Institution Profiles | `ops/complete-remaining-institution-profiles` | `.github/workflows/complete-remaining-institution-profiles.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392354880 | Enrich UVAS Official Program Campus Catalog | `ops/enrich-uvas-official-program-campus-catalog` | `.github/workflows/enrich-uvas-official-program-campus-catalog.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392348473 | Enrich LUMS Official Undergraduate Programs | `ops/enrich-lums-official-undergraduate-programs` | `.github/workflows/enrich-lums-official-undergraduate-programs.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392330488 | Fix LUMS Offering and 2027 Intake | `ops/fix-lums-offering-and-2027-intake` | `.github/workflows/fix-lums-offering-and-2027-intake.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392328047 | Add Verified Campus Offerings and LUMS 2027 Intake | `ops/add-verified-campus-offerings-and-lums-2027-intake` | `.github/workflows/add-verified-campus-offerings-and-lums-2027-intake.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392327201 | Fill Remaining Institution Program Catalog | `ops/fill-remaining-institution-program-catalog` | `.github/workflows/fill-remaining-institution-program-catalog.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392326634 | Expand Official Program Catalog Round Two | `ops/expand-official-program-catalog-round-two` | `.github/workflows/expand-official-program-catalog-round-two.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392325916 | Expand Program Catalog Offerings and Historical Intakes | `ops/expand-program-catalog-offerings-intakes` | `.github/workflows/expand-program-catalog-offerings-intakes.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392318751 | Add Officially Listed Programs | `ops/add-officially-listed-programs` | `.github/workflows/add-officially-listed-programs.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |
| 392337430 | Fix COMSATS Program Source URL | `ops/fix-comsats-program-source` | `.github/workflows/fix-comsats-program-source.yml` | `disabled_manually` | ID/path match (owner-supplied metadata read-back; YAML name checked) |

**Totals:** 15 initially `active`; 15 finally `disabled_manually`; 15 identity matches; 0 reported ID/path mismatches.

### 3. Post-containment execution snapshot

| GitHub Actions execution status | Count returned |
|---|---:|
| `queued` | 0 |
| `in_progress` | 0 |
| `waiting` | 0 |
| `requested` | 0 |
| `pending` | 0 |
| **Total across these five categories** | **0** |

This is a point-in-time repository Actions-run query reported by the owner. It does not prove that no completed or historical run exists, and it does not guarantee that no execution can occur through another workflow, runner, integration or future configuration change.

### 4. Containment boundary — not a complete production safety certification

Disabling these 15 workflow IDs contains the specific workflow identities listed above. It does **not** establish that every production-write path is disabled or that production is globally safe for writes.

In particular, this record does not establish the current state or safety of:
- other GitHub Actions workflows, including workflows on `main` or any other retained branch;
- Hostinger cron jobs, persistent queue workers, Laravel scheduler execution or manually run server commands;
- other SSH-capable workflows, external integrations, credentials, deploy keys, personal access tokens or operator sessions;
- application/admin/API write paths, migration paths, import/approval/promotion commands or future workflow changes;
- any already completed production writes or their resulting production data state.

Workflow-level disablement does not reverse prior actions, undo database changes, terminate unrelated workflows or stop independent schedulers and workers. A separate, scoped audit and authorization is required before making broader production-safety claims or resuming production operations.

### 5. Preservation and restrictions

The owner-reported containment was performed at the GitHub workflow level. This documentation PR does not edit, delete, rename or recreate any of the 29 historical `ops/*` branches, their workflow YAML files, Git history or PRs. The 15 workflow YAML files remain on their respective historical branches; their recorded content was read only to confirm the declared workflow names.

No workflow was enabled, disabled, dispatched, rerun, cancelled or approved as part of this documentation phase. No branch-specific workflow file, application code, database, repository setting, ruleset, secret, variable or permission was changed. No Hostinger/production SSH, database, Artisan, migration, import, approval, activation, campus promotion, deployment, cron, queue or production data operation was performed.

GCU rows #348–353 and batches #19–23 remain protected and untouched. The 20 additional HEC profiles remain disabled; AI extraction remains disabled; no dummy production data was created.

**Decision:** record the owner-authorized containment as verified owner-supplied evidence, retain the distinction between workflow containment and complete production safety, and stop at this documentation-only PR. No further containment or production operation is authorized by this record.
