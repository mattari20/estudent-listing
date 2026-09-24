# DEV-309 — Final Production Promotion Readiness

## Purpose

Provide one read-only operator gate that composes the existing import-verification and source-verification gates before canonical promotion.

## Required conditions

The batch is promotion-ready only when:

- import verification is ready;
- source verification is ready;
- all underlying blockers are clear;
- eligible candidate rows exist.

The service does not create, update, promote, reverse, or publish canonical data.

## Route

Authenticated operators can inspect:

GET /institutes/operations/imports/{importBatch}/promotion-readiness

The response includes the batch context, both underlying readiness summaries, blocking findings, and a final `promotion_ready` boolean.

## Governance

This is an orchestration gate, not a replacement for the existing promotion services. A positive result authorizes an operator to proceed to the existing controlled promotion workflow; it does not itself perform promotion.
