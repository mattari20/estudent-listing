# Phase 1C Test 1B — Source-Level Review of All 18 Pending Migrations

**Reviewed source revision:** `66c7d0b64cc85b9523b64fe0df18d706cbcaf1e4`  
**Review type:** Static source/dependency/risk review. No production writes or migration execution.  
**Disposition:** Source review completed; production schema and data safety remain blocked by observed missing prerequisites and incomplete metadata.

## Executive summary

All 18 migration source files in the current pending inventory were reviewed. The set is not one uniform migration batch:

- 7 create-table migrations (including one two-table migration);
- 10 migrations alter existing tables and/or add indexes/keys;
- 1 high-impact migration changes audit-retention foreign keys and installs append-only triggers.

Several migrations perform data writes during `up()`, including copying open reverification and monitoring-alert rows into new identity tables and filling monitoring heartbeat values. Some migrations perform duplicate/orphan checks before schema changes. A migration ledger entry being absent is not proof that the migration is safe to rerun: existing objects and partial application must be reconciled first.

## Migration-by-migration source review

| Migration | Source operation | Data / dependency impact | Risk classification |
|---|---|---|---|
| 000001 password reset tokens | Creates `password_reset_tokens` with email primary key | No application-row backfill; table must be absent or structurally reconciled | Low DDL, but confirm table absence and target engine |
| 000002 user password flag | Adds `users.must_change_password`, default false | Existing users receive default semantics; confirm type/default and partial column state | Medium |
| 000003 import duplicate count | Adds `import_batches.duplicate_rows`, default 0, guarded by `hasColumn` | Existing batches get default value; confirm column type/default and any pre-existing values | Medium |
| 000001 structured enrichment events | Creates event table with FKs to enrichment rows and import batches; three indexes | Depends on both parent tables and compatible key types | Medium |
| 000001 governed execution leases | Creates leases table, unique task key and expiry index | No existing-row backfill; verify absence and UUID/key types | Medium |
| 000002 governed execution runs | Creates run and event tables; event FK to UUID run ID | Depends on exact UUID shape and both tables absent; two-table operation may partially apply on non-transactional DDL | Medium-high |
| 000003 retrieval idempotency | Adds nullable unique `source_retrieval_runs.idempotency_key` | Existing rows receive NULL; duplicate non-NULL keys matter if column already exists or is partially populated | Medium |
| 000004 snapshot idempotency | Adds nullable unique snapshot capture key and unique observation current-snapshot ID | Explicit duplicate check on `source_change_observations.current_snapshot_id`; then two schema alterations | High |
| 000005 discovery outcome key | Adds nullable unique `institute_discovery_run_outcomes.idempotency_key` | Existing rows receive NULL; inspect partial schema and duplicate non-NULL values | Medium |
| 000006 change-event source link | Adds nullable unsigned bigint and unique index to `change_events` | Existing rows initially NULL; confirm no incompatible partial column/index and exact numeric type | Medium |
| 000007 reverification identities | Checks duplicate open queue identities, creates identity table, copies every open queue row in chunks | Latest observed open rows: 5; duplicate open subject/attribute groups: 0. Backfill must be reconciled against existing identity table if partially created | High |
| 000009 monitoring alert identities | Checks duplicate open dedupe keys, creates identity table, copies open alerts, adds unique nullable audit idempotency key | Latest observed open alerts: 2; duplicate open dedupe groups: 0. The two-row identity copy is a production data write | High |
| 000010 monitoring checkpoints | Creates policy-run and policy-run-source tables with indexes | Depends on policy/source/retrieval/observation tables and compatible IDs; no data backfill in this migration | High |
| 000011 discovery persistence keys | Adds four nullable unique identity-key columns across batches, evidence, import rows and assertions | Existing rows become NULL; four sequential table alterations may partially apply; no key backfill in this migration | High |
| 000012 identity/conflict keys and events | Adds nullable unique keys to three existing tables; creates conflict event table with FK and unique idempotency key | Check partial columns/indexes, parent key type, and table absence. New event table initially empty | High |
| 000013 snapshot reconciliation requests | Creates request table with one unique key, five FKs and two indexes | New table has no existing rows; all five referenced tables/columns and key types must match | High |
| 000014 append-only audit retention | Replaces 11 FK rules with RESTRICT and installs 24 MySQL triggers; contains 11 orphan prechecks | High-impact retained-history protection. Requires all referenced history/parent tables, exact old FK names/definitions, compatible columns, and trigger definition verification. Migration creates triggers only when names are absent; a same-name but wrong-definition trigger would not be repaired | Critical |
| 000015 checkpoint FK and heartbeat | Adds heartbeat column, seven FKs, then updates NULL heartbeat values from `started_at` | Requires 5 related tables plus compatible key columns; checks 7 orphan relationships; heartbeat update is DML | Critical |

