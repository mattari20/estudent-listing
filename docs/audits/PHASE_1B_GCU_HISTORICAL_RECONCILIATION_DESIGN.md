# Phase 1B — GCU Historical Candidate Preservation and Reconciliation Design

Date: 2026-11-03
Scope: non-executing design. Historical production rows and batches are not queried or changed.

## 1. Preservation invariant

The previously identified GCU candidate rows #348–353 and batches #19–23 are protected historical records. This design does not infer their present row-to-batch mapping, evidence quality, approval status or canonical identity from their numeric ranges. Those facts require authorized, read-only record-level evidence in a later phase.

Until then:
- Do not delete, overwrite, renumber, merge, approve, reject, import or promote these records.
- Do not create replacement/dummy records to make the public directory look populated.
- Preserve source snapshots, evidence links, import-batch history and review events.
- Keep the 20 additional HEC profiles and AI extraction disabled as already recorded.

## 2. Reconciliation inputs required later

A separately authorized read-only export must provide, for each target row:
- row ID, batch ID, row number, entity type and current status;
- parent/canonical subject type and ID, if present;
- raw payload and normalized payload, with secrets/private data redacted;
- validation errors and duplicate/match state;
- source ID, source URL, snapshot ID and evidence IDs;
- source capture time, evidence timestamp and provenance method;
- existing review/approval/promotion events and actor/time;
- immutable batch metadata and any supersedes/superseded relationships.

If any field cannot be obtained, mark it UNKNOWN; never synthesize it. Keep export read-only and sanitize sensitive content before storing any artifact in this public repository.

## 3. Identity and duplicate-resolution rules

1. Primary identity uses normalized official legal name plus institution type and jurisdiction/location, corroborated by authoritative source evidence.
2. Acronyms, transliterations, punctuation variants and historical names are aliases, not sufficient standalone proof of identity.
3. Campus identity must remain distinct from a standalone parent institution. A campus must not be promoted as a separate university merely because it has its own page or slug.
4. Similar names, shared acronyms, same city or common parent terms are duplicate-review signals, not automatic merge instructions.
5. A duplicate decision requires evidence-backed identity equivalence and an explicit reviewer decision.
6. Preserve all source provenance and historical IDs when a duplicate is confirmed; record the canonical survivor and the reason in an append-only reconciliation event.
7. Conflicting official sources, uncertain parent relationships or ambiguous identity remain unresolved and must not be auto-merged.

## 4. Evidence disposition states

Use distinct outcomes:
- \`VERIFIED\`: authoritative evidence directly supports the identity and required fields.
- \`INSUFFICIENT_EVIDENCE\`: source/evidence is missing, inaccessible, stale or does not support the field.
- \`CONFLICTING_EVIDENCE\`: authoritative evidence disagrees.
- \`DUPLICATE_CONFIRMED\`: equivalent identity established with cited evidence and reviewer decision.
- \`NOT_A_STANDALONE_INSTITUTION\`: evidence identifies a campus/constituent unit rather than a separate institution.
- \`REJECTED\`: reviewer rejects the candidate with a recorded reason.
- \`PENDING_REVIEW\`: evidence package is complete enough for a human decision but no decision exists.
- \`UNKNOWN\`: the historical state cannot be established from authorized evidence.

Missing evidence is never equivalent to rejection. A source retrieval failure is never proof that the institution does not exist.

## 5. Dry-run reconciliation report contract

The future dry-run report must be deterministic and read-only. For every row, include:
- original row/batch IDs and immutable source identifiers;
- evidence IDs and source timestamps;
- current state and proposed disposition;
- normalized identity key and possible duplicate candidates;
- confidence/rationale with field-level evidence references;
- unresolved conflicts and missing evidence;
- proposed action (no-op, request evidence, human review, duplicate review);
- explicit \`canonical_mutation_performed=false\`, \`approval_performed=false\`, \`import_performed=false\`;
- report revision, code revision, run ID and generation timestamp.

The dry-run must not write back to source tables, change row status, create a new candidate batch, or update canonical tables. Any report persisted for audit must be written only to an approved private destination; the current sanitized report is public under an explicit exception.

## 6. Human approval and append-only history

A future decision must record:
- reviewer identity and role;
- exact row and batch IDs;
- selected disposition and rationale;
- field-level evidence references;
- before/after state;
- timestamp and application revision;
- whether a second reviewer is required;
- explicit approval scope (field-only, identity resolution, or promotion eligibility).

Approval of a candidate is not itself permission to import or publish it. Canonical promotion remains a separate controlled operation with preflight, transaction/rollback design, schema verification and explicit authorization.

## 7. Acceptance tests required before execution

- Historical row and batch IDs remain unchanged through a dry-run.
- Missing evidence maps to \`INSUFFICIENT_EVIDENCE\` or \`UNKNOWN\`, never implicit rejection.
- Campus/parent ambiguity cannot auto-merge.
- Acronym-only or same-city matches cannot auto-merge.
- Conflicting official evidence remains unresolved.
- Confirmed duplicate requires evidence references and a reviewer event.
- Dry-run emits no canonical writes, approvals, imports or publications.
- Repeated dry-run with identical inputs is deterministic.
- Report includes all target rows or an explicit incomplete-input blocker.
- Any export/report is sanitized and stored only in an approved private location.

## 8. Explicit non-actions

No production query/export, source retrieval, candidate staging, approval, rejection, import, canonical promotion or publication occurred. Rows #348–353 and batches #19–23 remain to be preserved without assumed mapping. No dummy production data was created.
