# Database Operations Automation Proposal

| Field | Value |
| --- | --- |
| Document ID | RFC-DB-OPS-001 |
| Status | **Phase 1 health check and Phase 2 read-only geography preflight approved for review branch only; production operations remain separately gated** |
| Project | eStudent.pk — Institutes & Education Directory |
| Scope | Laravel application database operations and production data safety |
| Target deployment | `https://estudent.pk/institutes/` |
| Last updated | 2026-10-12 |

## 1. Purpose

Define a safe, highly automated operating model for database health, data integrity, backups, deployment checks, recovery readiness, and operational reporting. The objective is to reduce repetitive manual work without allowing automation to silently damage canonical education data.

The project owner has authorized Phase 1 (read-only database health check) and Phase 2 (read-only geography seed impact preflight) on a review branch. This approval does not authorize production database writes, migrations, data repair, schema changes, credential changes, merging, or deployment. Scheduling, backup automation, notifications, and production remediation remain proposals until separately reviewed.

## 2. Non-negotiable safety boundaries

1. **Read-only by default.** Health checks, diagnostics, gap detection, and reports must not mutate canonical data.
2. **Explicit allowlist for automatic repairs.** A repair may run automatically only after its exact rule, preconditions, idempotency, rollback, audit evidence, and tests have been approved.
3. **Never auto-delete canonical records.** Never auto-merge institutions/campuses, overwrite source evidence, promote unverified imports, or change verification/status fields based on guesses.
4. **No automatic production migrations by a monitoring job.** Schema changes require reviewed release steps, a verified backup/recovery path, and a separate deployment decision.
5. **No secrets in logs or reports.** Never print database passwords, full environment configuration, or sensitive connection strings.
6. **Protect other site modules.** Automation must be scoped to the Institutes Laravel application and its configured database. It must not touch the CV Builder directory or database.
7. **Fail closed.** If configuration, permissions, backup status, or preconditions are uncertain, report the issue and stop the relevant action.
8. **No network-exposed database access required.** Prefer Laravel's existing on-host database connection. Remote MySQL access should remain disabled unless there is a separately approved, time-limited operational need.

## 3. Proposed automation layers

### Layer A — Database connection and runtime health

Run a low-cost, read-only check that records:
- whether the application can connect to its configured database;
- database driver and server version where safely available;
- migration status without executing migrations;
- required table presence;
- query latency and last successful check time;
- whether scheduled commands and required cache/queue mechanisms are configured, where applicable.

The report must distinguish **healthy**, **warning**, **critical**, and **check failed**. It must not expose connection secrets.

### Layer B — Canonical data integrity and readiness

Compose existing read-only services rather than duplicate their logic:
- `InstituteDataReadinessService`;
- `GeographyIntegrityService`;
- `GeographyGapDetectionService`;
- existing import/review readiness checks where available.

Report counts and actionable record identifiers for missing relationships, inactive/missing geography, duplicate identities, invalid parent relationships, and unreviewed import rows. Use bounded result lists and summary counts to prevent oversized logs.

### Layer C — Scheduled operational report

A scheduled Laravel command should produce a structured, timestamped summary and exit code:
- **Critical:** database unavailable, required tables missing, severe integrity failure, or backup verification failure.
- **Warning:** known data gaps, stale backup, scheduler not running, or non-blocking readiness issues.
- **Healthy:** all configured checks pass.

Use overlap protection, sensible timeouts, and a single source of truth for each check. Monitoring failures must themselves be observable. Notification delivery should be a separately configured adapter; no credentials should be committed to Git.

### Layer D — Backups and recovery assurance

Define and verify:
- automatic database backup cadence and retention with the hosting provider or an approved backup mechanism;
- encrypted/private storage separate from the application web root;
- backup age, size, completion status, and integrity checks;
- periodic restore tests in a **non-production** environment;
- a documented recovery-point objective (RPO), recovery-time objective (RTO), and restore runbook.

A backup is not considered verified merely because a file exists. Do not test restores against production. Do not delete old backups until retention policy and restore coverage are approved.

### Layer E — Deployment and schema-change gates

Before release:
1. run CI tests and formatting checks;
2. inspect migration status and migration diff;
3. verify a current recoverable backup;
4. validate application configuration without printing secrets;
5. confirm the release does not target the CV Builder database or directory;
6. require explicit approval for destructive or data-transforming migrations;
7. after release, run read-only smoke checks and record the deployed commit.

A failed gate should block the release where the platform permits; it must never attempt an improvised repair.

