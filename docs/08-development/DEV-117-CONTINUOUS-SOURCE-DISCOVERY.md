# DEV-117 — Continuous Source Monitoring to Governed Discovery

## Purpose

This batch connects the existing source-monitoring system to the automated institute discovery agent.

The resulting loop is:

Official Source -> Monitoring Snapshot -> Change Detection -> Discovery Extraction -> Evidence -> Candidate Batch -> Duplicate/Conflict Review -> Approval -> Promotion.

## What happens automatically

1. A discovery profile registers its source monitoring policy.
2. The existing scheduled source monitor retrieves the source.
3. The exact retrieved content is stored in the source snapshot.
4. A content-hash comparison detects a change.
5. If the source is configured for automatic discovery, the current snapshot is parsed without fetching the page a second time.
6. A new governed discovery batch is created.
7. Existing duplicate detection determines candidate versus duplicate rows.
8. Evidence, snapshot lineage, extraction method, and confidence are retained.
9. No canonical record is automatically overwritten or published.

## Current live profile

CUI Lahore academic programmes:

https://lahore.comsats.edu.pk/academics.aspx

Monitoring is enabled at weekly cadence. The application's hourly scheduler checks which policies are due; the policy itself controls its next run.

AI is deliberately disabled for automatic monitoring by default. This prevents an unconfigured provider from becoming a production dependency. The deterministic extractor can safely create candidate batches first.

## Database requirement

Run the new migrations after deployment:

php artisan migrate

Then run the normal discovery command once:

php artisan institutes:discover cui-lahore

That creates the first snapshot and automatically registers the monitoring policy.

Subsequent scheduled monitoring can create new candidate batches when the official source content changes.

## AI input

No user input is required to use deterministic continuous discovery.

If AI extraction is later enabled, the only external secret required is the provider API key in the deployment environment. It must never be committed to Git.

## Governance boundary

Monitoring can detect and propose changes. It cannot approve, promote, overwrite, or delete canonical institute data.

## Next batch

Expand the same framework to additional official source profiles and entity-specific extractors for admissions, fees, scholarships, accreditation, campuses, and institutional facts.
