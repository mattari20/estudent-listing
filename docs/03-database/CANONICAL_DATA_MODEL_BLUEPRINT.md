# DB-001 — Canonical Data Model Blueprint

**Document ID:** DB-001  
**Title:** Canonical Data Model Blueprint  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, ARC-004, MOD-005 through MOD-018  
**Technology Status:** Technology-neutral

## 1. Purpose

This document converts the approved domain model into an implementation-ready conceptual data blueprint.

It defines canonical entities, relationships, identity rules, lifecycle requirements, provenance boundaries and derived representations.

It does not approve a database engine, physical table design, ORM, storage vendor, indexing technology or implementation language.

## 2. Core Principle

The canonical data model is the source of truth for directory facts.

Search indexes, SEO pages, structured data, APIs, rankings, recommendations, AI outputs, caches and analytics are derived or access representations unless a future governed document explicitly establishes otherwise.

## 3. Canonical Entity Map

The initial canonical model consists of:

1. Institution
2. Institution Name / Alias
3. Campus
4. Geography
5. Program
6. Program Offering
7. Admission / Intake
8. Eligibility Requirement
9. Merit / Selection Record
10. Fee Information
11. Scholarship / Financial Aid
12. Accreditation / Recognition / Regulatory Record
13. Source
14. Evidence
15. Assertion
16. Verification State
17. Change / Lifecycle Event

Community and external signals remain separate domains:

18. Review / Feedback
19. Community Recommendation / Rating
20. External Ranking / Signal

## 4. Institution

Institution is the stable identity of an educational organization.

Conceptual responsibilities:

- stable institution identifier;
- current identity state;
- institution type/classification references;
- official identity;
- lifecycle;
- relationships to campuses;
- relationships to programs;
- provenance;
- historical identity.

An institution identifier must not depend on its current name, slug or address.

Identifiers must not be casually recycled for unrelated entities.

## 5. Institution Names and Aliases

Names are attributes/history associated with an institution rather than replacements for institution identity.

The model should distinguish where applicable:

- official name;
- legal name;
- current public name;
- common name;
- short name;
- acronym;
- historical name;
- translated name;
- transliterated name;
- alternate/alias.

Each name representation may require:

- language;
- validity period;
- source/evidence;
- preferred/current status.

Name changes must preserve historical discoverability.

## 6. Institution Type and Classification

Institution type/classification must remain governed taxonomy rather than free-text labels.

An institution may have:

- primary classification;
- additional classifications;
- historical classifications;
- classification validity period.

Classification must not be confused with institution identity or legal status.

## 7. Campus

Campus is a separately identifiable physical presence belonging to an institution.

The model should support:

- stable campus identifier;
- institution relationship;
- campus name;
- campus type;
- lifecycle state;
- address;
- geographic references;
- coordinates where available;
- contacts;
- source/evidence;
- validity period.

A campus identifier must remain stable through ordinary name changes.

A campus must not be used to represent an institution merely because the institution has one physical location.

## 8. Geography

Geography is a reusable reference domain.

It should support the governed hierarchy from MOD-005, including as applicable:

- country;
- province/territory;
- division;
- district;
- tehsil/subdivision;
- city/municipality;
- locality/area.

The model must permit:

- aliases;
- historical names;
- uncertain or partial geography;
- temporal changes;
- source/evidence;
- geographic relationships.

Institution identity must not depend on geography.

## 9. Address and Coordinates

Physical address and coordinates are distinct representations.

The conceptual model should permit:

- structured geographic references;
- human-readable address;
- postal information;
- latitude/longitude where available;
- coordinate precision/quality where needed;
- validity period;
- source/evidence.

Coordinates must not be treated as proof of institutional identity.

## 10. Program

Program represents the academic/educational offering concept independently of a particular admission cycle.

A program may include:

- stable program identifier;
- institution relationship;
- governed program name;
- award/credential;
- academic level;
- discipline/subject classification;
- aliases;
- lifecycle;
- source/evidence;
- historical identity.

Program identity should not be recreated for every intake.

## 11. Program Offering

Program Offering represents a context-specific instance of a program.

It may vary by:

