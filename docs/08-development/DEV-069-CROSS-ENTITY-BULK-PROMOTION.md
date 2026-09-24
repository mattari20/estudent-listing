# DEV-069 — Cross-Entity Bulk Promotion

Status: Implemented

Bulk promotion supports institution identity decisions and enrichment promotion decisions through separate governed records. Enrichment rows require an explicit `approved` promotion decision before bulk promotion. Each row is independently locked and parent-validated. Selection alone never creates canonical data.
