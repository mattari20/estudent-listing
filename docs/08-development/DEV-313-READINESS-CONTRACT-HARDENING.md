# DEV-313 — Readiness Contract Hardening

## Purpose

Stabilize the final readiness layer with a small read-only contract that verifies the deployment-readiness response remains structurally usable for operator workflows.

## Contract checks

The contract confirms:

- deployment readiness is present;
- the readiness layer remains mutation-free;
- batch context contains the batch and source identifiers;
- blocking findings and their aggregate count are present.

This does not change promotion rules and does not authorize mutation by itself.

## Route

GET /institutes/operations/imports/{importBatch}/readiness-contract
