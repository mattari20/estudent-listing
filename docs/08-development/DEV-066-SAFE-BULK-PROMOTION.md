# DEV-066 — Safe Bulk Promotion

Status: Implemented

Operators can submit up to 100 selected rows for bulk promotion. Each row is independently locked and checked; only rows with an explicit approved_new or approved_match decision can be promoted. Unapproved selections are skipped and canonical data is never promoted merely because a row was selected.
