# DEV-302 — Source Verification Readiness

**Depends On:** DEV-301  
**Scope:** Read-only source-level verification readiness for controlled institute imports

## Purpose
DEV-302 verifies that a staged institute import is backed by an active source with current evidence and operational monitoring coverage before an operator treats the source as ready for controlled population.

## Checks
- source exists and is active;
- active evidence exists;
- fresh evidence exists;
- an active source monitoring policy exists;
- a completed source retrieval run exists.

The result is a readiness signal, not an automatic approval or promotion.

## Safety
The service is read-only. It does not create evidence, retrieve URLs, modify sources, enqueue reverification, promote import rows, or publish canonical records.

## Relationship to DEV-301
DEV-301 verifies the import batch and row-level workflow state. DEV-302 adds the source-verification layer so a syntactically valid and identity-reviewed import is not treated as fully source-ready merely because its rows pass import checks.

## Real-Data Boundary
No institute records are fabricated or automatically populated. Real source material must be captured through the existing governed source/evidence/retrieval workflows and then reviewed by an operator.
