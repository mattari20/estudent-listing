# System Architecture

## Application boundary
The Institutes Directory is a separate Laravel application deployed under `https://estudent.pk/institutes/`. The main eStudent site remains a separate system. The directory must not depend on WordPress for its core data or runtime.

## Current components observed in the repository
- Public institution listing and institution detail routes/controllers.
- Search/filter presentation and pagination.
- Canonical institution and related academic/geographic entities.
- Import batches and rows with validation and workflow statuses.
- Promotion/publication controls and source-evidence requirements.
- Read-only data-gap, import-row, duplicate and canonical-collision diagnostics.
- Authenticated operations/review workflows.
- GitHub Actions CI and public endpoint monitoring workflows.

The exact current implementation is defined by the code on the reviewed branch; this document is a conceptual map, not a claim that every planned subsystem is complete.

## Intended modular boundaries
1. **Directory and discovery:** listing, search, filters, detail pages and location discovery.
2. **Canonical institution registry:** institution identity, aliases, institution type, parent/child relationships and canonical slugs.
3. **Campus and geography:** campus/sub-institution relationships and structured geography; location coordinates and map integration.
4. **Academic catalogue:** programs, offerings, eligibility and related academic details.
5. **Admissions and student information:** admission cycles, deadlines, fees, scholarships, facilities, contact information and other supported attributes.
6. **Import and source provenance:** source registry, snapshots, extracted evidence, import batches/rows and validation.
7. **Review and publication governance:** identity decisions, conflicts, approval queues, promotion and publication.
8. **Community trust:** ratings, written reviews/comments, moderation, anti-abuse, verification and reporting.
9. **Reputation/ranking:** future explainable composite rating; separate from raw user submissions and externally sourced ratings.
10. **AI research agent:** future controlled source discovery, extraction, entity matching, change detection and candidate submission for human review.
11. **Operations and observability:** diagnostics, CI, deployment checks, audit events, alerts and recovery procedures.

## Integration principles
- Maps/location provider integration must be replaceable and must not make coordinates or provider responses the sole identity of an institution.
- AI components submit proposed changes with source provenance and confidence; they do not silently overwrite canonical data.
- Community contributions are separate from official source evidence.
- External ratings/rankings retain their source, date, scale and methodology and must not be represented as directly comparable without normalization.
- Keep public queries bounded and indexed; use queued/background processing for imports, source refresh and AI research as scale requires.

## Required architecture verification
Before declaring the architecture stable, map each boundary to actual routes, controllers, services, models, migrations, policies, jobs and tests. Document current versus planned components and identify coupling that should be reduced.
