# ARC-004 — Institutes Directory: Implementation Readiness and MVP Blueprint

**Document ID:** ARC-004  
**Title:** Institutes Directory — Implementation Readiness and MVP Blueprint  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-001 through MOD-026

## 1. Purpose

This document transitions the project from documentation-first governance toward controlled implementation planning.

It defines the implementation boundary, MVP scope, required foundations, deferred capabilities, readiness gates and sequencing.

It does not select a programming language, framework, database, hosting provider, search engine or AI provider.

## 2. Implementation Principle

Implementation must realize the approved domain and governance model rather than simplify it into unrelated technical objects.

The first implementation should be intentionally small, but its boundaries must remain compatible with the long-term model.

## 3. MVP Objective

The first production-capable version should establish a trustworthy, searchable and SEO-friendly institute directory with a strong canonical data foundation.

The MVP is not required to implement every future capability described in the project vision.

## 4. MVP Core Entities

The initial implementation should support, at minimum:

- Institution;
- Institution identity and aliases;
- Institution type/classification;
- Campus;
- Geography;
- Program;
- Program Offering where required;
- Source/evidence metadata;
- Verification/lifecycle state;
- Canonical public representation.

## 5. MVP Core Relationships

The implementation must preserve:

`Institution → Campus`

`Institution → Program`

`Program → Program Offering`

`Campus → Program Offering`

`Campus → Geography`

`Canonical Entity → Source/Evidence/Verification`

These relationships must not be collapsed merely to simplify the first version.

## 6. MVP Public Capabilities

The first public release should target:

- institution listing;
- institution search;
- institution profile;
- campus information;
- geographic discovery;
- program discovery where reliable data exists;
- basic filtering;
- canonical URLs;
- metadata;
- structured-data readiness;
- responsive public experience.

## 7. MVP Data Quality

No institution should be published as an authoritative canonical record without an appropriate identity and source/evidence workflow.

The MVP must support explicit states such as:

- discovered;
- under review;
- verified;
- active;
- historical/inactive where applicable.

Unknown information must remain unknown rather than being fabricated.

## 8. MVP Data Sources

The first implementation should allow governed source attribution.

The architecture must not assume that every future source is manually entered.

Future sources may include:

- authoritative institutional sources;
- regulatory sources;
- trusted secondary sources;
- institution submissions;
- contributor corrections;
- controlled automated discovery.

## 9. MVP Geography

Geography should be represented independently from institution identity.

At minimum, the implementation should support:

- province/territory;
- district;
- city;
- campus address/location.

The broader hierarchy from MOD-005 should remain extensible.

## 10. MVP Search

Initial search should support:

- institution name;
- aliases;
- institution type;
- city/location;
- basic program attributes where available.

Natural-language AI search is deferred.

Search relevance must remain separate from institutional ranking.

## 11. MVP SEO

The first release should establish:

- permanent `/institutes/` root;
- canonical institution URLs;
- stable slugs;
- metadata;
- internal linking;
- crawlable valuable pages;
- sitemap readiness;
- structured-data readiness;
- historical URL strategy.

SEO implementation must follow MOD-020.

## 12. MVP Community Features

The following should not block the initial directory:

- student ratings;
- teacher ratings;
- recommendations;
- written reviews;
- institution responses.

These can be introduced after the canonical directory foundation is stable.

Their governance is already established by MOD-015, MOD-017, MOD-024 and MOD-025.

## 13. MVP Ranking

No public eStudent institutional ranking is required for the first implementation.

Ranking should be introduced only after:

- sufficient governed data;
- sufficient community/external signal coverage where applicable;
- methodology approval;
- anti-manipulation controls;
- reproducibility requirements.

## 14. MVP Recommendations

Personalized recommendations are deferred from the core MVP.

Basic discovery/filtering is sufficient for initial launch.

Future recommendation capability must follow MOD-023.

## 15. MVP Admissions, Fees and Scholarships

These are valuable but should be introduced progressively.

The architecture must permit them without making their absence a failure of the core directory.

Current principles:

- admissions are time-sensitive;
- fees are optional;
- scholarships are optional;
- unknown does not mean negative.

## 16. MVP API

A private/internal access boundary may be needed during implementation.

A broad public API is not required for the first public launch.

Future public APIs must follow MOD-021.

## 17. MVP AI

AI is not a prerequisite for launching the core directory.

Future AI may assist:

- data discovery;
- duplicate detection;
- extraction;
- search;
- summaries;
- recommendations;
- monitoring.

Core identity and canonical data must remain functional without an AI provider.

## 18. MVP Administration

