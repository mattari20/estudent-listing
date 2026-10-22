# Data Architecture and Lifecycle

## Import-to-publication lifecycle
1. Register or select an allowed source.
2. Retrieve source content and preserve a timestamped snapshot when appropriate.
3. Extract proposed facts with source references and, where useful, confidence.
4. Create an import batch and staged rows.
5. Validate required fields, formats, relationships and allowed values.
6. Match rows against canonical names, official names, short names, aliases, slugs and known identifiers.
7. Flag duplicates, conflicts, missing evidence and incomplete data.
8. Record an explicit review decision such as approved new entity, approved match, rejection or request for more information.
9. Promote only when the configured validation, evidence, identity and conflict gates pass.
10. Publish only through the explicit publication workflow.
11. Monitor freshness and allow corrections through auditable changes.

## Row categories must remain distinct
- **Candidate:** unresolved or awaiting review; not public by default.
- **Invalid/incomplete:** failed validation; retain actionable errors and allow correction/reprocessing.
- **Duplicate candidate:** may refer to an existing canonical entity; review and map it instead of creating a second entity.
- **Promoted:** linked to a canonical entity; this alone must not be assumed to mean publicly approved.
- **Rejected:** review outcome; retain reason and history according to retention policy.
- **Published/active canonical entity:** visible only after publication and relationship rules pass.
- **Conflict:** unresolved identity or fact disagreement; do not silently choose a value.

## Safe cleanup procedure
1. Generate a read-only inventory grouped by batch, row status, validation reason, duplicate candidate and canonical link.
2. Identify whether each row is a disposable test fixture, legitimate candidate, duplicate of a canonical record, invalid row, rejected row or historical audit record.
3. Check foreign-key/dependency references and whether the row is needed for auditability.
4. Produce a dry-run plan with exact row IDs, proposed action, reason, expected count and dependencies.
5. Review the plan before any destructive or state-changing operation.
6. Prefer reversible state transitions or explicit archive/rejection where appropriate over hard deletion.
7. Execute only an approved, bounded action and record an audit event.
8. Re-run diagnostics and tests to prove that canonical records, relationships and public pages were not damaged.

## Source evidence and AI
- Every externally sourced proposed fact should retain a source URL, retrieval date and evidence excerpt or snapshot as supported by the implementation.
- Evidence must not be invented to make a row promotable.
- An AI agent may research, extract, match and recommend; it submits proposals to the review queue.
- AI cannot silently publish or overwrite canonical records.
- Refresh workflows should detect changed or stale sources and propose reviewed updates rather than replacing data without traceability.

## Community data
Ratings, comments and visitor reviews have separate provenance and moderation states from official facts. A visitor statement must not be stored as an official institutional claim. External rating observations are stored with source, date and original scale.

## Data governance questions to resolve before launch
- Retention periods for rejected rows, snapshots, comments and audit events.
- User deletion/privacy requirements and moderation escalation.
- Coordinate correction and geocoding provenance.
- Policy for stale external ratings and institution responses.
