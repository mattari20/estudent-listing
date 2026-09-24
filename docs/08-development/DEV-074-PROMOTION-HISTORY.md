# DEV-074 — Promotion History and Canonical Link History
Document ID: DEV-074
Status: Implemented

Import promotion events are append-only operational history. They retain the canonical subject type/id observed at each event, decision references, actors, reasons and transition metadata.

The current import-row canonical link represents only the latest state. Historical events remain available after reversal so operators can reconstruct what happened, when, and under which decision.

Canonical IDs are never recycled. Matching an existing canonical entity is recorded distinctly from creating a new canonical entity.