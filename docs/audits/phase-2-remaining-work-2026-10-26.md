# Institutes Directory — Phase 2 Remaining Work Plan

**Prepared:** 2026-10-26  
**Purpose:** Remaining Phase 2 work after the current audit checkpoint  
**Operating rule:** Read-only diagnosis first; all canonical data changes require a row-level plan and explicit approval. CI green is not production sign-off.

## Current checkpoint

- PR #821 — CSV BOM normalization and duplicate-header rejection: CI passed; open/unmerged.
- PR #823 — import integrity status semantics: CI passed; open/unmerged.
- Audit report commit `16e8a19a9dfc4c0eff8b20d3148047942e6154a2`: Laravel CI run 40319173991 and Test run 40319173978 passed.
- Live read-only snapshot: 19 active institutions, 22 active campuses, 85 active programs, 55 active offerings, 5 admission intakes; 36 active programs without active offerings.
- Import queue: 18 batches (15 validated, 3 failed); 347 rows (22 promoted, 126 candidate, 192 duplicate, 7 rejected); 9 rows lack approved identity decisions; 4 promotion-failure events.
- No production mutations were performed.

## Remaining Phase 2 plan (ordered)

### P0 — Close import failure evidence gaps
1. Extend or use a read-only batch-level diagnostic to emit exact IDs/status/timestamps and safe error categories for the 3 failed batches. Do not expose credentials, raw exception messages, private notes, or full payloads.
2. Inspect promotion event metadata for row IDs 15, 16, 17, and 29 and reconcile the earlier summary that one failure lacked active source evidence. Preserve audit trails.
3. Produce a retry eligibility matrix. Retry only rows/batches whose input, source evidence, identity decision, and idempotency conditions have been verified.

**Acceptance:** exact batch IDs and safe failure categories documented; every promotion event has an evidence-based classification or explicitly remains unknown; no mutation performed.

### P0 — Resolve duplicate queue safely
1. Export all 192 duplicate rows with row ID, batch ID, CSV row number, normalized key, duplicate detector result, candidate canonical ID, and dependency references where available.
2. Compare normalized names, aliases, slugs, parent institution/campus, and source evidence. Similar names alone are insufficient for a merge/delete decision.
3. Assign one disposition per row: link to verified canonical record, retain as distinct entity, correct source/input, or escalate for manual review.

**Acceptance:** all 192 rows have a documented proposed disposition and evidence; no deletion until a separate approved change plan exists.

### P0 — Verify missing offerings
1. Check each of the 36 program records against official institution/admissions pages.
2. Record source URL, page title/date if available, program identity, campus/location, level, intake/status, and evidence excerpt.
3. Classify each as confirmed offering missing, program inactive/obsolete, duplicate program, or insufficient evidence. Do not create offerings from assumptions.

**Acceptance:** source-backed disposition for every record; any proposed canonical changes separated into a reviewable plan.

### P1 — Identity decision and import governance
1. Review the 9 institution rows without approved identity decisions.
2. Verify reject/duplicate/candidate transitions, audit event creation, stale review prevention, and reversal behavior.
3. Add regression tests for conflicting identity decisions and failed promotion recovery.

**Acceptance:** each row has an evidence-backed disposition; critical transitions have automated tests.

### P1 — Discovery agent and source provenance
1. Verify scheduled discovery is candidate-only and cannot silently publish canonical institutions, campuses, programs, or offerings.
2. Test source URL validation, evidence excerpt handling, idempotency, duplicate source snapshots, parent selection, and review package lifecycle.
3. Verify source changes and stale evidence invalidate approval where appropriate.

**Acceptance:** automated tests demonstrate the agent stages candidates and cannot bypass approval/provenance gates.

### P1 — Security and operator routes
1. Enumerate all admin/operator routes and middleware.
2. Test guest, normal user, and authorized admin access for imports, review packages, approvals, exports, reset/repair utilities, and diagnostics.
3. Check CSRF, upload limits/type validation, output escaping, URL-fetch protections, authorization policy coverage, and audit logging.

**Acceptance:** route-to-role matrix and regression tests; no unguarded mutation route identified.

### P1 — Geography, search, and public directory
1. Review district-level coverage; distinguish missing data from an actual integrity defect.
2. Test geography cycles, inactive ancestors, parent constraints, import/seed/promotion enforcement.
3. Test search filters, pagination boundaries, no-results behavior, detail relationships, query safety, nearby search, and sitemap/canonical behavior.

**Acceptance:** reproducible tests for identified edge cases and a documented data-coverage decision.

### P2 — Schema, migration, workflow and deployment assurance
1. Inspect migration ordering, duplicate table creation, foreign keys/indexes, and deployed schema compatibility.
2. Review scheduled/manual workflow permissions, revision mismatch fail-closed behavior, concurrency, and whether workflows can trigger unsafe writes.
3. Re-run read-only live integrity and smoke checks after reviewed code is merged/deployed.

**Acceptance:** schema/workflow findings tied to exact file/line evidence; latest deployment revision and diagnostics are recorded.

## Release gates

- Keep changes in focused pull requests.
- Require CI success and review before merge.
- Do not treat CI as a substitute for live diagnostics.
- No production deletes, bulk approvals, promotions, or reset operations as part of audit-only work.
- Any future data repair must include exact IDs, before/after counts, relationship/dependency impact, reversible execution plan, and explicit authorization.
- Preserve users and migration history if a test-data reset is later separately approved.

## Phase 2 completion criteria

Phase 2 may be called complete only when:
1. P0 import failures, duplicate rows, missing offerings, and identity decisions have evidence-backed dispositions.
2. Discovery/promotion, security, geography, search, schema/migrations, and workflow paths are audited with critical regression tests.
3. All accepted fixes have passing CI and are merged through the controlled process.
4. The deployed revision matches the reviewed revision and read-only live integrity/smoke checks pass.
5. Remaining limitations are documented and accepted; no unresolved high-severity finding is silently marked complete.
