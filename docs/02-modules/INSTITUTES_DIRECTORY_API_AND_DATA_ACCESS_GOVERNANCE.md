# MOD-021 — Institutes Directory: API and Data Access Governance

**Document ID:** MOD-021  
**Title:** Institutes Directory — API and Data Access Governance  
**Version:** 1.0  
**Status:** Approved — implemented read-only API v1 boundary  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-24  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-006, MOD-018, MOD-019, MOD-020  
**Related Documents:** All canonical entity and module governance documents  
**Next Documents:** Ranking and Recommendation Methodology; Security and Privacy Governance

---

## 1. Purpose

This document defines governance principles for future APIs and programmatic access to Institutes Directory data.

APIs are access and integration layers. They must not become an uncontrolled second source of truth.

---

## 2. Core Principle

The canonical data model and governance documents define meaning.

APIs expose approved representations of that meaning.

API consumers must not be allowed to redefine canonical entity identity, verification state or provenance merely through API usage.

---

## 3. API Consumer Categories

Future access may serve:

- eStudent web experiences;
- mobile applications;
- internal tools;
- partner integrations;
- approved third-party applications;
- research or data-access consumers where permitted;
- AI agents and assistant experiences.

Different consumers may receive different scopes.

---

## 4. Canonical vs Derived Data

APIs may expose:

- canonical entity data;
- verified source information;
- historical information;
- community signals;
- external signals;
- search results;
- derived recommendations;
- AI-generated representations.

The response should preserve the distinction between these categories.

---

## 5. Entity Identity

Stable identifiers should remain independent from:

- URLs;
- names;
- slugs;
- search results;
- API clients;
- external identifiers.

Identifiers should not be casually recycled.

---

## 6. Lifecycle States

API representations should preserve meaningful lifecycle states such as:

- active;
- planned;
- suspended;
- closed;
- historical;
- superseded;
- under review.

Consumers should not infer “active” merely because an object exists.

---

## 7. Verification and Provenance

Where appropriate, API responses should expose or preserve:

- verification state;
- source/provenance indicators;
- freshness;
- effective dates;
- historical state;
- conflict indicators.

Sensitive internal evidence may remain private.

---

## 8. Temporal Data

APIs must support the conceptual distinction between:

- current;
- historical;
- future/planned;
- expired;
- superseded.

Time-specific information such as admissions, fees, scholarships and rankings should retain temporal context.

---

## 9. Search API

Future search APIs may expose:

- query interpretation;
- matching entities;
- relevance;
- filters;
- pagination;
- current/historical states;
- geographic discovery.

Search results are derived representations and must not become canonical entities.

---

## 10. Geographic Access

Geographic APIs may expose:

- administrative areas;
- campus locations;
- nearby discovery;
- geographic relationships.

Exact location data must follow geography governance and applicable privacy/security controls.

---

## 11. Program and Offering Access

Program APIs should preserve:

**Program identity ≠ Program Offering**

Offering-specific attributes such as campus, intake, mode, dates and availability must not be incorrectly promoted to permanent program identity.

---

## 12. Admissions and Eligibility

Admission APIs should preserve:

- intake;
- opening/closing dates;
- eligibility;
- application destination;
- entry-test information;
- current/historical status.

An expired admission must remain distinguishable from a current admission.

---

## 13. Financial Information

Fee data is optional.

API consumers must be able to distinguish:

- known;
- partially known;
- unknown;
- not published;
- historical;
- stale;
- conflicting.

Unknown financial information must not be serialized as zero or free.

---

## 14. Scholarships and Financial Aid

Scholarship data is optional and non-blocking.

APIs should distinguish:

- scholarship exists;
- scholarship eligibility;
- scholarship application period;
- information unavailable.

Absence of data must not be interpreted as absence of aid.

---

## 15. Accreditation and Recognition

API representations must distinguish:

- recognition;
- accreditation;
- affiliation;
- authorization;
- licensing;
- regulatory status.

Scope and temporal validity must remain available where relevant.

---

## 16. Community Recommendations and Ratings

MOD-017 community signals may be exposed through approved API representations.

They should retain appropriate context such as:

- contributor category;
- verification/provenance;
- period;
- moderation state;
- aggregation context.

Community opinion must not be represented as official institutional fact.

---

## 17. External Rankings

External ranking information should retain:

- publisher;
- ranking system;
- edition/year;
- scope;
- rank/score/band;
- source context.

External rankings must not silently become eStudent rankings.

---

## 18. Search vs Ranking

API contracts must distinguish:

**Search relevance**

from

**Institution/program ranking**

from

**Recommendation**

These are different derived functions.

---

## 19. AI and Agent Access

Future AI agents may consume directory APIs for:

- question answering;
- search;
- comparison;
- recommendations;
- monitoring;
- educational assistants.

AI consumers should receive enough provenance/state information to avoid turning uncertain information into asserted fact.

---

## 20. Write Access

Public APIs should not automatically receive unrestricted write access.

Potential write operations may include:

- institution submissions;
- correction reports;
- community recommendations;
- ratings;
- approved partner updates.

Write submissions must enter appropriate governance workflows.

---

## 21. Validation

Future write interfaces should validate:

- entity identity;
- target scope;
- data type;
- temporal context;
- source/provenance;
- required evidence where applicable.

Validation must not be treated as verification itself.

---

## 22. Versioning

Long-lived API contracts should support controlled evolution.

Changes should distinguish:

- backward-compatible additions;
- behavior changes;
- deprecated representations;
- breaking changes.

Major API changes require governed review.

---

## 23. Compatibility

Long-term compatibility should consider:

- mobile apps;
- third-party integrations;
- cached responses;
- search systems;
- AI agents;
- analytics;
- internal tools.

A breaking change should not be made casually because downstream consumers may persist for years.

---

## 24. Rate and Resource Governance

Future API operations may require controls for:

- request volume;
- expensive search;
- bulk extraction;
- geographic queries;
- AI-intensive operations.

Limits should protect service stability without changing the meaning of data.

---

## 25. Bulk Data Access

Future bulk exports may be considered for approved use cases.

Bulk representations should preserve important:

- identifiers;
- provenance;
- lifecycle;
- temporal states;
- verification states.

Bulk access must follow security, privacy and licensing rules.

---

## 26. Caching and Freshness

API consumers may cache data.

Future contracts should define appropriate freshness semantics for time-sensitive information.

Consumers must not assume that cached admissions, fees or scholarships remain current indefinitely.

---

## 27. Errors and Unknowns

API responses should preserve meaningful distinctions between:

- not found;
- unknown;
- unavailable;
- restricted;
- conflicted;
- stale;
- invalid request.

An empty response must not always mean “does not exist.”

---

## 28. Privacy

Future APIs must not expose personal information merely because it exists internally.

Community contributor information should follow privacy governance.

Exact location, contact and operational information may require separate access controls where appropriate.

---

## 29. Security

Future API architecture must address:

- authentication;
- authorization;
- abuse prevention;
- input validation;
- audit logging;
- credential protection;
- data minimization;
- incident response.

Implementation decisions remain outside this document.

---

## 30. Observability

Future API operations may monitor:

- availability;
- errors;
- latency;
- usage;
- abuse patterns;
- data freshness;
- deprecated clients.

Operational metrics must remain separate from institutional quality metrics.

---

## 31. Documentation

Public APIs should eventually provide:

- stable documentation;
- data definitions;
- lifecycle meanings;
- provenance meanings;
- version information;
- deprecation notices;
- usage constraints.

Documentation should link concepts back to canonical governance.

---

## 32. API and SEO

API representations should not create uncontrolled duplicate public pages.

Public SEO representations remain governed by MOD-020.

---

## 33. API and Search

Search APIs expose derived discovery results.

They must not mutate canonical records merely because a query is executed.

---

## 34. API and Ranking

Future ranking APIs must expose methodology/context appropriate to the ranking representation.

A score without its definition and scope can be misleading and should not be treated as self-explanatory.

---

## 35. Explicit Non-Approval of Implementation

This document does not approve:

- REST, GraphQL or another API style;
- programming language;
- framework;
- API gateway;
- authentication provider;
- hosting platform;
- database;
- cache;
- rate-limiting technology;
- AI provider.

---

## 36. Future Evolution

Future documents may define:

- concrete API contracts;
- authentication/authorization;
- developer access policy;
- API versioning implementation;
- bulk data policy;
- partner integration policy;
- security/privacy governance.

---

## 37. Current Implementation State

The Institutes Directory currently exposes a versioned, read-only API v1 surface:

- `GET /api/v1/institutions`
- `GET /api/v1/institutions/{institution:slug}`

The collection endpoint reuses the governed directory search/filter boundary and bounds `per_page` to 1–100. The profile endpoint applies the public lifecycle and geography visibility boundary before serialization.

Nearby discovery is also implemented as a bounded read-only operation at `GET /api/v1/institutions/nearby`, using active campus coordinates, active geography, a maximum 100 km radius, and a maximum 100-result limit.

Implementation details are tracked in `docs/08-development/DEV-298-API-DATA-ACCESS-HARDENING.md`.

## 38. Approval State

This document is approved project governance. Future concrete API contracts, authentication, partner access, bulk export, write workflows, and security/privacy controls remain subject to separate governed decisions.
