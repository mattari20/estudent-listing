# API-001 — Initial API Contract Blueprint

**Document ID:** API-001  
**Title:** Initial API Contract Blueprint  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, ARC-001 through ARC-004, DB-001, MOD-019, MOD-021, MOD-023, MOD-024  
**Technology Status:** Technology-neutral

## 1. Purpose

Define the first stable access boundaries for the Institutes Directory without selecting an implementation technology.

The API is an access layer. It is not a second canonical data store.

## 2. Core Principles

- Canonical data remains the source of truth.
- Stable identifiers are independent of URLs, slugs and API representations.
- Read contracts should be designed for backward compatibility.
- Unknown and optional data retain their governed meaning.
- Search, ranking and recommendation are distinct operations.
- Sensitive/internal data must never be exposed merely because it exists internally.
- AI and future applications consume governed access layers rather than bypassing governance.

## 3. Initial Resource Families

The API contract should conceptually support:

- institutions;
- campuses;
- geography;
- programs;
- program offerings;
- admissions;
- eligibility;
- merit/selection;
- fees;
- scholarships;
- accreditation/recognition;
- sources/provenance where authorized;
- search/discovery.

## 4. Institution Access

Institution access should support:

- lookup by stable identifier;
- canonical public representation;
- current/historical state where authorized;
- aliases;
- type/classification;
- campuses;
- programs;
- source/trust indicators appropriate for public use.

An API consumer must not have to depend on the institution slug as the permanent identity.

## 5. Campus Access

Campus access should support:

- stable campus identity;
- parent institution;
- campus name/type;
- location;
- lifecycle;
- available programs/offers where public.

Campus data must not be silently merged into institution identity.

## 6. Geography Access

Geographic access should support governed geography and location-based discovery.

The API should be able to represent:

- administrative hierarchy;
- city/locality;
- campus location;
- geographic relationships.

Nearby discovery remains a geographic operation, not a quality or ranking operation.

## 7. Program and Offering Access

Program and Program Offering must remain separate in API representations.

Program identifies the enduring academic concept.

Offering represents contextual availability such as:

- campus;
- intake;
- mode;
- duration;
- admission cycle;
- status.

## 8. Search Contract

Search is a discovery operation.

The search contract should support, as appropriate:

- text query;
- institution aliases;
- geography;
- institution type;
- program attributes;
- admission-related intent;
- filters;
- pagination;
- current/historical scope.

Search ordering must not be described as an institutional quality ranking.

## 9. Search Result Semantics

A search result should distinguish:

- matched entity;
- match context;
- relevant public fields;
- lifecycle visibility;
- trust/provenance indicators where appropriate.

Search may return derived relevance metadata, but such metadata is not canonical institutional fact.

## 10. Recommendation Boundary

Recommendations must use a distinct contract from ordinary search.

A future recommendation request may include explicit user preferences such as:

- location;
- discipline;
- education level;
- delivery mode;
- timing;
- voluntarily provided budget.

The recommendation response should be able to explain material reasons and identify important data limitations.

## 11. Ranking Boundary

Ranking must remain separate from:

- entity retrieval;
- search relevance;
- recommendation.

Future ranking responses must expose methodology/version context where appropriate.

No ranking formula is approved by this document.

## 12. Admissions

Admission access should preserve temporal context.

Where public, responses should identify:

- intake/cycle;
- status;
- dates;
- program/offering;
- eligibility context;
- application destination;
- source/freshness information where appropriate.

Current and historical admission records must not be conflated.

## 13. Fees

Fees are optional.

API consumers must be able to distinguish:

- known;
- partially known;
- unknown;
- not published;
- not applicable;
- awaiting verification;
- conflicting;
- historical;
- stale.

Missing fees must not cause an otherwise valid institution/program response to fail.

## 14. Scholarships

Scholarships are optional and separate from fees.

Responses should preserve:

- scholarship scope;
- eligibility;
- benefit;
- application period;
- lifecycle;
- source/trust state where appropriate.

Unknown scholarship data must not be interpreted as no scholarship.

## 15. Accreditation and Recognition

The API must distinguish:

- recognition;
- accreditation;
- affiliation;
- authorization;
- licensing;
- other regulatory status.

