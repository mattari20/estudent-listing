# Offering-gap audit artifact validation contract

## Required checks
1. Artifact belongs to the intended workflow run and current main revision.
2. Preflight and reconciliation JSON parse successfully.
3. Both reports declare read-only mode, `mutation_performed=false`, `database_mutations=0`, and `production_write_authorization=false`.
4. Required table/column and register-header checks pass.
5. Register row count matches the expected 36 for the current baseline run; a different count must be explained by an explicitly versioned register, not silently accepted.
6. Reconciliation status remains `ROW_LEVEL_REPORT_CREATED_REVIEW_REQUIRED` until review is complete.
7. Every row has a stable register/programme identifier, a classification, and a blocker list.
8. Report does not include passwords, SSH keys, environment values, or other secrets.
9. Artifact retrieval is recorded; missing/expired artifacts fail closed rather than being inferred from a green CI run.

## Interpretation
A validator pass proves report structure and safety flags, not the correctness of official-source claims or approval to write data. A human row-level evidence review remains required.