# Geography integrity review manifest

Use one manifest row per geography finding from the read-only integrity report. Do not repair or deactivate records automatically based only on a finding.

| Field | Description |
|---|---|
| Check timestamp | When the integrity report ran |
| Deployment revision | Observed live revision, separately verified |
| Geography ID | Stable canonical ID |
| Name and slug | Current stored values |
| Type and status | Current geography classification and lifecycle state |
| Parent ID/type/status | Parent relationship details |
| Finding code | Exact integrity finding code |
| Campus references | Active campus IDs depending on this geography |
| Import provenance | Batch/row/evidence IDs, if known |
| Source confirmation | Official source and last verified date |
| Proposed correction | Rename, parent correction, duplicate review, or hold |
| Impact | Affected campuses, filters, URLs, and downstream records |
| Decision owner | Reviewer responsible for the decision |
| Verification query | Read-only post-change condition |
| Authorization | Required approval before any live write |

## Review sequence

1. Re-run the read-only report and capture the exact timestamp/revision.
2. Review the finding and parent chain, including active/inactive status.
3. Check whether campuses depend on the geography.
4. Compare official administrative boundaries and names; do not infer the correct parent from a similar name.
5. Identify duplicates by parent plus slug, then review the canonical identity manually.
6. Document the proposed action and expected impact.
7. Make changes only through an authorized workflow; re-run integrity checks afterward.

Unknown, ambiguous, or source-conflicted rows remain on hold. This manifest does not authorize geography seeding, parent reassignment, deletion, activation, or any other database write.
