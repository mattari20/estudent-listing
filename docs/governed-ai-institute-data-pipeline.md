# Governed AI Institute Data Pipeline

## Purpose

Scale institute, campus, programme, and programme-offering discovery without requiring a person to manually investigate and repair every row. The AI agent is a discovery and evidence-gathering worker; it is not an authority to overwrite canonical records or publish unverified facts.

## Operating model

1. **Discover** — collect candidate facts from configured official sources and permitted secondary sources. Store source URL, capture timestamp, content hash, extractor version, and source authority.
2. **Normalize** — map extracted values to a versioned schema: institution, campus, programme, programme-offering, delivery mode, study level, dates/status, and evidence references. Preserve raw values alongside normalized values.
3. **Resolve identity** — match candidates to canonical entities using stable identifiers where available, then normalized names, institution/campus context, and location. A name-only match must never silently merge records.
4. **Validate** — run deterministic rules before creating a review package: required fields, URL validity, institution-campus-programme relationship, official-source authority, status/date plausibility, duplicate detection, and cross-record consistency.
5. **Score evidence and confidence** — compute field-level confidence from source authority, directness, recency, corroboration, and extraction quality. Keep confidence separate from evidence completeness; a high model confidence cannot replace missing official evidence.
6. **Compare with canonical data** — classify each candidate as new, unchanged, changed, duplicate, conflict, stale, or insufficient evidence. Produce a field-level diff, never just a whole-record replacement.
7. **Create a review package** — group changes by institution and source; include candidate facts, canonical values, field diffs, source citations/snippets, confidence, rule failures, duplicate matches, and recommended action.
8. **Apply policy** — auto-accept only low-risk changes meeting explicitly configured deterministic rules. Route identity changes, campus mapping, programme creation/deletion, delivery mode, accreditation/status, and conflicts to human review. Uncertain facts remain blocked.
9. **Promote transactionally** — after an allowed decision, re-check canonical row/version and uniqueness constraints, write only approved fields in a transaction, and record before/after values plus decision provenance. Reject stale review packages.
10. **Verify and monitor** — run post-write integrity checks, compare counts, record outcomes, alert on failures, and allow rollback through a documented compensating action where safe.

## Required record contract

Each candidate and proposed field change must retain:

- candidate_id, batch_id, source_id, source_url, source_authority, captured_at, content_hash
- entity_type, canonical_entity_id when matched, institution_id, campus_id, programme_id as applicable
- field_name, raw_value, normalized_value, previous_value
- evidence_url, evidence_excerpt_or_locator, evidence_type, evidence_captured_at
- extractor_version, normalizer_version, validation_rule_version
- confidence_score, evidence_completeness, validation_status, duplicate_match_ids
- decision, decision_by, decision_at, decision_reason, review_package_id
- immutable audit event with before/after values for every canonical write

Do not log credentials, session cookies, or unrestricted page contents into ordinary application logs. Apply retention and access controls to raw snapshots and review artifacts.

## Decision policy

- **AUTO_ACCEPT**: deterministic, low-risk field updates only; authoritative direct evidence; all required validations pass; no conflict; no identity or relationship change; uniqueness checks pass.
- **REVIEW_REQUIRED**: new institution/campus/programme, campus mapping, identity-resolution ambiguity, competing official sources, significant programme attributes, or medium confidence.
- **BLOCKED**: missing/invalid evidence, invalid relationship, duplicate conflict, low confidence, stale source, schema failure, or unsupported assertion.
- **NO_CHANGE**: normalized facts match canonical values; record the observation without creating duplicate candidates.
- **DUPLICATE**: retain a link to the canonical entity and evidence; never create another entity.
- **SOURCE_UNAVAILABLE**: record a retryable operational outcome, not a deletion or a claim that the programme no longer exists.

Confidence thresholds must be configurable and calibrated against reviewed examples. No single LLM score is sufficient to authorize a canonical write.

