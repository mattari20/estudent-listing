# DEV-312 — Deployment Readiness Gate

## Purpose

Add a final read-only operational gate that combines batch-level production readiness with canonical structural integrity.

## Checks

Deployment readiness requires:

- final production readiness for the selected import batch;
- structural canonical integrity across institutions, campuses, geography, programs, offerings, and admission intakes.

This keeps deployment approval from relying only on import/source provenance checks while ignoring canonical relationship integrity.

## Route

GET /institutes/operations/imports/{importBatch}/deployment-readiness

## Safety

The gate is diagnostic only. It performs no data repair, promotion, activation, deactivation, or publication.
