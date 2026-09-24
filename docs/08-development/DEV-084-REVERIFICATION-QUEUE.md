# DEV-084 — Automated Reverification Queue
Document ID: DEV-084
Status: Implemented

Introduces an operator-facing queue foundation for evidence that requires re-verification. Queue entries preserve source/evidence references, reason, priority, timestamps and resolution state.

The queue does not automatically change canonical data. Re-verification remains a governed human/operational decision.