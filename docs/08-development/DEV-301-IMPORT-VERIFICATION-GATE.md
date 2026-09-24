# DEV-301 — Controlled Institute Import Verification Gate

**Document ID:** DEV-301  
**Depends On:** DEV-300  
**Scope:** Read-only verification of staged real institute import batches

## Purpose
DEV-301 adds a batch-level verification boundary between validated import data and canonical promotion. It does not create or promote institute records.

## Governed Flow
Source → Import Batch → Validation → Candidate → Identity Resolution → Verification Gate → Operator Promotion → Audit → Public Representation

## Blocking Checks
- inactive or missing source;
- batch not in validated state;
- invalid rows;
- candidates without an approved identity decision;
- approved matches without a canonical institution reference;
- candidates with open import conflicts.

## Non-Destructive Boundary
The verification service does not create, update, delete, resolve, approve, promote, repair, or publish data.

## Access
The endpoint is protected by authentication and the existing institute operator authorization middleware and is not a public API contract.

## Interpretation
`verification_ready=true` means the defined gate has no blocking findings and at least one candidate is currently eligible. It does not mean the source is permanently authoritative or that promotion must occur.

## Testing
Tests cover unresolved candidates, an approved new candidate, open conflicts, and mutation-free behavior.

## Real-Data Rule
No fabricated institute records are introduced. Real data continues through source-backed import batches and explicit operator review.


## DEV-305 Evidence Readiness Extension
Candidate institute rows are not verification-ready unless they have at least one active source evidence assertion explicitly attached to the import row. This remains read-only and does not promote, mutate, or publish canonical data. The evidence must be captured through the controlled source-evidence flow.