### Layer F — Safe automatic remediation (future, allowlisted only)

Potential candidates after separate approval:
- idempotent creation of known reference records only when their exact natural keys and parent relationships are verified;
- cleanup of expired **temporary operational artifacts** only under an approved retention policy, never canonical education records;
- retry of transient, idempotent operational jobs with bounded attempts.

Each rule must define: unique rule ID, exact eligibility query, dry-run output, maximum affected rows, transaction boundary, concurrency lock, audit record, postcondition, rollback/recovery plan, and stop conditions. All other findings remain human-reviewed tasks.

## 4. Geography bootstrap: special handling

The repository already contains an idempotent `institutes:seed-pakistan-geography` command for Pakistan and top-level provinces/territories. The production `geographies` table was previously observed to contain zero rows, which may explain the empty Province selector.

Do not run the seeder automatically against production yet. First:
1. review the seeder and its update behavior;
2. test it on a disposable database and prove reruns are idempotent;
3. verify its expected country/province/territory records against approved reference data;
4. ensure it cannot overwrite operator-managed fields unexpectedly;
5. run a production **dry-run/read-only preflight** and capture current counts;
6. obtain explicit approval for the one-time production seed;
7. execute through the Laravel application with a logged operator/action and verify counts afterward.

Districts and cities must not be inferred or fabricated by this top-level seeder. Imported geography candidates remain staged until governed review and promotion.

## 5. Proposed cadence

| Check | Suggested cadence | Mutation |
| --- | --- | --- |
| Application/database liveness | Every 5–15 minutes | None |
| Structural and geography integrity summary | Hourly | None |
| Backup age/completion check | Hourly or provider event | None |
| Backup restore drill | Monthly/quarterly in non-production | Non-production only |
| Deployment smoke checks | After each approved release | None |
| Safe remediation | Only per individually approved rule | Allowlisted only |
| Retention cleanup | Only after retention policy approval | Temporary artifacts only |

The cadence is a proposal and should be adjusted to hosting limits and measured cost. Avoid stacking heavy full-table scans on frequent liveness checks.

## 6. Operational visibility

The operations dashboard/report should show:
- last successful check and current status;
- last successful backup and last restore drill;
- migration drift (reported, not automatically applied);
- integrity finding counts and a small, permission-protected detail view;
- latest deployed commit and smoke-check result;
- recent automation actions, skipped actions, and failure reasons;
- clear next action and severity.

Operational records must be access-controlled and must not contain secrets. Alerting should be deduplicated to avoid repeated notifications for the same unresolved incident.

## 7. Testing and acceptance criteria

Before production use:
- unit tests for each check and classification;
- feature tests for command output, exit codes, permissions, and failure paths;
- tests proving all diagnostic commands are read-only;
- tests proving no action occurs when preconditions fail;
- idempotency and concurrency tests for each approved repair;
- tests for missing tables, failed database connection, empty geography, duplicate identities, and partial failures;
- backup/restore drill evidence from non-production;
- staging verification against a production-like schema;
- documented operator runbook and rollback plan.

Acceptance requires evidence that diagnostics never mutate canonical tables, secrets never appear in logs, and critical failures produce a non-zero result or equivalent observable alert.

## 8. Rollout plan

1. **Phase 1 authorized:** implement a read-only database health command and tests in a reviewable branch; verify CI and output before any merge.
2. **Phase 2 authorized:** implement a read-only geography seed impact preflight that lists create/no-change/update effects, fails closed on detected changes, and never executes the seeder; verify CI and output before any merge.
3. Inventory existing commands, scheduler, dashboards, hosting backup facilities, and notification capability.
4. Add scheduled reporting only after manual command output is verified and scheduling is separately reviewed.
5. Add backup freshness verification and a non-production restore drill.
6. Add release gates and post-deploy smoke checks.
7. Consider each automatic repair as a separate reviewed change.
8. Approve and execute the one-time geography seed only after its dedicated preflight and explicit production approval.

## 9. Explicit exclusions

This proposal does not authorize:
- production SQL changes performed externally;
- automatic migrations or schema alteration;
- deletion or merging of canonical institutions, campuses, programs, or source evidence;
- automatic promotion of imported data;
- invented geography data;
- changes to Hostinger Git deployment configuration;
- changes to CV Builder files, services, or database;
- enabling public or unrestricted remote MySQL access.

## 10. Approval needed

Review the proposed safety boundaries, cadence, backup targets, notification method, and one-time geography-seeding process. Implementation should begin only after this proposal is approved under the repository's documentation-first governance.