Directory verification must not be represented as government or regulator recognition.

## 16. Provenance and Trust

Public API responses should expose only appropriate provenance/trust indicators.

Internal evidence, moderation notes, private contributor information and security-sensitive metadata must remain protected.

Public trust labels may include concepts such as:

- verified;
- current;
- historical;
- community reported;
- unavailable.

## 17. Versioning

API contracts must be versionable.

Changes should be classified as:

- additive/non-breaking;
- compatible correction;
- deprecation;
- breaking change.

Breaking changes require documented migration and compatibility planning.

The exact versioning mechanism is intentionally deferred.

## 18. Pagination

Collection responses must support controlled pagination.

The contract should avoid assumptions that a collection is permanently small.

Pagination behavior must remain deterministic enough for clients to safely navigate changing datasets.

## 19. Filtering

Filters should map to governed fields.

Examples:

- institution type;
- geography;
- education level;
- discipline;
- delivery mode;
- current/historical state.

A filter must not silently convert missing data into a negative condition.

## 20. Error Semantics

API errors should distinguish conceptually between:

- invalid request;
- unauthorized/forbidden access;
- resource not found;
- unavailable/temporarily unavailable;
- conflict;
- validation failure;
- unsupported operation.

The exact status-code implementation is deferred.

## 21. Write Access

Public write access is not part of the initial API requirement.

Future submissions/corrections must pass:

- validation;
- abuse controls;
- moderation where applicable;
- provenance;
- verification workflow.

Submitting a claim must not automatically make it canonical.

## 22. Bulk Access

Bulk export may become important for:

- research;
- partners;
- internal processing;
- AI/data pipelines.

Bulk access must preserve governance, provenance, privacy and licensing constraints.

It should not bypass canonical validation.

## 23. AI and Agent Access

Future AI agents may consume APIs for:

- search;
- institution lookup;
- program discovery;
- admissions discovery;
- comparison;
- recommendation.

AI agents must receive governed representations and should not gain uncontrolled access to private/internal data.

## 24. Security and Privacy

API access must enforce:

- least privilege;
- authentication where required;
- authorization;
- rate/resource controls;
- privacy boundaries;
- auditability;
- abuse prevention.

No specific authentication or security technology is selected here.

## 25. Caching and Freshness

Caching may be used as a derived access mechanism.

Cached information must have a governed freshness strategy appropriate to the underlying data.

Highly time-sensitive information such as admissions should not be treated like static identity data.

## 26. API and SEO

SEO pages may consume API/access-layer data, but canonical SEO content must remain grounded in canonical records.

API representations must not create uncontrolled duplicate public URLs.

## 27. API and Search

Search infrastructure may consume canonical data and expose derived relevance.

Search indexes must be rebuildable from canonical data.

Search availability must not alter canonical identity.

## 28. API and Rankings/Recommendations

Ranking and recommendation services may consume API-accessible governed data.

They must retain their distinct methodology and semantics.

A recommendation response must not be presented as a ranking.

## 29. Observability

Future API implementation should support operational visibility for:

- availability;
- latency;
- error rates;
- unusual traffic;
- data freshness failures;
- access abuse.

Exact observability tooling is deferred.

## 30. Documentation

Every public API contract should document:

- purpose;
- resource meaning;
- fields;
- lifecycle semantics;
- provenance/trust semantics;
- version;
- compatibility;
- examples;
- limitations;
- deprecation status.

## 31. Initial MVP API Boundary

The first implementation should keep the public API surface intentionally small.

Initial priority:

1. institution lookup;
2. campus lookup;
3. program lookup;
4. public search;
5. geography/filter discovery.

Admissions, fees, scholarships and advanced recommendation/ranking APIs may be added progressively.

## 32. Explicit Non-Approval

This document does not select:

- API framework;
- transport protocol implementation;
- authentication provider;
- API gateway;
- database;
- search engine;
- caching system;
- hosting;
- programming language.

## 33. Next Documents

- UI-001 — Public Directory UX Specification
- SEO-001 — SEO Implementation Specification
- TST-001 — MVP Testing Strategy

## Approval State

This document is a **final candidate pending merge into `main`**.
