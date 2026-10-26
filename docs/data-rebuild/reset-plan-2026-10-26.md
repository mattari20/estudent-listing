# Hostinger test-data reset inventory and plan

Inventory workflow: https://github.com/mattari20/estudent-listing/actions/runs/40308556364  
Inventory timestamp: 2026-10-26 10:35 UTC  
Target: Hostinger database configured for the Institutes Directory  
Status: **Inventory complete; reset not executed**

## Baseline

- Tables: 50
- Columns: 639
- Foreign-key relationships: 65
- Total rows: 1,243
- Schema fingerprint: `cb73922d02d999527ca67fe1ab556a70cbad49587bb6ef8513537e41a1f27e6a`
- All 50 tables use InnoDB.
- The exact table counts and proposed action are in `reset-inventory-2026-10-26.csv`.

## Proposed reset boundary

Preserve:
- `migrations` (77 rows): migration history must not be erased.
- `users` (2 rows): preserve existing admin/operator access.

Clear values in the other 48 tables, currently 1,164 rows total, including the institution, campus, programme, offering, geography, import staging, source evidence, discovery, and test audit/history records.

This means row values only. No table, column, index, constraint, migration, or application code structure should be removed or recreated.

## Foreign-key-safe deletion order

The dependency graph has 65 foreign-key relationships and no cycles among the proposed reset tables. The following child-before-parent order was computed from the live schema:

1. `accreditations`
2. `admission_intakes`
3. `assertions`
4. `campuses`
5. `change_events`
6. `data_quality_events`
7. `data_reverification_queue`
8. `eligibility_rules`
9. `enrichment_import_rows`
10. `evidence`
11. `external_rankings`
12. `fee_records`
13. `geographies`
14. `identity_match_candidates`
15. `identity_resolution_decisions`
16. `import_batches`
17. `import_conflicts`
18. `import_promotion_decisions`
19. `import_promotion_events`
20. `import_reconciliation_events`
21. `import_rows`
22. `import_schema_profiles`
23. `institute_discovery_run_outcomes`
24. `institution_names`
25. `institutions`
26. `merit_records`
27. `moderation_actions`
28. `monitoring_alerts`
29. `program_offerings`
30. `programs`
31. `provenance_review_events`
32. `ranking_methodologies`
33. `recommendation_policies`
34. `recommendations`
35. `release_records`
36. `reverification_events`
37. `review_ratings`
38. `reviews`
39. `scholarships`
40. `security_audit_events`
41. `selection_outcomes`
42. `source_change_assertion_links`
43. `source_change_observations`
44. `source_monitoring_policies`
45. `source_retrieval_runs`
46. `source_snapshots`
47. `sources`
48. `verification_decisions`

## Mandatory execution checks

A reset implementation must abort before writing unless all of these match the reviewed inventory:
- Expected database name is exactly `u537436044_Institutes`.
- The schema fingerprint is unchanged.
- Exact table set is unchanged.
- All tables remain InnoDB.
- Every table's exact row count matches the reviewed baseline.
- Deletion runs transactionally, child tables before parent tables.
- After deletion, every proposed reset table is empty; `users` remains at 2 rows and `migrations` at 77 rows.
- Recompute the schema fingerprint after the transaction and confirm it is identical.

## Fresh data import

Do not directly import the source-candidate CSV into canonical tables. Use the application's existing import staging/review workflow. Start with 10 institutions, retain source URL and checked date on every record, verify exact campus identity, and only create programme offerings when the official source explicitly ties the programme to that campus.

## Execution status

No production rows have been deleted by the inventory. This document is the verified reset manifest and safety checklist; it is not evidence that a reset has been run.