- campus;
- delivery mode;
- intake;
- admission cycle;
- duration;
- availability;
- category;
- award details;
- effective period.

This separation is essential for accurate historical and current information.

## 12. Admission / Intake

Admission information is temporal.

An admission/intake record should be capable of representing:

- institution;
- program offering;
- campus where applicable;
- admission cycle;
- intake name/season;
- opening date;
- closing date;
- announcement date;
- status;
- application destination;
- source/evidence;
- effective period.

Historical admission records must remain distinguishable from current admissions.

## 13. Eligibility Requirement

Eligibility is separate from merit.

The conceptual model should support requirements such as:

- prior qualification;
- subjects;
- minimum marks/grades;
- entry test;
- domicile;
- experience;
- professional registration;
- language requirements;
- age requirements where applicable;
- applicant category.

Requirements should preserve source and applicability context.

## 14. Merit and Selection

Merit records must retain context.

A merit record should be associated with:

- institution;
- program/offering;
- campus where applicable;
- intake/year;
- applicant category/quota;
- stage/list;
- merit type;
- score/position/cutoff where published;
- methodology/source;
- publication date;
- source/evidence.

Eligibility, merit and actual selection must remain separate concepts.

## 15. Fees and Financial Information

Financial information is optional and must never be a hard dependency for the core directory.

The model should permit fee components such as:

- application;
- admission;
- tuition;
- semester;
- annual;
- credit-hour;
- examination;
- registration;
- laboratory;
- hostel;
- transport;
- technology;
- library;
- deposits.

Each financial assertion should preserve context including program/offering, campus, intake/year, category, mode, period and source.

Missing financial information is a data-availability state, not a failed institution record.

## 16. Scholarship and Financial Aid

Scholarships remain separate from fees.

The model should support:

- scholarship identity;
- provider;
- target scope;
- eligibility;
- benefit;
- duration;
- renewal;
- application period;
- lifecycle;
- source/evidence.

Unknown scholarship information must not be interpreted as absence of scholarships.

## 17. Accreditation, Recognition and Regulatory Status

Recognition, accreditation, affiliation, authorization and licensing must remain distinct concepts.

A record should identify:

- authority;
- type of status;
- subject/scope;
- institution/program/campus applicability;
- effective period;
- status;
- source/evidence.

The directory's internal verification status must not be confused with external regulatory recognition.

## 18. Source

Source represents where information originated.

A source may be:

- institutional;
- regulatory/authoritative;
- secondary;
- tertiary/discovery;
- submitted by institution;
- submitted by contributor;
- web-discovered;
- AI-assisted discovery.

Source records should support identity, authority context, location/reference, access metadata and temporal information as governed by MOD-006.

## 19. Evidence

Evidence is the traceable material supporting an assertion.

Evidence must remain distinguishable from the source itself.

An evidence record may identify:

- source;
- captured/retrieved time;
- relevant content/location;
- evidence type;
- applicable period;
- processing state;
- reviewer information where applicable.

## 20. Assertion

An assertion represents a claim about a canonical entity or relationship.

Conceptually:

`Source → Evidence → Assertion → Verification State → Published Representation`

Assertions may conflict.

Conflicting assertions must not be silently overwritten.

## 21. Verification State

Verification state describes the governance status of an assertion or relevant data item.

The model should support the governed states from MOD-006, including:

- discovered;
- submitted;
- under review;
- corroborated;
- verified;
- conditionally accepted;
- conflicted;
- stale;
- superseded;
- rejected;
- withdrawn.

Verification state is not the same as confidence.

## 22. Confidence and Quality

Confidence should not replace verification.

Quality may be represented through multiple dimensions including:

- completeness;
- freshness;
- authority;
- provenance;
- consistency;
- temporal validity;
- scope correctness;
- uniqueness;
- verification;
- conflict state.

A single universal trust score must not become the canonical data model.

## 23. Lifecycle and Change Events

Important entities require lifecycle history.

Change events may include:

- creation/discovery;
- verification;
- activation;
- rename;
- relocation;
- opening;
- closure;
- reopening;
- merger;
- split/reorganization;
- supersession;
- correction;
- withdrawal.

Historical records must remain queryable where policy permits.

## 24. Relationships

Core relationships include:

