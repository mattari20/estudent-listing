# Phase 2 — Automated Discovery Review Packages

## Scope

Generate a private CSV candidate list and JSON manifest for each successful deterministic or AI discovery batch. Candidate records remain non-canonical until the existing governed review and promotion workflow approves them.

## Command

Create or reuse a package for the newest discovery batch with:

    php artisan institutes:discovery-review-package

To target a specific batch:

    php artisan institutes:discovery-review-package 123

The package is written to Laravel's private local storage under:

    storage/app/private/institute-discovery-review-packages/snapshot-{snapshot_id}/

The CSV includes candidate identity, entity and parent, official source URL, snapshot/evidence identifiers, observation time, extraction method/confidence, duplicate state, validation errors and review status. The JSON manifest records counts and explicitly states that no canonical mutation or automatic approval occurred.

## Idempotency and safety

- One package path is used per source snapshot; repeat requests reuse the existing files.
- The command accepts only automated discovery and AI extraction batches.
- Snapshot provenance must exist and match the batch's source.
- Files are not placed under public/ and are not exposed through a public route.
- The package is a review artifact, not an approval or promotion instruction.

## Limitations / next steps

This initial implementation packages successful discovery batches. Scheduled empty/no-change outcomes, protected admin retrieval, package retention and full live Hostinger verification remain follow-up acceptance criteria. CI must pass before merge/deployment. Do not run migrations, canonical promotions, or production data repairs as part of this phase.
