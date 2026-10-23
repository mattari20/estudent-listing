# Phase 2 — Import and publication acceptance matrix

**Scope:** source/evidence → batch/row → validation → identity decision → promotion → explicit publication → public visibility.

## Required safety invariants

1. Import staging never creates canonical records or publishes data.
2. A row can be promoted only from a validated batch with an active source, active evidence, an explicit identity decision, and no unresolved conflict.
3. An approved match must link to the selected canonical identity; it must not create a duplicate.
4. New canonical records remain unpublished after promotion.
5. Publication is a separate explicit operator action requiring active evidence and no open conflicts.
6. Promotion failure is atomic: canonical records and row state must not be partially committed.
7. Reversal is auditable and reversible; existing canonical matches must not be deactivated by reversing an import row.
8. Public listing and profiles expose only active/published records and eligible related records.

## Existing automated coverage

| Acceptance area | Test |
|---|---|
| CSV staging without canonical writes | `InstitutionCleanImportFlowTest` |
| Promotion requires active source, validated batch and evidence | `ImportPromotionSafetyGateTest` |
| Audit persistence failure rolls back canonical and row changes | `InstitutionPromotionAtomicityTest` |
| Duplicate canonical identity blocks new promotion | `DuplicateCanonicalInstitutionBlocksPromotionTest` |
| Duplicate alias blocks new promotion | `DuplicateInstitutionAliasBlocksPromotionTest` |
| Open conflict blocks promotion | `OpenConflictBlocksPromotionTest` |
| Reversal preserves history and does not remove an existing match | `ImportPromotionReversalTest` |
| Public pages hide non-active institutions | `PublicDirectoryPublicationFilterTest` |
| End-to-end readiness report is read-only and fails closed when readiness is incomplete | `InstituteEndToEndReadinessTest` |
| Complete evidence-backed promotion → explicit publication → public listing/profile | `ImportLifecyclePublicationEndToEndTest` |


## Discovery no-change outcome regression

The deterministic discovery agent records an explicit zero-row validated batch when a source snapshot yields no candidates. It also records active source evidence and emits a private CSV/JSON review package whose manifest status is `no_candidates`. This is an observed no-candidate outcome, not proof that the institution has no programmes; the source may have changed layout or temporarily omitted content.

The empty-result path does not create import rows, promote canonical records, publish records, or run migrations. Source retrieval and extraction exceptions remain failures and should be surfaced by the caller/run-history workflow rather than mislabeled as no-change.

## Retry and failure policy

- **Safe to retry after correction:** validation failures, missing evidence capture, and transient source retrieval failures, provided no canonical mutation occurred.
- **Must resolve before retry:** duplicate slug/identity collision, alias collision, unresolved parent, open conflict, or ambiguous institution-versus-campus identity.
- **Never auto-retry as a new canonical entity:** a failed promotion that may have partially succeeded, a row with a prior promotion event, or any candidate whose identity is unresolved. Reconcile the row, event history, and canonical link first.
- **Do not auto-reject:** rows with incomplete evidence, ambiguous identity, missing parent, or temporary retrieval failures. Keep them reviewable with a reason.
- **Publication:** do not publish just because promotion succeeded. Re-check active evidence and conflicts and require the separate operator publication action.

## Required CI and live evidence before Phase 2 exit

- Full Laravel test suite and frontend build pass on the merged commit.
- Test results include the complete lifecycle acceptance test above.
- Deployment revision is verified after relevant application code is released.
- Public smoke checks verify that the synthetic candidate is invisible before publication and visible only after explicit publication.
- Read-only live diagnostics report current canonical and import status counts.
- Any remaining failed rows have a reason category and documented retry/review path.

## Status

This matrix defines the Phase 2 exit gate. The presence of test files alone is not a completion claim. Mark Phase 2 complete only after CI, relevant deployment evidence, and a fresh read-only live audit are all recorded.
