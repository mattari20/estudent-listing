# Phase 5A — Discovery Idempotency and First Pilot Readiness

Date: 2026-10-29
Repository baseline: `main` at `d606c1fb9747167e354c5c82d26a08e8616465f0`

## Decision summary

- Discovery idempotency repair: implemented in this PR; CI must pass before merge.
- Historical production candidate rows: untouched. No production row was changed by this work.
- Six-row production trace: **PARTIALLY VERIFIED** from successful read-only Hostinger Diagnostics run #40866751798. All six row IDs, batch IDs, source ID, identity fields, statuses, and decision-history state are available; the bounded evidence-lineage section exposed snapshot/evidence/hash details for five rows only.
- Raw response comparison: **UNVERIFIED**. The five exposed SHA-256 content hashes differ, proving bytewise differences, but the raw snapshot bodies and stored evidence excerpts were not returned, and row 353's detailed lineage was omitted by the bounded report. The differences cannot be classified as substantive or non-substantive from hashes alone.
- Private review package for an existing production GCU row: **BLOCKED** pending authorized retrieval of its private manifest/CSV. The existing review-package service and protected download route were identified, but no production package was generated or downloaded in this phase.
- Pilot of 5–10 institutions: **BLOCKED**. The GCU identity profile is enabled, but its six existing candidate rows have no approved identity/promotion decisions. Other HEC identity profiles remain disabled/unqualified for live retrieval and are not to be enabled to meet a count.

## Code-level root cause established

The discovery service's existing-batch reuse key is exact source content hash plus profile/source type and extractor version. Any changed response bytes therefore miss exact-snapshot reuse and enter candidate persistence again.

The previous top-level institution duplicate detector checked a JSON-path equality on `normalized_payload->slug`, without normalized name/official-name fallback. The six trace rows all have the same slug, so a functioning exact-slug lookup should have caught sequential repeats; slug variants alone do **not** explain this particular history. Exact-content-hash batch reuse explains why changed source bytes create new batches, but the production-specific reason the duplicate check failed to classify these rows remains **UNVERIFIED** (for example, an older deployed detector path, database JSON-path behavior, or a concurrency race must be distinguished with deployed-file and query-level evidence). Candidate persistence also lacked a per-source transaction/row lock, so concurrent runs were not serialized.

The repair replaces the fragile JSON-path-only check with conservative normalized identity keys read from existing staged payloads, and serializes check-and-create per source. This closes the identified code gaps; it does not retrospectively prove which execution path produced each historical row.

## Repair

1. Serialize the discovery check-and-persist path inside a transaction while locking the existing source row. The HTTP retrieval remains outside the transaction.
2. Compare top-level institution candidates against canonical and staged records using conservative normalized slugs derived from configured slug, name, and official name.
3. Deliberately exclude short-name-only matching to avoid collisions between unrelated institutions/campuses.
4. Treat a match as a duplicate requiring review; do not merge, overwrite, approve, promote, or publish.
5. Preserve a new source snapshot, evidence, batch, and duplicate row for materially changed response content. Exact unchanged snapshots continue to reuse the existing batch/package.
6. Add regression tests for three changed response bodies yielding one candidate plus duplicate observations, and for a legacy slug variant matching by normalized institution name.

No schema migration or database constraint is added. Historical batches, rows, snapshots, evidence, and decision history are not deleted or rewritten.

## Production six-row audit — read-only evidence

Run #40866751798 completed successfully on 2026-10-29. The private record-level trace confirms six candidate rows across six validated batches, all from source ID 27, with the same normalized institution slug. Each row had zero validation errors, no open conflicts, active source evidence, no identity decisions, no promotion decisions, and no approved promotion decision. The canonical institution count remains zero. The rows were not modified.

