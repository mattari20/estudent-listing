# DEV-303 — Controlled Source Evidence Capture

**Depends On:** DEV-302  
**Scope:** Operator-controlled capture of source evidence and optional assertions

## Purpose
DEV-303 closes the next operational gap in the real-data pipeline: a governed mechanism to record the exact source reference and excerpt used to support institute data.

## Flow
Source → Evidence Capture → Assertions → Source Readiness → Import Verification → Promotion

Evidence is attached to an existing active source. Optional assertions connect the evidence to a canonical subject and attribute without automatically changing canonical data.

## Safety
- active sources only; enforced at both the authenticated controller boundary and the capture service boundary;
- authenticated operator route;
- request validation;
- no canonical institution mutation;
- no import promotion;
- no automatic identity resolution;
- no automatic publication.

## Service Contract
The capture service rejects inactive sources with an explicit `InvalidArgumentException`. This prevents alternate callers from bypassing the controller-level active-source guard.

## Real-Data Rule
This mechanism is intended for actual source material. It does not generate institute facts or fabricate evidence.
