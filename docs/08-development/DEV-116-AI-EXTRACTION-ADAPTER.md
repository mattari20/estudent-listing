# DEV-116 — AI Extraction Adapter

## Purpose

This batch adds the first provider-backed AI extraction layer without changing the existing governance boundary.

Flow:

Source -> Stored Snapshot -> Evidence -> AI/Deterministic Extraction -> Candidate Import Row -> Duplicate/Conflict Review -> Approval -> Promotion -> Canonical Data.

## Provider contract

The application uses InstituteAiExtractionProvider so the discovery agent is not coupled to one SDK or vendor.

The initial implementation is an OpenAI-compatible HTTP adapter. It uses:

- INSTITUTE_AI_ENABLED
- INSTITUTE_AI_PROVIDER
- INSTITUTE_AI_ENDPOINT
- INSTITUTE_AI_API_KEY
- INSTITUTE_AI_MODEL
- INSTITUTE_AI_TIMEOUT
- INSTITUTE_AI_MAX_SOURCE_CHARS
- INSTITUTE_AI_FALLBACK_DETERMINISTIC

No API key is committed to the repository.

## Stored source snapshots

Source snapshot content is now stored with its SHA-256 hash. This allows a later AI pass to operate on the exact retrieved source version instead of fetching a potentially changed page.

## Candidate provenance

AI candidate payloads include _provenance containing:

- extraction method;
- source ID;
- snapshot ID;
- evidence ID;
- extraction confidence;
- source evidence excerpt.

This metadata does not authorize canonical writes.

## Real CUI Lahore database test

The normal command:

php artisan institutes:discover cui-lahore

retrieves the current official CUI Lahore academic page and writes a real governed discovery batch into the application's database.

With an AI provider configured:

php artisan institutes:discover cui-lahore --ai

creates an ai_extraction batch using the same official source and stored snapshot.

The Operations Hub uses the same discovery service. AI is not enabled by default until provider credentials are configured and tested.

## Safety boundary

The AI adapter may extract and normalize. It may not approve, promote, overwrite, or delete canonical data.

## Next batch

Add broader source profiles and field-specific extraction for admissions, fees, scholarships, accreditation, and campus data while continuing to use the same evidence and approval workflow.
