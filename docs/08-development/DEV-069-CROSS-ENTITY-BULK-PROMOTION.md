# DEV-069 — Cross-Entity Bulk Promotion

Status: Implemented

Bulk promotion now supports institution decisions and enrichment rows through a common safety layer. Each row is independently locked, parent-validated and promoted. A failure does not silently promote an invalid dependency.