Implementation planning should include controlled operational capabilities for:

- institution creation/review;
- source/evidence management;
- correction;
- lifecycle changes;
- campus management;
- program management;
- verification;
- audit/history.

The exact interface is deferred to UI/UX and implementation specifications.

## 19. Deferred Capabilities

The following should be treated as later phases rather than MVP blockers:

- advanced AI search;
- AI recommendations;
- full community review ecosystem;
- eStudent ranking;
- sophisticated comparison;
- automated source monitoring;
- large-scale crawling;
- mobile applications;
- broad partner APIs;
- advanced maps;
- personalized student accounts;
- predictive analytics.

## 20. Implementation Workstreams

Implementation should be divided into independent workstreams:

### A. Foundation
Repository, configuration, environments, core conventions and deployment baseline.

### B. Canonical Data
Institution, campus, geography, program and source/evidence lifecycle.

### C. Public Directory
Listings, profiles and navigation.

### D. Search and Discovery
Query, filters and relevance.

### E. SEO
Canonical URLs, metadata, structured data, sitemap and indexability.

### F. Operations
Review, correction, verification and audit.

### G. Security
Access control, privacy and abuse protection.

### H. Testing
Data integrity, functional, SEO, security and regression testing.

## 21. Implementation Sequence

Recommended sequence:

1. Repository and environment foundation.
2. Canonical data model implementation.
3. Institution and campus management.
4. Geography.
5. Program and offering layer.
6. Source/evidence/verification lifecycle.
7. Public institution pages.
8. Search and filters.
9. SEO and structured data.
10. Operational review tools.
11. Quality/security testing.
12. Controlled launch.
13. Progressive enrichment.

This sequence minimizes rework.

## 22. Data Import Strategy

Large-scale data import should occur only after the canonical model and validation rules are operational.

Initial data should be treated as a controlled dataset rather than an uncontrolled bulk upload.

Import must support:

- identity matching;
- duplicate detection;
- source attribution;
- validation;
- lifecycle state;
- correction/review.

## 23. Quality Gates

Before public launch, the project should pass gates for:

- identity integrity;
- duplicate control;
- source/provenance;
- lifecycle correctness;
- geographic correctness;
- URL stability;
- SEO correctness;
- search accuracy;
- security;
- privacy;
- performance;
- backup/recovery;
- operational correction.

## 24. MVP Success Definition

MVP success should be evaluated by system quality and user utility, including:

- accurate institution discovery;
- trustworthy canonical records;
- usable search;
- stable URLs;
- useful public pages;
- manageable operations;
- maintainable architecture.

Traffic volume alone is not an adequate architecture-readiness criterion.

## 25. Technology Selection Gate

Technology selection should happen only after:

- domain boundaries are accepted;
- MVP requirements are frozen;
- data lifecycle is understood;
- search/SEO requirements are known;
- security requirements are defined;
- expected operational workload is estimated.

Technology should be selected against requirements, not before them.

## 26. Documentation Gap Review

Before coding, the following implementation-facing documentation should be completed or explicitly waived:

- database/data-model blueprint;
- API contract blueprint;
- UI/UX specification;
- SEO implementation specification;
- security/privacy implementation requirements;
- testing strategy;
- deployment/operations requirements.

## 27. Architecture Freeze Boundary

After these implementation-facing specifications are approved, the project may enter an architecture freeze for the MVP.

The freeze should protect:

- entity meanings;
- identifiers;
- permanent URLs;
- major lifecycle states;
- public API contracts where approved;
- migration compatibility.

It should not prevent normal bug fixes or non-breaking improvements.

## 28. Long-Term Extension Rule

Future capabilities must extend existing bounded concepts rather than replace them.

For example:

- ratings extend community signals;
- rankings consume governed signals;
- recommendations consume governed data;
- AI consumes approved data;
- mobile apps consume approved access layers.

## 29. Explicit Non-Approval

This document does not select:

- language;
- framework;
- database;
- search engine;
- map provider;
- AI provider;
- hosting;
- deployment platform.

## 30. Next Implementation Documents

The immediate next documentation sequence should be:

1. **DB-001 — Canonical Data Model Blueprint**
2. **API-001 — Initial API Contract Blueprint**
3. **UI-001 — Public Directory UX Specification**
4. **SEO-001 — SEO Implementation Specification**
5. **TST-001 — MVP Testing Strategy**

After these are approved, implementation can begin against a controlled technical baseline.

## Approval State

This document is a **final candidate pending merge into `main`**. Once merged, it becomes approved project documentation unless superseded by a governed decision.
