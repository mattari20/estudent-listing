# Phase 1C — Monitoring checkpoint integrity and direct-caller concurrency

Date: 2026-11-04  
Repository: mattari20/estudent-listing  
Base: e9feccedbb20078c3af24656e6399d55383b6722

## Schema findings

The durable monitoring policy-run and per-source checkpoint tables previously used indexed scalar IDs without foreign keys. The active monitoring-alert identity registry also stored active_alert_id without a relationship constraint. The policy-run table had no heartbeat timestamp, and startPolicyRun classified every existing running row as interrupted, even if a direct caller had just created it. The command-level governed lease did not by itself protect callers that invoked the checkpoint/batch service directly.

## Remediation implemented

- Added an orphan preflight that fails closed before foreign-key creation if any existing policy, execution-run, source, retrieval-run, observation or active-alert reference is orphaned.
- Added restrictive policy and source checkpoint relationships so deleting a policy/source cannot silently erase checkpoint history.
- Added a nullable governed execution-run reference with SET NULL semantics to preserve policy-run history if the parent execution ledger row is removed.
- Added per-source checkpoint relationships to its policy run and source with RESTRICT semantics; retrieval-run and observation references use SET NULL so the checkpoint record remains.
- Added a nullable active-alert foreign key with SET NULL semantics to the monitoring alert identity registry.
- Added heartbeat_at to monitoring_policy_runs and refreshes it at each policy/source progress boundary alongside the outer governed execution heartbeat.
- startPolicyRun now locks the policy row. A run with a heartbeat in the last 15 minutes is considered active and a direct concurrent caller is rejected. Only running attempts with no heartbeat for at least 15 minutes are marked interrupted before a replacement attempt is created.

## Regression and concurrency coverage

- Updated abandoned-run recovery coverage to use a 20-minute-old heartbeat-less attempt.
- Added a direct service-call test proving a fresh active attempt is not superseded.
- Added a heartbeat refresh test.
- Added an independent PHP process script against isolated MySQL: the first worker starts and holds a policy attempt; the second direct caller is rejected; exactly one completed policy-run row remains.
- Extended the isolated MySQL workflow to run the new feature tests and multiprocess proof.

## Recovery semantics and limitations

The 15-minute staleness window is tied to a durable policy heartbeat, not only started_at. The batch service refreshes the policy heartbeat at its normal progress boundaries. A process stalled longer than the window without reaching a heartbeat boundary can be treated as stale; the existing bounded network request timeout and outer lease heartbeat remain part of the operational design. This does not activate monitoring or establish provider-side scheduling ownership.

## Safety and status

Repository migration and isolated CI only. No production monitoring, database access, migration, Hostinger, Artisan production command, scheduler/worker change or source retrieval was performed.

IMPLEMENTED on branch. TESTED awaits exact-head Test, Laravel CI, Project Controller and isolated MySQL results. MERGED and PRODUCTION VERIFIED are not claimed.

GCU rows #348–353 and batches #19–23 remain protected. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data.
