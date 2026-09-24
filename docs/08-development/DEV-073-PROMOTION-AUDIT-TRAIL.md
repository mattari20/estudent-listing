# DEV-073 — Import Promotion Audit Trail
Document ID: DEV-073
Status: Implemented

This batch adds durable import-specific audit history for promotion attempts and outcomes.

Events can record approval, start, success, failure, rejection, reversal and supersession-related transitions. Each event links the import row and batch, entity type, transition state, canonical subject, actor, decision reference, reason and metadata.

This operational history complements Source/Evidence/Assertion provenance, verification decisions, security audit events and canonical change events. It does not replace them.

Failed attempts must preserve a reason in audit metadata rather than disappearing into aggregate counters.