# Phase 1C Test 1B — Structural Metadata Audit

**Purpose:** capture exact live MySQL schema metadata needed to compare the production schema against the 18 pending migration definitions.

## Safety boundary

This is a read-only metadata diagnostic. It uses Laravel's effective cached or dotenv database configuration, a read-only transaction, and information_schema queries only. It emits schema metadata (table engines, column definitions, index composition, FK columns and rules, trigger timing/event/definer and SHA-256 trigger-body fingerprints). It never selects application row payloads, runs migrations, issues DDL/DML, changes application files, or creates remote temporary files.

The output includes a non-secret database fingerprint. Compare it with the consolidated all-18 preflight fingerprint to ensure both diagnostics inspected the same target.

## Run

Dispatch **Phase 1C Test 1B — Structural Metadata Audit (Read-only)** from GitHub Actions on current `main`. Supply the exact current main SHA, the existing Institutes project path, a reason, and confirmation phrase:

`AUTHORIZE-PHASE1C-TEST1B-STRUCTURAL-METADATA-READONLY`

The workflow uses the existing `production-readonly-diagnostics` environment. It does not authorize schema changes.

## Interpretation

The workflow captures actual metadata; it does not itself assert that production matches migration source. Compare its output to the exact migration definitions for:
- column type, nullability, default, generated expression and extra attributes;
- index uniqueness, ordered columns, prefix lengths and index type;
- FK ordered columns, referenced columns, update/delete rules;
- table engine and collation;
- trigger event, timing, definer and body hash.

A matching name alone is insufficient. Any partial or mismatched object remains a blocker and must be documented before remediation.

## Recovery evidence remains separate

A successful metadata audit does not prove backup recoverability. Obtain a dated Hostinger backup inventory, retention details and a successful controlled restore proof in an isolated staging location. Do not restore over production and do not treat a backup listing as restore proof.

## Acceptance

Test 1B remains open until structural comparison, all data-safety checks, recovery proof and a separately reviewed controlled remediation proposal are complete. No production migration or manual schema change is authorized by this diagnostic.
