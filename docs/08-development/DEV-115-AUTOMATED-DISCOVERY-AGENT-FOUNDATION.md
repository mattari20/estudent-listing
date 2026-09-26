# DEV-115 — Automated Discovery Agent Foundation

## Purpose

This batch begins the operational implementation of the documented automated-discovery model.

The agent:

1. resolves an existing canonical parent institution;
2. retrieves a configured official institutional source;
3. records a source snapshot and evidence;
4. extracts candidate undergraduate programmes;
5. creates governed import rows;
6. runs deterministic duplicate detection;
7. records discovery assertions;
8. leaves every candidate non-canonical until governed approval and promotion.

## Initial test profile

The first live test profile is COMSATS University Islamabad — Lahore Campus.

Source:

https://lahore.comsats.edu.pk/academics.aspx

The current official source lists undergraduate programmes including Architecture, Interior Design, Design, Economics with Data Science, Economics, English, Media and Communication Studies, Psychology, Business Administration, Accounting and Finance, Business Data Analytics, Chemical Engineering, Electrical Engineering, Computer Engineering, Computer Science, Software Engineering, Artificial Intelligence, Chemistry, Mathematics with Data Science, Physics, Electronics and Computing, and Statistics with Data Science.

Current source content is rechecked at runtime. The agent does not assume that this list remains unchanged.

## Governance boundary

The agent is intentionally not permitted to write directly to canonical programs or institutions.

The flow is:

Source -> Snapshot -> Evidence -> Discovery Assertion -> Candidate Import Row -> Duplicate/Conflict Review -> Approval -> Existing Promotion Workflow -> Canonical Data.

AI extraction can replace or augment the initial deterministic extractor later without changing this governance boundary.

## Live test

After deployment and migration, run:

php artisan institutes:discover cui-lahore

The command creates a real discovery batch from the official CUI page. Review it from the existing Operations Hub. Candidate rows should appear as waiting or duplicate according to current canonical data.

## Next batch

The next implementation batch adds an AI extraction adapter behind the same candidate contract. It consumes source evidence, returns structured proposals with provenance, and never bypasses the review/promotion boundary.
