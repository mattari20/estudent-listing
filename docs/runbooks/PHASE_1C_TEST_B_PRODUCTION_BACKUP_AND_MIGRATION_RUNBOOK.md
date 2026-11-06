# Phase 1C Test B — Production Backup and Controlled Migration Runbook

**Status:** Preparation only; not an execution record.  
**Engineering baseline:** PR #1050 merge 34f7e87e385859a7e038a0a2043149559d5a384b.  
**Scope:** The 18 migrations identified in the Test B reconciliation.  
**Authorization:** The owner authorized backup preparation and production migration only subject to backup and preflight. This runbook does not bypass protected GitHub environment gates or authorize application deployment.

## 1. Hard stop conditions

Do not execute production DDL/DML unless all are true:

- A complete production database backup exists, is private, non-empty, integrity-checked, and its restore has been tested in an isolated database.
- A current production read-only schema inventory is tied to the exact current main SHA.
- The deployed application-code revision is verified against the reviewed code required by the migrations.
- Every dependency and data-integrity preflight passes. Missing prerequisites or skipped checks are blockers, not passes.
- The exact migration manifest, order, and operation-specific confirmation are approved.
- The protected production execution environment is configured and its required credentials are present.
- No protected GCU historical rows #348–353 or batches #19–23 are in scope for mutation.

If any condition fails, stop without changing production.

## 2. Backup preparation and evidence

No backup has yet been created by this runbook. Use the Hostinger-supported backup mechanism available for the account, or a reviewed database-export method with an approved private destination.

### Required backup scope

1. Full application database, including schema, rows, indexes, foreign keys, triggers, views, routines/events if present.
2. Application code snapshot or a verified deployable revision and configuration inventory. Do not include plaintext secrets in the report.
3. Existing Hostinger backup metadata where available: creation time, completion status, retention/expiry and restore capability.

### Safe export requirements

- Confirm the actual production database engine/version and a compatible export client before choosing flags.
- Resolve the effective Laravel database connection without printing host, username, password, URL or environment-file values.
- Use a private destination outside the public web root; verify the directory is owned by the application account and not web-served.
- Use restrictive permissions (umask 077; directory mode 700; file mode 600).
- Use a unique timestamped filename and refuse to overwrite an existing backup.
- Stream the export through compression where supported; do not send database contents to GitHub logs or Actions artifacts.
- Record only the backup filename, size, checksum, timestamp, database fingerprint and verification outcomes in the diagnostic report. Do not record credentials or row payloads.

### Backup verification

- Confirm the export completed with exit status zero and non-zero file size.
- Verify compressed-file integrity and SHA-256 checksum.
- Restore the export into a separate isolated MySQL/MariaDB database, never over production.
- Verify restored table count, migration ledger, critical indexes, foreign keys and triggers against the backup's own schema.
- Record the restore test result and cleanup only the isolated test database after evidence is captured.
- Preserve the original backup and existing Hostinger backups; do not delete old backups to make room without separate approval.

A backup that has not passed an isolated restore test is not a verified recovery point.

## 3. Production preflight

Run only the existing protected read-only diagnostics against the current main SHA. The last all-18 preflight was tied to the prior revision and must not be treated as current after subsequent code changes.

Required evidence:

- Database fingerprint matches the previously verified production target.
- Current production table/column/index/FK/trigger definitions are captured.
- Each of the 18 migration ledger entries is counted.
- Each expected schema object is classified as absent, exact, partial, or incompatible.
- Every duplicate/orphan/backfill precondition is evaluated or explicitly marked NOT_CHECKED.
- The 5 queue identity and 2 alert identity backfill candidates are reconciled by deterministic key; no automatic remapping of conflicting identities.
- Storage engines, privileges and FK compatibility are confirmed.
- Current deployed application-code revision is compared with the reviewed code revision.

Do not infer readiness from a green workflow status alone.

## 4. Migration execution strategy

- Execute only the reviewed 18-migration manifest in timestamp/dependency order.
- Do not run an unrestricted php artisan migrate against production; it may include unrelated pending migrations.
- Use the controlled production workflow with an explicit allow-list and per-migration ledger/object verification.
- Stop at the first failure. Do not blindly rerun after MySQL DDL partial application.
- Before retrying, inspect the exact object state and classify each expected object as absent, exact, partial or incompatible.
- Never manually insert migration-ledger rows to make the workflow green.
- Never drop a non-empty table or truncate data as a generic repair.
- Apply only the explicitly defined idempotent backfills; record aggregate counts and before/after reconciliation results, not row payloads.
- Verify after each migration before proceeding to the next dependency group.

### Application-code parity gate

PR #1050 adds App/Support/Database/MigrationSchemaGuard, which the hardened migrations call. If this helper and the reviewed migration files are not already present in the deployed Hostinger application code, the production migration cannot run safely against the old code.

The owner has authorized production migrations but has not separately authorized deploying application code. If parity is not already satisfied, stop and obtain separate deployment authorization rather than silently deploying or copying application files.

## 5. Post-migration acceptance

- Exactly one migration-ledger row for each of the 18 migration names.
- All expected tables, columns, indexes and storage engines match their reviewed definitions.
- All 23 expected foreign keys match referenced table/column and delete/update rules.
- All 24 expected triggers match name, table, timing, event and body.
- Backfill rows reconcile to their source identities; no duplicate mappings or unexpected changes.
- Orphan and duplicate checks are evaluated and pass.
- Application read-only smoke tests pass.
- The all-18 production preflight is rerun against the post-migration state.
- Capture final schema fingerprint and execution report without secrets or application row payloads.

## 6. Authorization boundary

The owner authorized:
- Grouped migration engineering and isolated MySQL tests.
- Backup preparation.
- Production migrations after backup and preflight conditions are satisfied.
- PR merges after all required checks pass.

Not yet separately authorized:
- Deploying/updating application code on Hostinger.
- Destructive data cleanup or table drops.
- Modifying protected GCU historical records.
- Publishing dummy/test records.
- Broad production database grants beyond the minimum required migration privileges.

Production migration workflows currently contain fail-closed owner/environment gates. Do not remove those gates or substitute repository secrets until the required production environment and credentials are explicitly configured and reviewed.
