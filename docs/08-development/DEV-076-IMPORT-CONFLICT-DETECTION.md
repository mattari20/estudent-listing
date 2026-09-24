# DEV-076 — Import Conflict Detection
Document ID: DEV-076
Status: Implemented

Introduces durable conflict records for material import ambiguity. Initial checks cover duplicate normalized institution identity within the same batch and multiple canonical institutions sharing a normalized name.

Open conflicts set the row match state to `conflict` and block canonical promotion. The system never silently selects a winner. Conflict records are separate from identity-match candidates and preserve their own severity, status, details and resolution history.