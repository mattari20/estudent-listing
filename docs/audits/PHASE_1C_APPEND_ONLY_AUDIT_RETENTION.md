# Phase 1C — Append-only audit retention safeguards

Date: 2026-11-04  
Repository: mattari20/estudent-listing  
Base: e9feccedbb20078c3af24656e6399d55383b6722

## Scope

The repository contains immutable decision/event history alongside mutable lifecycle state. The migration replaces destructive cascade relationships with restrictive foreign keys on MySQL for identity-resolution decisions, verification decisions, import reconciliation batch history, import promotion decisions/events, reverification events, structured-enrichment promotion events, import-conflict events and governed execution-run events.

A preflight checks every affected child-to-parent relationship and fails closed if existing orphan history is found. No historical rows are rewritten or deleted.

## Safeguards implemented

- MySQL receives restrictive foreign keys so parent deletion cannot cascade away the listed history records.
- SQLite test databases receive database triggers rejecting direct UPDATE/DELETE on scoped immutable event tables and guarding parent deletes. Identity-resolution decisions allow only a one-time NULL-to-non-NULL decision_identity_key enrichment while all decision content remains unchanged.
- Eloquent model guards reject updates/deletes for security audit events, verification decisions, provenance review events, import promotion decisions/events, import conflict events, reverification events, structured enrichment promotion events and official source refresh events.
- Identity-resolution decision records reject deletion and all decision-content changes. A single NULL-to-non-NULL decision_identity_key enrichment is permitted to preserve the existing legacy idempotent replay path; decision, actor, reason and timestamp content remain immutable.
- Import-reconciliation event rows retain their existing nullable row association semantics; batch deletion is restricted while reconciliation history exists.
- Mutable operational state (data-quality lifecycle, monitoring checkpoints/alerts, current conflict state and other mutable projections) is not incorrectly classified as immutable event history.

## Driver and privilege design

The first isolated MySQL validation attempt rejected CREATE TRIGGER with MySQL error 1419 because the CI database account lacks SUPER while binary logging is enabled. The migration therefore does not require MySQL trigger privileges: MySQL uses foreign-key RESTRICT constraints plus Eloquent guards. SQLite test databases use triggers because SQLite permits them and they let tests verify direct-SQL immutability. No privilege escalation or CI MySQL server configuration change was made.

## Regression coverage

The focused test covers Eloquent update/delete rejection, one-time legacy decision-key enrichment, decision-content immutability, parent-row/batch deletion protection and transaction rollback. The isolated MySQL workflow runs the same focused test against MySQL 8.4.

## Unresolved limitation

On MySQL, foreign keys protect retained history from parent cascades, and model guards protect normal Eloquent mutation paths. Direct SQL UPDATE/DELETE against an event table can still bypass Eloquent guards because MySQL trigger creation is unavailable to the isolated CI account and the actual managed-host privilege configuration has not been inspected. Therefore this package is a partial safeguard, not a claim of fully database-enforced append-only immutability. Full direct-SQL enforcement requires a separately approved database privilege/security design compatible with the managed MySQL host. No production access was used to investigate those privileges.

## Status and safety

IMPLEMENTED: restrictive MySQL FK migration, SQLite test triggers and Eloquent model guards on branch. TESTED awaits exact-head Test/Laravel CI/Project Controller/isolated MySQL. MERGED and PRODUCTION VERIFIED are not claimed. Direct-SQL event mutation on MySQL remains an explicit unresolved security limitation pending an approved database-level enforcement mechanism.

No production migration, database access, Hostinger, Artisan production command, deployment or history rewrite was performed. GCU rows #348–353 and batches #19–23 remain protected. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data.