## Duplicate and identity resolution

Use a layered match strategy: official institutional/programme identifiers; exact normalized keys within parent scope; conservative fuzzy similarity as a review signal only. Never deduplicate across different institutions solely because names are similar. Enforce database uniqueness only on stable business keys validated for the domain. Keep rejected/merged candidate history for audit.

## Recency and change detection

Use content hashes to distinguish unchanged pages from changed pages. A changed page triggers extraction and field-level comparison; it does not itself authorize a change. Source policies define cadence, last successful check, next due time, failure backoff, and stale-after limits. A failed or empty fetch must not be interpreted as programme removal. Removal/deactivation requires explicit authoritative evidence and review.

## Scale and operations

- Queue work by source and institution; make jobs idempotent using source + snapshot hash + extractor version.
- Retry transient network/HTTP failures with bounded exponential backoff; quarantine repeated failures.
- Batch candidates and paginate review screens; support bulk decisions only when every row independently passes the same policy and shows its evidence.
- Keep metrics for fetched sources, parse success, changed/unchanged snapshots, extracted candidates, duplicates, validation failures, review backlog, accepted/rejected decisions, and post-write integrity failures.
- Provide a dry-run mode that emits a complete proposed diff without writes.
- Use feature flags per source/profile; disabled or untrusted sources cannot promote data.
- Keep database credentials least-privileged. Discovery credentials should not have write access; promotion should use a separate narrowly scoped execution path.

## Acceptance tests

The pipeline is not production-ready until automated tests prove:

1. An unchanged snapshot creates an outcome but no duplicate candidate and no canonical mutation.
2. A changed snapshot creates field-level proposals with evidence and extractor/rule versions.
3. Repeated processing of the same snapshot is idempotent.
4. Similar names across different institutions are not silently merged.
5. Missing or non-official evidence blocks sensitive fields and relationship changes.
6. Duplicate candidates link to canonical entities rather than creating duplicates.
7. A failed/empty source fetch never deletes or deactivates canonical data.
8. Conflicts, stale packages, invalid campus/programme relationships, and malformed output fail closed.
9. Only an explicitly approved review decision can reach the promotion service.
10. Before/after audit events, database constraints, and post-write integrity checks are recorded.
11. Dry-run/preflight modes prove zero writes and unchanged canonical counts.
12. End-to-end tests cover changed, unchanged, duplicate-only, conflict, blocked, review-approved, and failed-source outcomes.

## Applying this to the current 36-programme sample

Treat the 36 rows as a calibration set, not a manual cleanup checklist or production truth. For each row, the pipeline should generate a machine-readable result with canonical IDs found, official programme evidence, official campus evidence, current offering match, duplicate/conflict results, field-level proposed changes, blocker codes, confidence/evidence completeness, and next action. The current preflight already reports that records can be present while evidence gates remain blocked; this distinction must remain visible.

Do not edit canonical records merely to make the preflight green. First improve source evidence and identity resolution, rerun the same report, and only promote individually approved changes. Keep the evidence register's required 36 rows intact until its contract is deliberately versioned.

## Delivery sequence

- **A. Contract and observability:** freeze candidate/evidence/decision schema, stable blocker codes, and machine-readable outcomes.
- **B. Validation and matching:** centralize deterministic validators, conservative identity resolution, duplicate handling, and field-level diffs.
- **C. Review workflow:** evidence-rich review package, bulk actions constrained by policy, stale-package protection, and complete audit trail.
- **D. Governed promotion:** explicit approved decisions, transactional writes, least-privilege promotion identity, post-write verification, and rollback plan.
- **E. Scale acceptance:** multi-source fixtures, idempotency/load tests, retry/backoff, monitoring, and a representative reviewed calibration set before enabling more profiles.

Each stage must ship with tests and acceptance evidence. Enabling more sources or AI-assisted extraction is gated on the previous stage passing.