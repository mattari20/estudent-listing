# Phase 5A — Discovery Idempotency and First Pilot Readiness

Date: 2026-10-29
Repository baseline: `main` at `d606c1fb9747167e354c5c82d26a08e8616465f0`

## Decision summary

- Discovery idempotency repair: implemented in this PR; CI must pass before merge.
- Historical production candidate rows: untouched. No production row was changed by this work.
- Exact six-row production audit: **BLOCKED** pending retrieval of the protected record-level diagnostics. The available successful Hostinger diagnostics run exposed aggregate/schema/API results, not the six rows' IDs, batch keys, source/snapshot IDs, content hashes, evidence IDs, or identity-decision events.
- Raw response comparison: **UNVERIFIED**. The six production snapshot bodies/hashes and stored evidence excerpts were not available in the retrieved diagnostic output, so it would be unsafe to assert that the differences are only analytics/footer/timestamp content.
- Private review package for an existing production GCU row: **BLOCKED** pending authorized invocation of the existing review-package service against the selected production batch and retrieval of its private manifest/CSV. This PR does not fabricate a package or copy production data into Git.
- Pilot of 5–10 institutions: **BLOCKED**. The GCU identity profile is enabled, but its six existing candidate rows have no approved identity/promotion decisions. Other HEC identity profiles remain disabled/unqualified for live retrieval and are not to be enabled to meet a count.

## Code-level root cause established

The discovery service's existing-batch reuse key is exact source content hash plus profile/source type and extractor version. Any changed response bytes therefore miss exact-snapshot reuse and enter candidate persistence again.

The previous top-level institution duplicate detector checked only exact equality against `normalized_payload->slug`. It did not compare conservative normalized keys derived from candidate name and official name, so legacy slug variants could evade deduplication. Candidate persistence also lacked a per-source transaction/row lock around the check-and-create sequence, allowing concurrent runs to race past a check before either candidate row became visible.

These are confirmed code paths that can permit repeated candidate rows; they are not proof of which path produced each of the six live rows. A per-row audit is required to attribute the production history conclusively.

## Repair

1. Serialize the discovery check-and-persist path inside a transaction while locking the existing source row. The HTTP retrieval remains outside the transaction.
2. Compare top-level institution candidates against canonical and staged records using conservative normalized slugs derived from configured slug, name, and official name.
3. Deliberately exclude short-name-only matching to avoid collisions between unrelated institutions/campuses.
4. Treat a match as a duplicate requiring review; do not merge, overwrite, approve, promote, or publish.
5. Preserve a new source snapshot, evidence, batch, and duplicate row for materially changed response content. Exact unchanged snapshots continue to reuse the existing batch/package.
6. Add regression tests for three changed response bodies yielding one candidate plus duplicate observations, and for a legacy slug variant matching by normalized institution name.

No schema migration or database constraint is added. Historical batches, rows, snapshots, evidence, and decision history are not deleted or rewritten.

## Production six-row audit — required evidence

For each of the six reported rows, retrieve the private record-level diagnostic fields without mutating production:

- row ID/status/match state; batch ID/key/source type/entity type/created time;
- source ID and official URL;
- snapshot ID, SHA-256 content hash, captured timestamp, HTTP status/content type if recorded;
- evidence ID, evidence content hash, excerpt, and source-to-snapshot lineage;
- payload and normalized identity fields (name, official name, short name, slug);
- identity decision history and promotion event history;
- any links between batches/rows indicating explicit refresh/supersession.

Then compare the six raw snapshot bodies or an approved normalized-text diff. A hash difference alone proves byte differences, not substantive identity changes. Keep raw bodies and row-level output private.

## Existing review-package and identity-decision workflow

Use the existing `InstituteDiscoveryReviewPackageService::create($batch, $snapshot, $evidenceId)` for the selected existing GCU candidate batch. Verify the private manifest's batch/snapshot/evidence IDs, raw snapshot hash, CSV checksum, official URL, candidate identity, excerpt, and the flags `canonical_mutations_performed=false` and `automatic_approval_performed=false`. Do not copy the package or raw response into a public repository.

The package was **not generated/retrieved in this phase** because the available connected GitHub operations did not provide an authorized way to invoke the protected production command and fetch its private-storage result. The selected row/batch ID also remains unverified here; selecting one by guess would be unsafe.

Record identity decisions through the existing import-row identity review workflow, using an explicit `approved_new` or `approved_match` decision with reviewer/reason and resolved canonical identity as applicable. A duplicate finding is not itself an identity decision. Run the existing verification, source-readiness, provenance-readiness, promotion-readiness, final-readiness, deployment-readiness, readiness-contract, and end-to-end gates before any separately authorized promotion.

## Pilot source qualification

| Source/profile | Current posture | Phase 5A decision |
|---|---|---|
| `gcu-lahore-institution-identity` — HEC Government College University, Lahore | Enabled; existing candidates need record-level review and explicit identity decision | Eligible only for review/package preparation; not ready for promotion |
| Other HEC identity profiles | Disabled; comments/configuration require isolated qualification and live response checks | Keep disabled; do not activate to reach 5–10 |
| Other enabled, qualified official identity sources | No verified list of 5–10 sources was available from the current evidence | Do not assume qualification; collect independent source evidence first |

**Pilot count: 0 of the requested 5–10 institutions is currently cleared for promotion.** The GCU row is a review candidate, not a promoted institution. Before any broader pilot, independently qualify each source by checking official ownership/authority, successful response status and content type, stable identity terms, fresh snapshot hash, evidence lineage, extraction quality, duplicate/campus ambiguity, and all readiness gates. Source enablement or schedule changes require separate authorization.

## Safety / production state

- No production migration, SQL repair, schema change, import, identity decision, promotion, publication, cron/schedule change, or profile activation was performed.
- No canonical institution was created or published.
- No AI API call was made.
- The six historical candidate rows and their provenance remain untouched by this PR.
- The historical `import_batches.duplicate_rows` ledger inconsistency remains untouched.

## Acceptance status

| Criterion | Status |
|---|---|
| Code-level duplicate mechanism documented | PASS |
| Safe idempotency repair and regression tests | Awaiting PR CI |
| Six production rows and decision history enumerated | BLOCKED — protected record-level report not retrieved |
| Substantive vs non-substantive response diff | UNVERIFIED |
| Existing private GCU review package verified | BLOCKED — protected package retrieval not available |
| Explicit identity decision | PENDING human review |
| Pilot qualification for 5–10 sources | BLOCKED |
| No canonical promotion/publication | PASS |
