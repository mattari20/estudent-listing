# DEV-077 — Conflict Review Workflow
Document ID: DEV-077
Status: Implemented

Authenticated operators can inspect conflict context and resolve an individual conflict with a mandatory reason. Promotion remains blocked while any conflict for the row is open.

Conflict resolution does not itself approve identity. Identity decisions and conflict decisions remain separate. No fuzzy automatic merge or automatic canonical winner is introduced.