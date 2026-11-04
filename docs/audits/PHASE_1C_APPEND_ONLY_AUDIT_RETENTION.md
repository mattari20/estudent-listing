# Phase 1C — Database-enforced append-only audit retention

Date: 2026-11-04  
Repository: mattari20/estudent-listing  
Base: e9feccedbb20078c3af24656e6399d55383b6722

## Scope review

The repository contains immutable decision/event history alongside mutable lifecycle state. The migration treats the following as append-only: security_audit_events, change_events, identity_resolution_decisions, verification_decisions, provenance_review_events, import_reconciliation_events, import_promotion_decisions, import_promotion_events, reverification_events, structured_enrichment_promotion_events, official_source_refresh_events and import_conflict_events.

Data-quality events, current import-conflict state, monitoring alerts and monitoring policy/source checkpoints are not made immutable by this change because they carry mutable lifecycle or recovery state. Their audit history is represented by the separate append-only event records where applicable.

## Database enforcement implemented

- Adds BEFORE UPDATE and BEFORE DELETE triggers for each scoped event/history table on both MySQL and SQLite.
- Adds BEFORE DELETE parent guards for import rows, import batches, assertions, data-reverification queue rows, enrichment import rows and import conflicts where existing cascade relationships could remove dependent history.
- Parent deletion is rejected only when retained audit history exists; the guards do not blanket-disable deletion of records with no protected history.
- The triggers are installed by a new ordered migration and are removed by its down migration.
- No existing historical rows are rewritten, copied or deleted.

## Regression coverage

Added tests for security-audit event update/delete rejection, promotion-event update/delete rejection, import-row and import-batch cascade prevention, and transaction rollback. The isolated MySQL workflow now runs the focused test file against MySQL 8.4 in addition to the normal SQLite suite.

## Scope limitations

The trigger set is a repository-level database enforcement layer for the explicitly listed event tables and known cascade parents. It is not a claim that every table with an audit-like name in the wider application is immutable. Mutable operational state remains mutable. A future schema change introducing new audit/event tables or new cascade paths must extend this inventory and its tests.

## Safety and status

Repository migration and isolated CI only. No production migration, production database access, Hostinger, Artisan production command, deployment or history rewrite was performed.

IMPLEMENTED on branch. TESTED awaits exact-head Test, Laravel CI, Project Controller and isolated MySQL results. MERGED and PRODUCTION VERIFIED are not claimed.

GCU rows #348–353 and batches #19–23 remain protected. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data.
