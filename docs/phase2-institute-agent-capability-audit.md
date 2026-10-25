# Institute discovery agent capability audit — Phase 2

Audit basis: current repository source on the PR base revision, especially
`InstituteDiscoveryAgentService`, `InstituteDiscoveryCandidateExtractor`,
`config/institute_agent.php`, `RunInstituteDiscoveryAgent`, and the Step 3
offering-gap preflight. This is a code-capability audit, not a claim that the
live database has been reconciled.

## Current capabilities

| Capability | Current state | Practical consequence |
|---|---|---|
| Fetch configured official source | Implemented through the safe source retriever, with body-size limit | Only configured profiles/sources are covered |
| Snapshot and content hash | Implemented | Evidence source content can be tied to a stable snapshot |
| Deterministic programme/campus candidates | Implemented | Extractor currently supports only `program` and `campus` |
| Candidate quality and duplicate checks | Partially implemented | Candidate rows remain non-canonical; relationship-level checks are separate |
| Immutable import batch and review package | Implemented | History and private review artifacts are retained |
| Idempotent unchanged-source handling | Implemented | Repeated unchanged content reuses the batch/package; explicit refresh preserves lineage |
| Source monitoring profiles | Implemented but narrow | GCU Lahore is the active pilot; COMSATS profiles are paused |
| AI extraction | Optional adapter exists | Scheduled profile has `use_ai=false`; no paid AI call is needed for this work |
| Institution discovery | Not implemented as a general entity extractor | Agent cannot autonomously enumerate institutions nationwide |
| Programme-to-campus offering reconciliation | Not part of the discovery entity model | The 36 Step 3 rows need a dedicated relationship-aware reconciliation pass |
| Live source truth verification | Incomplete | URL syntax and a candidate excerpt do not establish that the page is current or proves the relationship |
| Automatic canonical repair | Intentionally absent | No approval, promotion, publication, deletion, or offering insert should occur in discovery |

## Recommended integration

Keep one governed discovery architecture. Do not create a parallel autonomous agent. Keep source retrieval, snapshots, evidence, candidate extraction, provenance, duplicate prevention, review packages, and monitoring policy shared.

Add a separate **relationship reconciliation mode** because an offering is a relationship between a canonical programme and campus, with a delivery mode and status. It must compare the live canonical rows and the Step 3 register without treating a programme candidate as an offering.

The new `institutes:offering-gap-reconcile` command implements the first read-only pass. Its output distinguishes an already-active relationship, inactive relationship, delivery-mode mismatch, another-campus relationship, blocked evidence/identity, and a missing-relationship candidate. A later source-review stage must fetch and retain official page evidence before producing an actionable row-level repair plan.

## Acceptance gates before calling the agent complete

1. The read-only command returns exactly 36 register rows, unique row/program IDs as expected, and a disposition for every row.
2. Each proposed relationship has current official programme evidence and campus/delivery evidence, captured with retrieval timestamp and content hash.
3. All existing relationships and duplicates are reconciled before any insert is proposed.
4. A second read-only preflight confirms the exact current row state and the proposed change set.
5. Production repair remains a separately authorized operation. No migration, promotion, publication, deletion, or canonical write is implied by this audit or by a passing test.
6. Only after the 36-row workflow is proven should the same governed architecture expand to institution discovery and nationwide source monitoring.

## Known live-verification limitation

A repository change or passing CI test does not itself run this command against Hostinger. The live report must be produced by the approved read-only workflow and attached with its workflow run, deployed revision, and report artifact. Until that exists, no count or resolution claim should be made for the current 36 live gaps.
