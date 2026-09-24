# DEV-314 — Operator Readiness Dashboard

## Purpose

Provide operators with one read-only summary of recent import batches and their deployment-readiness state.

## Scope

The dashboard reports the latest 20 batches with:

- batch identity and status;
- source ID;
- deployment readiness;
- blocking count;
- ready versus blocked batch totals.

It delegates readiness evaluation to the existing deployment-readiness service and does not create a second set of promotion rules.

## Route

GET /institutes/operations/readiness-dashboard

## Safety

Read-only diagnostics only. No import, repair, promotion, activation, or publication is performed.
