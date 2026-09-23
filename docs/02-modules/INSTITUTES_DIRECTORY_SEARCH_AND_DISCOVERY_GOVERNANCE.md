# MOD-019 — Institutes Directory: Search and Discovery Governance

**Document ID:** MOD-019  
**Title:** Institutes Directory — Search and Discovery Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-002, MOD-003, MOD-004, MOD-005, MOD-006, MOD-007, MOD-008, MOD-009, MOD-010, MOD-011, MOD-012, MOD-013, MOD-014, MOD-015, MOD-016, MOD-017, MOD-018  
**Related Documents:** Institute directory information, taxonomy, geography, data quality and trust governance  
**Next Documents:** SEO and Structured Data Governance; API and Data Access Governance

---

## 1. Purpose

This document defines the long-term governance principles for search and discovery across the Institutes Directory.

Search is a **derived discovery capability**. It must consume governed canonical information and must not become a second source of truth.

---

## 2. Core Principle

Search should help users discover relevant institutions, campuses, programs and educational opportunities without changing the meaning or authority of the underlying data.

The canonical dataset remains authoritative.

---

## 3. Searchable Entity Types

Discovery may operate across:

- institutions;
- campuses;
- programs;
- program offerings;
- admissions;
- scholarships;
- geographic areas;
- educational categories;
- other approved directory entities.

The relationship between these entities must remain explicit.

---

## 4. Search Intent

The system should recognize different discovery intents, including:

- institution lookup;
- university/college discovery;
- program discovery;
- subject/discipline discovery;
- admission discovery;
- scholarship discovery;
- geographic discovery;
- nearby discovery;
- comparison discovery;
- eligibility-oriented discovery;
- campus discovery;
- educational category discovery.

Intent interpretation must not alter canonical facts.

---

## 5. Exact Lookup vs Discovery

The system should distinguish:

**Exact lookup**
- user seeks a known institution/program.

**Discovery**
- user seeks options matching criteria.

Exact lookup should prioritize identity and aliases.

Discovery may use multiple governed attributes and derived signals.

---

## 6. Query Interpretation

Future search systems may interpret:

- names;
- aliases;
- acronyms;
- historical names;
- transliterations;
- spelling variations;
- geographic terms;
- program names;
- disciplines;
- qualification levels;
- admission terms;
- scholarship terms;
- natural-language queries.

Interpretation must remain distinguishable from canonical facts.

---

## 7. Identity and Alias Search

Institution identity should support discovery through:

- official names;
- legal names;
- common names;
- short names;
- acronyms;
- historical names;
- translated names;
- transliterations;
- approved aliases.

An alias must not create a duplicate institution identity.

---

## 8. Geography-Aware Discovery

Search may use:

- country;
- province/territory;
- division;
- district;
- tehsil/subdivision;
- city;
- locality/area;
- campus location;
- distance/nearby context.

Geographic discovery must follow MOD-005.

A nearby result must not imply institutional quality or endorsement.

---

## 9. Program Discovery

Program search may consider:

- program name;
- program family;
- discipline;
- award;
- qualification level;
- delivery mode;
- campus;
- institution;
- intake;
- current availability.

Program identity and program offering must remain separate.

---

## 10. Admission Discovery

Admission-oriented search may use:

- current admission status;
- intake;
- application dates;
- eligibility;
- entry tests;
- application destination;
- campus;
- program.

Historical admission records should not be presented as current unless explicitly identified as historical.

---

## 11. Scholarship Discovery

Scholarship search may use:

- scholarship type;
- eligibility;
- institution;
- program;
- campus;
- intake;
- applicant category;
- application period;
- benefits.

Unknown scholarship information must not be interpreted as absence of scholarships.

---

## 12. Filters

Future filters may include:

- institution type;
- province;
- city;
- campus;
- program level;
- discipline;
- delivery mode;
- admission status;
- eligibility attributes;
- scholarship availability;
- accreditation/recognition;
- fees where available;
- community signals;
- external ranking signals.

Filters must respect governed unknown/partial states.

---

## 13. Missing-Data Behavior

Search must not unintentionally exclude records merely because optional information is missing.

For example:

- missing fees should not automatically exclude an institution;
- missing scholarship data should not mean no scholarship;
- missing reviews should not mean poor reputation;
- missing ranking should not imply low quality.

Where appropriate, users should be able to distinguish “unknown” from “does not meet criterion.”

---

## 14. Relevance

Search relevance is a derived function.

Potential relevance inputs may include:

- exact identity match;
- name/alias match;
- program match;
- geographic match;
- query intent;
- current availability;
- structured attribute match;
- freshness;
- data quality;
- source-backed information.

No universal relevance formula is approved by this document.

---

## 15. Ranking of Search Results

Search-result ordering is distinct from institutional ranking.

Search ordering answers:

**“Which records best match this query?”**

Institutional ranking answers a different question and is governed separately.

Search relevance must not silently become an institutional quality ranking.

---

## 16. Community Signals

Student and teacher recommendations and ratings from MOD-017 may be displayed or used as a search signal where appropriate.

They must remain clearly identified as community-derived.

Search relevance must not convert community opinion into verified institutional fact.

---

## 17. External Rankings

External rankings governed by MOD-016 may be searchable or displayed.

The publisher, edition/year, scope and methodology context should remain available.