The 18 entries above are the same migration set in the full-ledger inventory. The filenames for 000001/000002 are distinguished by their full date prefixes in the repository; the shorthand labels in this table refer to the trailing sequence.

## Latest read-only data evidence relevant to migration source gates

From consolidated preflight run [#3](https://github.com/mattari20/estudent-listing/actions/runs/42146936130), on revision `66c7d0b64cc85b9523b64fe0df18d706cbcaf1e4`:

| Gate | Observed result | Interpretation |
|---|---:|---|
| Duplicate current snapshot IDs | 0 groups | Passed for the evaluated query |
| Duplicate open reverification identity groups | 0 | Passed; 5 open rows remain to be mapped by 000007 |
| Duplicate open monitoring alert dedupe groups | 0 | Passed; 2 open alerts would be copied by 000009 |
| Orphan checks | 7 evaluated / 11 skipped | Skipped are NOT passes |
| Expected audit triggers | 0 / 24 present | Schema gap |
| Expected pending ledger entries | 0 / 18 recorded | All 18 remain pending |
| Heartbeat NULL backfill | Not checked | Required table/column missing |
| Several identity duplicate checks | Not checked | Required columns/tables missing |

The current 39-table audit also reports 11 absent tables and missing expected columns/indexes. Refer to the paired evidence report in this PR for the exact inventory.

## Critical diagnostic limitations to retain as explicit blockers

1. **Foreign-key definition parity:** the metadata audit counts foreign-key metadata but does not yet prove the exact existing FK names, referenced columns, delete rules and compatibility for every FK that 000014 intends to replace.
2. **Trigger definition parity:** 0/24 names are present in the latest report; if a later run finds names, compare timing, event, table and normalized body, not just names.
3. **Skipped orphan checks:** all 11 checks skipped because dependencies are missing must remain `NOT_CHECKED`, not zero.
4. **Backfill impact:** 000007 maps 5 open reverification rows; 000009 maps 2 open alerts; 000015 copies `started_at` into NULL heartbeat values. These are data writes, not metadata-only migrations.
5. **Partial DDL:** MySQL DDL can commit independently. Multi-table/multi-step migrations need object-by-object partial-application reconciliation before any retry.
6. **Schema shape:** verify types, lengths, nullability, defaults, uniqueness, primary keys and referenced key types, not object names alone.
7. **Backups:** no dated backup inventory or successful restore evidence is established by these GitHub diagnostic runs.

## Safe next actions

- Complete the remaining aggregate-only data preflight for all relationships whose prerequisite tables and columns exist; keep unavailable checks explicitly `NOT_CHECKED`.
- Extend metadata capture to exact FK definitions and trigger definitions and compare against migration source.
- Obtain Hostinger backup retention and controlled restore evidence without altering production.
- Verify deployed revision parity.
- Produce a grouped, ordered remediation proposal with every DDL/DML operation, expected row effects, rollback limits, stop conditions and post-checks.

## Prohibited under this review

No `php artisan migrate`, migration rollback, DDL, DML, trigger installation, FK modification, application-file write, deployment, scheduler/worker change, live source retrieval, candidate staging, approval, import, canonical promotion or publication was performed or authorized by this source review.

**Source-level review: COMPLETE. Production data-integrity acceptance: INCOMPLETE. Test 1B: OPEN / BLOCKED.**
