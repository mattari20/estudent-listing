# Import lifecycle state matrix

This matrix is an operator reference for reconciling import counts. Exact state names depend on the table and workflow; map each value to the actual deployed schema rather than assuming that similar labels mean the same thing.

| Lifecycle concept | What it means | Evidence to inspect | Do not infer |
|---|---|---|---|
| Imported | Row was accepted into an import batch | Batch and row IDs, created timestamp | That the row is valid |
| Validated | Required fields and shape passed validation | Validation result and rule version | That identity is resolved |
| Candidate | Row remains a proposed record | Candidate status and normalized metadata | That it is approved |
| Matched | A possible canonical identity was found | Match candidates and confidence/rationale | That the match is correct |
| Review | Human decision is required | Review event and assigned reviewer | That review is complete |
| Approved | A decision authorizes the next eligible step | Decision ID, actor, timestamp, scope | That promotion succeeded |
| Eligible | All required gates currently pass | Parent, source evidence, geography, schema checks | That an attempt occurred |
| Attempted | A promotion operation started | Attempt/event ID, start/end, outcome | That a canonical row exists |
| Promoted | Operation reports success | Canonical entity ID and post-write verification | That public visibility is enabled |
| Skipped | Operation intentionally did not proceed | Controlled skip reason | That it failed |
| Failed | Operation ended unsuccessfully | Sanitized exception class and safe constraint hint | That retry is safe |
| Publicly visible | Public listing filters include the canonical record | Read-only public endpoint/profile verification | That the promotion event alone proves visibility |

## Reconciliation procedure

1. Record the check timestamp and deployed revision.
2. Query each lifecycle concept independently using the actual state column and event source.
3. Join by stable batch/row/decision/attempt IDs where available; avoid matching on name alone.
4. Reconcile canonical entity IDs against the reported successful promotions.
5. Record skipped and failed outcomes separately.
6. Verify public visibility independently from canonical existence.
7. Keep unknown outcomes blocked and produce a row-level review manifest.

The matrix is diagnostic documentation only. It does not authorize retries, writes, activation, or publication.
