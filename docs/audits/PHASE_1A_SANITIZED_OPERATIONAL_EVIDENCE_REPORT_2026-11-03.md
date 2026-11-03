# Phase 1A — Sanitized Operational Evidence Report

**Prepared:** 2026-11-03  
**Repository baseline checked:** `9141ffddd4462fdb8c9e9f8ab3a296a56ff34db4`  
**Report status:** Draft for owner review; not a Phase 1A completion certificate  
**Visibility:** This file is stored in the public `mattari20/estudent-listing` repository. The owner explicitly accepted public visibility for this sanitized report on 2026-11-03.  
**Retention:** GitHub repository history has no automatic expiry configured by this report. This report remains in repository history until the owner removes/re-writes it or changes repository retention/access arrangements. Repository visibility must be reviewed again when the project is made private.

## 1. Scope and safety boundary

This report consolidates the read-only evidence supplied or reviewed for Phase 1A. It deliberately excludes credentials, tokens, database names or contents, server addresses, account identifiers, private diagnostic payloads, raw logs, and sensitive filesystem paths.

No database writes, migrations, imports, approvals, canonical promotions, publications, source activation, deployments, cache clears, cron edits, queue operations, backup restores, or production data changes are authorized by this report. Historical GCU candidate rows #348–353 and batches #19–23 remain protected; the additional 20 HEC profiles and AI extraction remain disabled. No dummy production data was created.

Evidence classes:
- **Owner-supplied UI/terminal evidence:** screenshots and command output supplied by the project owner; not independently captured by this report author.
- **Repository evidence:** static configuration or GitHub metadata read-only review; does not prove deployed runtime state.
- **Unverified:** not established by the evidence available for this report.

## 2. AUTH-03 — bounded Artisan inspection

The owner supplied a terminal screenshot showing the Laravel scheduler listing:

```text
0 * * * * php artisan institutes:bootstrap-agent
0 * * * * php artisan institutes:monitor-changes
```

Both commands are registered hourly. This confirms scheduler registration at the inspected runtime; it does not prove that an external scheduler invokes Laravel's scheduler or that either command is currently executing. The two scheduled tasks were not disabled or changed.

## 3. AUTH-04 — production database metadata

**Status: BYPASSED / NOT VERIFIED.**

The owner elected to bypass the production database metadata inspection rather than change the application's database-user privileges. No temporary privilege change was made and none of the five planned metadata SELECT queries was run under this authorization. Production schema, index and migration-ledger readiness therefore remain unverified by this evidence packet. Historical screenshots of database administration interfaces are not treated as proof of current schema correctness.

## 4. AUTH-05 — Hostinger read-only operational evidence

### 4.1 Cron and scheduler

Owner-supplied hPanel screenshots showed the Cron Jobs creation form and no saved-job entries in the visible area. The owner reported that this same view had been checked repeatedly. The SSH inspection output showed that the inspected account had no user crontab. The system crontab was not readable by that account.

**Conclusion:** no user crontab was present and no saved entries were displayed in the supplied hPanel view. This does not establish that no panel-managed, system-level, or provider-managed scheduler exists.

### 4.2 Queue worker

Owner-supplied read-only process output showed the interactive shell, PHP web process and the audit's own process-list command. No persistent Laravel queue worker was visible in that account-scoped process snapshot. `supervisorctl` was unavailable to the inspected account.

Repository `config/queue.php` defaults to the database queue driver and identifies the conventional `jobs` and `failed_jobs` tables, with a 90-second retry-after default. These are repository defaults, not proof of deployed queue environment values or worker health.

**Conclusion:** no persistent worker was observed in the supplied account-scoped snapshot. A worker managed outside that account's visible process scope remains unverified.

### 4.3 Backups

Owner-supplied hPanel screenshots showed:
- Automated backups on a weekly schedule.
- Latest displayed backup: 2026-11-01 18:05.
- Next displayed backup: 2026-11-08.
- Five visible restore-point dates: 2026-10-07, 2026-10-14, 2026-10-21, 2026-10-28 and 2026-11-01.
- Website, files and database backup selection options were visible; the owner selected the Institutes database in the database backup view.
- The interface stated that backup plugin archives, cache and database export files are excluded from backups.
- Manual backups were shown as locked on the current hosting plan.

The screenshots do not establish the provider's complete formal retention policy, backup storage destination, or a successful restore test. No restore or download was initiated.

## 5. Scheduler ownership and duplicate-execution risk

Repository source review establishes that Laravel registers `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly with `withoutOverlapping(55)`. The governed GitHub discovery workflow also defines an hourly schedule and invokes the same commands, with separate manual/workflow-run triggers and its own concurrency control.

The GitHub discovery workflow (ID `394137413`) was reported by the owner, using authenticated GitHub CLI/API output, as `disabled_manually` on 2026-11-03. This is owner-supplied API evidence, not a new state change performed for this report.

Laravel's cache lock and GitHub's concurrency group are separate mechanisms. The repository's default cache configuration does not establish the production cache driver or a shared cross-runner lock. No supplied evidence establishes whether Hostinger or a provider-managed scheduler invokes `schedule:run`.

**Conclusion:** repository-defined execution paths are known, but actual production scheduler ownership and cross-runner overlap are not fully resolved.

## 6. Remaining evidence gaps

| Gate / item | Status | Outstanding evidence |
|---|---|---|
| AUTH-01 / P1A-01 | Partial | Complete current workflow enabled-state inventory and paginated active-run snapshot |
| AUTH-02 / P1A-02 | Partial | Fresh deployed revision/runtime parity evidence against the current baseline |
| AUTH-03 / P1A-03 | Inspected, review pending | Reconcile complete command/help output and confirm any remaining acceptance items |
| AUTH-04 / P1A-04 | Bypassed | Production schema/index/migration-ledger verification remains pending |
| AUTH-05 cron / P1A-05 | Partial | Confirm provider-managed/system-level scheduler possibility and complete saved-entry view if available |
| AUTH-05 worker / P1A-06 | Unverified | Provider confirmation or authoritative worker/process evidence outside the inspected account scope |
| Scheduler / P1A-07 | Partial | Confirm actual scheduler invocation, production cache lock backend and cross-runner idempotency |
| Backups / P1A-08 | Partial | Formal retention duration, storage details and prior successful restore-test evidence |
| Private report controls | Publicly accepted for this report | Repository is public; all repository viewers can read this report. No automatic report expiry is configured. |
| Failed diagnostic run | Unresolved | Sanitized final error excerpt for the specifically identified failed Hostinger diagnostic step |

## 7. Overall decision

**Phase 1A remains INCOMPLETE / BLOCKED.** This report is an evidence consolidation, not proof of production schema readiness, complete operational ownership, nationwide data readiness, or end-to-end autonomous operation.

No further production operation is implied. Any future database inspection, scheduler/queue change, restore test, workflow action, deployment, migration, source activation, import, approval, promotion or publication requires its own applicable authorization.

## 8. Public-report review checklist

- [x] Owner explicitly accepted public visibility for this sanitized report on 2026-11-03.
- [x] Sensitive credentials, tokens, database names/contents, raw logs and private diagnostic payloads excluded.
- [x] AUTH-04 bypass and evidence limitations stated.
- [x] No production write or configuration change performed for this report.
- [ ] Owner reviews this draft before merge.
- [ ] Owner reviews repository visibility and access again when the project is made private.
