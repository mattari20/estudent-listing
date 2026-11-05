# Phase 1C Test 1B — Read-only Schema Metadata Inventory

## Purpose

This companion diagnostic collects the live MySQL schema metadata needed to compare the production database with the seven migration source files covered by the Test 1B migration dependency audit.

It is intentionally metadata-only. It reads `information_schema` and does not select application-table records. It reports table presence, column definitions, index entries, foreign-key definitions, and trigger metadata. Trigger bodies are represented only by SHA-256 fingerprints.

## Safety contract

- Uses Laravel's effective cached database configuration when present; otherwise loads the application's dotenv and database configuration.
- Resolves Laravel's configured database URL with Laravel's URL parser.
- Prints only a SHA-256 fingerprint for the database host/schema/account tuple; never prints credentials or raw connection identifiers.
- Opens a read-only transaction and rolls it back.
- Reads schema metadata from `information_schema` only.
- Does not read application-table rows, migration-ledger contents, or business data.
- Does not run Artisan, migrations, DDL, DML, triggers, or application-file writes.
- Transports the diagnostic over SSH stdin; it does not create a remote temporary script file or persistent report.
- Requires the protected `production-readonly-diagnostics` environment and an operation-specific confirmation phrase.

## Output format

- `table=`: presence or absence for each of the 30 Test 1B tables.
- `column=`: table, column, type, nullability, key marker, extra attributes, and available precision/length metadata.
- `index=`: table, index name, uniqueness, column order, column, prefix length, and index type.
- `foreign_key=`: table, constraint, local column, referenced table/column, order, update rule, and delete rule.
- `trigger=`: trigger name, target table, timing, event, and SHA-256 fingerprint of the trigger body.
- `inventory_summary=`: aggregate metadata counts.

The output is intended to be attached to a review artifact and compared with the migration source. Presence alone does not prove equivalence; all column, index, foreign-key, and trigger attributes must be reconciled.

## Dispatch

Workflow: `.github/workflows/phase-1c-test-1b-readonly-schema-metadata-inventory.yml`

Required confirmation phrase:

`AUTHORIZE-PHASE1C-TEST1B-SCHEMA-INVENTORY-READONLY`

Use the latest exact `main` SHA at dispatch time. Do not reuse an earlier SHA.

## Acceptance boundary

A successful workflow means metadata collection completed. It does not authorize or execute production remediation. Missing schema objects, definition mismatches, and migration ledger discrepancies must be reviewed in a separate reconciliation report. No production migration is authorized by this diagnostic.