| Row ID | Batch ID | Batch key (as returned) | Source ID | Snapshot ID | Evidence ID | Evidence SHA-256 / content hash (as returned) | Observed at (server local) |
|---:|---:|---|---:|---:|---:|---|---|
| 348 | 19 | `agent-gcu-lahore-institution-identity-pcxaxbmtceketifx` | 27 | 8 | 15 | `f7d3388f9c9c6d80e90743517f8200f660481f49cc8c051602da1360683460b4` | 2026-10-28 18:44:56 |
| 349 | 20 | `agent-gcu-lahore-institution-identity-ylupgij5zfcx3nw7` | 27 | 9 | 16 | `1c11facffabac7a933b8d5e7f9ddcbfe8d940e32fc6c7f6d49088d449de419cd` | 2026-10-28 18:44:58 |
| 350 | 21 | `agent-gcu-lahore-institution-identity-sr7ev8rad2ej8d2l` | 27 | 10 | 17 | `dee0b43bb287eed0f67695675e1ca8515c16e2d4950ad302d6a611074e8e0be4` | 2026-10-28 20:59:50 |
| 351 | 22 | `agent-gcu-lahore-institution-identity-4kezyzrhczwzbpye` | 27 | 11 | 18 | `f1da6faa17074fb276601e842741ce7c644d76ab298377e8c9759ca6856d3501` | 2026-10-29 02:01:54 |
| 352 | 23 | `agent-gcu-lahore-institution-identity-wnxxgrmeqxhiuqrp` | 27 | 12 | 19 | `b0fe0c3b76450169da669eeb8883d1dac5700fda29c9644853dd66398503e101` | 2026-10-29 05:53:37 |
| 353 | 24 | Not included in bounded lineage section | 27 | Not included | Not included | Not included | Not included |

The trace confirms row IDs 348–353 all have payload identity `Government College University Lahore`, slug `government-college-university-lahore`, status `candidate`, match state `new_candidate`, batch status `validated`, no validation errors, no identity/promotion decisions, and zero open conflicts. The row-level trace confirms active source evidence for all six; the bounded lineage section only returned detailed evidence linkage for rows 348–352. Do not infer row 353's missing identifiers from the sequence.

The source is the active official HEC page `https://www.hec.gov.pk/english/universities/Pages/Punjab/Government-College-University%2C-Lahore.aspx`. Five distinct hashes are available, so those five recorded responses were bytewise different. The production report does not expose raw snapshot bodies or evidence excerpts; compare those through the existing protected operator workflow before deciding whether the changes are only non-substantive page content. Keep raw bodies and row-level output private.

## Existing review-package and identity-decision workflow

Use the existing protected download route `GET /institutes/operations/discovery-packages/{importBatch}/manifest` and `/csv` (authenticated operator) to retrieve the private package, or use the existing `InstituteDiscoveryReviewPackageService::create($batch, $snapshot, $evidenceId)` through the already-authorized operations workflow. For a first review, row 348 / batch 19 is a candidate with known snapshot 8 and evidence 15; the package still must be checked against current private storage. Verify manifest batch/snapshot/evidence IDs, raw snapshot hash, CSV checksum, official URL, candidate identity, excerpt, and `canonical_mutations_performed=false` / `automatic_approval_performed=false`. Do not copy the package or raw response into a public repository.

The package was **not generated/retrieved in this phase** because no authorized authenticated production-session or private-storage retrieval operation was available through the connected tools. No guessed package path was used.

Record an explicit identity decision through `POST /institutes/operations/rows/{importRow}/decision`, with `decision=approved_new` or `approved_match`, a required reason, and `institution_id` for `approved_match`. Then separately evaluate `POST /institutes/operations/rows/{importRow}/promote`; publishing is a separate action at `/institutes/operations/rows/{importRow}/publish`. None of these write actions was invoked. A duplicate finding is not itself an identity decision. Run the existing verification, source-readiness, provenance-readiness, promotion-readiness, final-readiness, deployment-readiness, readiness-contract, and end-to-end gates before any separately authorized promotion.

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
| Six production rows and decision history enumerated | PARTIAL PASS — all row IDs/statuses/decisions traced; row 353 lineage details omitted by bounded report |
| Substantive vs non-substantive response diff | UNVERIFIED — five hashes differ, raw bodies/excerpts unavailable |
| Existing private GCU review package verified | BLOCKED — protected package retrieval not available |
| Explicit identity decision | PENDING human review |
| Pilot qualification for 5–10 sources | BLOCKED |
| No canonical promotion/publication | PASS |