External ranking signals must not automatically become eStudent institutional rankings.

---

## 18. Data Quality Signals

MOD-018 quality information may influence discovery behavior where appropriate.

Examples:

- stale information;
- verified information;
- unresolved conflicts;
- recently reviewed records;
- historical records.

Quality signals should improve user understanding rather than create unsupported claims.

---

## 19. Current vs Historical Discovery

Search should distinguish:

- current;
- planned;
- active;
- suspended;
- closed;
- historical;
- superseded.

Historical records may remain discoverable when useful, but their temporal state must be clear.

---

## 20. Duplicate Search Results

Duplicate results should be minimized through governed identity and matching.

Potential duplicate detection may consider:

- institution identity;
- campus;
- program;
- aliases;
- geography;
- source relationships;
- historical identity relationships.

Duplicate detection must not merge distinct entities merely because their names or locations are similar.

---

## 21. Ambiguous Queries

If a query can refer to multiple entities, search may present disambiguation.

Examples:

- same institution name in different cities;
- acronym shared by multiple institutions;
- program name shared across institutions;
- historical and current institution identities.

The system should preserve user choice rather than silently selecting an ambiguous entity.

---

## 22. Natural-Language Search

Future natural-language search may support requests such as:

- “universities offering computer science in Lahore”;
- “BS programs with current admissions”;
- “engineering colleges near a city”;
- “programs for students with a particular qualification.”

Natural-language interpretation must map to governed fields and relationships.

The system must not invent missing eligibility, admission, fee or accreditation facts.

---

## 23. AI-Assisted Search

AI may assist with:

- query interpretation;
- synonym expansion;
- spelling correction;
- transliteration;
- intent detection;
- entity matching;
- natural-language answers;
- result explanations.

AI-generated interpretations must remain derived.

When a query cannot be answered reliably from governed data, the system should preserve uncertainty rather than fabricate an answer.

---

## 24. Search Answers and Summaries

Future AI search summaries should distinguish:

- canonical facts;
- source-backed facts;
- community signals;
- external signals;
- derived interpretations;
- unknown information.

A generated summary must not hide material uncertainty.

---

## 25. Search and Recommendations

Search and recommendation systems are related but different.

Search responds primarily to explicit user intent.

Recommendation may infer broader suitability or preferences.

Future recommendation systems require separate governance and must not be treated as ordinary search ranking.

---

## 26. Personalization

Future personalization may use user-provided preferences such as:

- location;
- study level;
- discipline;
- preferred delivery mode;
- budget where voluntarily supplied;
- admission timing;
- other explicit preferences.

Personalization must not change canonical facts.

Any future personalization governance must define privacy and consent boundaries.

---

## 27. Nearby Discovery

Nearby search may use campus-level geographic information.

It should distinguish:

- physical campus;
- administrative office;
- facility;
- online-only offering.

“Nearby” is a geographic relationship, not a quality claim.

---

## 28. Search Result Labels

Public search results may expose meaningful labels such as:

- Verified;
- Current;
- Historical;
- Admission Open;
- Information Unavailable;
- Community Rated;
- External Ranking Signal.

Labels must accurately reflect governed states.

---

## 29. Search Freshness

Search indexes and derived representations should eventually reflect material canonical changes.

High-impact changes may require faster propagation than low-impact changes.

No implementation-specific propagation interval is approved here.

---

## 30. Search Safety and Trust

Search should avoid presenting unsupported or misleading combinations.

Examples:

- an expired admission as currently open;
- a historical fee as current;
- an external ranking as an eStudent ranking;
- a community opinion as institutional fact;
- an unverified accreditation claim as verified.

---

## 31. SEO Relationship

Search discovery and public SEO are related but distinct.

Internal search may use richer signals than public search-engine representations.

SEO must follow canonical identity, lifecycle and evidence governance.

Detailed SEO rules will be defined separately.

---

## 32. API Relationship

Future APIs may expose search and discovery capabilities.

API responses should preserve governed meanings and state distinctions.

Search APIs must not create a parallel canonical dataset.

---

## 33. Analytics

Future search analytics may measure:

- query volume;
- zero-result queries;
- query reformulation;
- click-through;
- filter usage;
- geographic demand;
- program demand;
- unanswered intents.

Analytics must not be treated as evidence that an institution or program is objectively better.

---

## 34. Search Quality Evaluation

Future evaluation may consider:

- relevance;
- exact-match accuracy;
- recall;
- freshness;
- duplicate rate;
- ambiguity handling;
- zero-result rate;
- factual correctness;
- user task completion.

Search quality measurements evaluate the search system, not institutions.

---

## 35. Explicit Non-Approval of Implementation

This document does not approve:

- a specific search engine;
- database search technology;
- indexing technology;
- ranking algorithm;
- vector database;
- AI provider;
- embedding model;
- geospatial engine;
- frontend search implementation;
- API implementation.

---

## 36. Future Evolution

Future documents may define:

- search architecture;
- search index model;
- relevance methodology;
- synonym and alias governance;
- natural-language query governance;
- recommendation governance;
- SEO and structured data;
- API contracts;
- analytics and search-quality monitoring.

---

## 37. Approval State

This document is a **final candidate pending merge into `main`**. Once merged, it becomes approved project documentation unless superseded by a governed decision.