- Institution → Institution Names
- Institution → Institution Types
- Institution → Campuses
- Campus → Geography
- Institution → Programs
- Program → Program Offerings
- Program Offering → Campus
- Program Offering → Admissions
- Program Offering → Eligibility
- Program Offering → Merit/Selection
- Program Offering → Fees
- Institution/Program/Offering → Scholarships
- Institution/Campus/Program/Offering → Accreditation/Recognition
- Any governed assertion → Source/Evidence/Verification

## 25. Community Signals

Reviews, ratings and recommendations must remain separate from canonical institutional facts.

They should retain:

- target;
- contributor category;
- experience period;
- content;
- moderation state;
- verification/provenance;
- timestamps;
- applicable context.

Community claims require independent evidence before becoming canonical facts.

## 26. External Rankings and Signals

External rankings must preserve:

- publisher;
- ranking name;
- edition;
- year/period;
- scope;
- rank/score/band/percentile;
- methodology reference;
- source;
- publication context.

External ranking data must not overwrite canonical institutional facts.

## 27. Derived Representations

The model must permit derived representations without duplicating authority.

Examples:

- search index;
- filters;
- nearby results;
- map representation;
- SEO page;
- structured data;
- API response;
- comparison view;
- ranking input/output;
- recommendation explanation;
- AI summary.

Derived data must be refreshable from canonical data and provenance.

## 28. Missing and Unknown Values

The model must distinguish where material:

- known;
- partially known;
- unknown;
- not published;
- not applicable;
- awaiting verification;
- conflicting;
- historical only;
- stale;
- superseded.

Null/empty data must not automatically acquire a semantic meaning such as false, zero or none.

## 29. Temporal Model

Time-sensitive information must support effective periods and publication/source dates where relevant.

The implementation must be able to distinguish:

- when information was published;
- when evidence was captured;
- when a fact became effective;
- when it ceased to apply;
- when the directory verified it.

## 30. Identity Resolution

Duplicate detection must operate without destroying legitimate entities.

Potential matching signals may include:

- official names;
- aliases;
- addresses;
- coordinates;
- official websites;
- regulatory identifiers;
- campus relationships;
- historical relationships.

AI may assist matching, but final high-impact identity decisions require governed controls.

## 31. Data Retirement

Retirement must preserve history and auditability.

The implementation must distinguish:

- superseded;
- inactive;
- closed;
- rejected;
- withdrawn;
- archived.

Deletion must not be used as a substitute for lifecycle governance where historical continuity is required.

## 32. Canonical vs Derived Boundary

The following are canonical-domain concerns:

- identity;
- relationships;
- governed attributes;
- lifecycle;
- provenance;
- verification;
- temporal validity.

The following are derived/access concerns:

- search ranking;
- SEO rendering;
- recommendation;
- AI summary;
- map visualization;
- cache;
- analytics;
- API presentation.

## 33. Implementation Constraints

The physical implementation must preserve:

- stable identifiers;
- historical relationships;
- provenance;
- verification state;
- temporal validity;
- optional-data semantics;
- separation of program and offering;
- separation of institution and campus;
- separation of canonical facts and community/external signals.

## 34. Initial Implementation Boundary

The first build should prioritize:

1. Institution
2. Institution names/aliases
3. Institution classification
4. Campus
5. Geography
6. Program
7. Program Offering
8. Source/Evidence/Assertion/Verification
9. Lifecycle/change history

Admissions, merit, fees, scholarships, accreditation, community and ranking layers should be designed for extension but may be implemented progressively.

## 35. Explicit Non-Approval

This document does not define:

- physical database tables;
- SQL/NoSQL choice;
- database vendor;
- ORM;
- indexes;
- caching technology;
- search technology;
- API technology;
- programming language;
- framework;
- hosting.

Those decisions require separate technical evaluation.

## 36. Next Documents

After approval, the next implementation-facing documents are:

- API-001 — Initial API Contract Blueprint
- UI-001 — Public Directory UX Specification
- SEO-001 — SEO Implementation Specification
- TST-001 — MVP Testing Strategy

A physical database design should be created only after this conceptual model is accepted.

## Approval State

This document is a **final candidate pending merge into `main`**.
