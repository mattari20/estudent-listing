# DEV-075 — Safe Promotion Reversal and Supersession
Document ID: DEV-075
Status: Implemented

Rules:
- Reversal is authenticated and operator-only.
- A reason is mandatory.
- A promoted import row becomes reversed; its history remains intact.
- Existing canonical matches are never deleted or rolled back because of one import row.
- A newly created institution may be transitioned to superseded only when dependency checks confirm that no dependent canonical records exist.
- If dependent records exist, deletion is blocked and a governed lifecycle workflow is required.
- Enrichment reversal does not hard-delete canonical records in this batch; the import row is reversed while canonical history is preserved.
- IDs and permanent URLs are never recycled.
- Every reversal creates an audit event.

This mechanism is a rollback safeguard, not a general-purpose canonical deletion tool.