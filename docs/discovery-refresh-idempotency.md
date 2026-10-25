# Discovery refresh and idempotency contract

## Default scheduled/manual discovery behavior

The normal discovery path is idempotent for the same configured profile, official source content hash, extraction method, and deterministic extractor version.

- The agent reuses the existing source snapshot when the retrieved content hash is unchanged.
- If an immutable batch already exists for that snapshot/content and extractor version, it returns that batch and its review package rather than creating duplicate evidence, assertions, import rows, or a second package.
- A changed source hash creates a new snapshot and new candidate batch.
- A changed extractor version may create a new batch from unchanged source content. Existing batches and row history remain intact.
- Duplicate detection continues to check canonical records and other import batches.

The normal path never approves, promotes, publishes, or overwrites canonical data.

## Explicit refresh of a populated batch

Command:

    php artisan institutes:refresh-discovery PROFILE --supersedes-batch-id=ID --confirm-new-batch

Replace PROFILE with an existing configured profile and ID with a prior discovery batch belonging to that same profile and source. The command is deterministic and does not opt into paid AI extraction.

Safety requirements:
- --confirm-new-batch is mandatory.
- A valid prior batch ID is mandatory.
- The service validates that the prior batch is a discovery batch for the selected profile/source and extraction method.
- A refresh creates a new immutable batch with refresh_of_batch_id=ID provenance. It does not delete or overwrite the prior batch.
- During this explicit refresh only, duplicate detection excludes the one specifically superseded batch. Canonical matches and other historical batches remain duplicate blockers.
- If the source or prior batch cannot be validated, the command fails closed.
- Refresh output is still a candidate review package. It does not authorize promotion.

## Review and rollback

No destructive rollback is needed because prior batches remain in place. To abandon a refresh, leave it unapproved and retain it for audit; do not delete history. Any later canonical data mutation requires its own reviewed row-level plan and explicit owner authorization.

## Validation requirements

Automated tests cover unchanged-content reuse, one-batch/one-package behavior, explicit refresh producing a new batch, preservation of the original batch, and duplicate checks that exclude only the named superseded batch. CI results must be recorded per commit; production scheduler behavior is verified separately.
