# MOD-009 — Institutes Directory: Program and Program Offering Governance

**Document ID:** MOD-009  
**Title:** Institutes Directory — Program and Program Offering Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-001, MOD-002, MOD-003, MOD-004, MOD-005, MOD-006, MOD-007, MOD-008  
**Related Documents:** MOD-004, MOD-005, MOD-006, MOD-007, MOD-008  
**Next Documents:** Program data quality, admissions and intake governance, program taxonomy detail, implementation specifications

---

## 1. Purpose

This document establishes the long-term conceptual governance for **Programs** and **Program Offerings** within the Institutes Directory.

The directory must distinguish between:

- an institution,
- a campus or physical presence,
- a reusable program identity,
- and a time-specific offering of that program.

This separation is required so that the platform can represent current, historical, planned, suspended, discontinued, campus-specific, multi-campus, online, distance, and other forms of program availability without corrupting institutional identity or historical records.

This document is technology-neutral. It defines concepts, boundaries, relationships, lifecycle rules, evidence requirements, and governance principles. It does not approve a database schema, API, framework, search engine, frontend implementation, AI provider, or deployment architecture.

---

## 2. Core Principle

A **Program** is not the same thing as a **Program Offering**.

A Program represents the enduring academic concept recognized by the directory.

A Program Offering represents a specific manifestation of that program under defined conditions such as:

- institution,
- campus or delivery location,
- academic period or intake,
- delivery mode,
- availability state,
- award or credential,
- duration,
- and other material offering attributes.

This distinction prevents the same academic program from being duplicated merely because it is offered at multiple campuses, in multiple modes, or during different admission cycles.

---

## 3. Entity Boundaries

The following concepts must remain distinct.

### 3.1 Institution

The legal or organizational educational entity governed by MOD-007.

An institution may offer many programs.

### 3.2 Campus

A physical or organizational teaching location governed by MOD-008.

A program may be offered at one, several, or no physical campuses.

### 3.3 Program

The canonical academic program concept.

A program may have:

- official name,
- common name,
- short name,
- acronym,
- historical names,
- aliases,
- discipline relationships,
- qualification or award characteristics,
- academic level,
- lifecycle history.

### 3.4 Program Offering

A specific availability of a program under defined institutional, temporal, geographic, delivery, or admission conditions.

A Program Offering may therefore vary by:

- campus,
- delivery mode,
- intake,
- academic year or term,
- duration,
- award wording,
- admission availability,
- language or other material delivery attributes.

### 3.5 Course / Subject

An individual course, subject, module, or curricular component is not automatically a Program.

The future curriculum domain may reference programs and offerings, but curriculum structure must not be conflated with program identity.

---

## 4. Program Identity

Program identity should remain stable when non-material presentation details change.

Examples that do not automatically create a new program identity include:

- formatting changes,
- common abbreviation changes,
- minor naming variations,
- translated representations,
- search aliases,
- spelling/transliteration variants.

A new program identity may be required when evidence indicates a materially distinct academic program rather than a renamed or continued program.

Identity decisions must be evidence-based and auditable under MOD-006.

---

## 5. Program Naming

The directory should conceptually support:

- official/current program name,
- historical name,
- common name,
- short name,
- acronym,
- alternate institutional naming,
- translated name,
- transliteration,
- search aliases,
- legacy names.

Names must not be treated as the sole identity key.

Historical names should remain available when they are needed to understand previous offerings, records, search queries, or source evidence.

---

## 6. Program Families and Related Programs

The platform may represent relationships such as:

- program family,
- specialization,
- concentration,
- major,
- minor,
- track,
- pathway,
- dual-degree structure,
- integrated program,
- successor program,
- predecessor program.

These relationships must not be collapsed into a single generic program identity.

A specialization or track may be represented independently only when its identity, discovery, admissions, credential, or governance requirements justify that treatment.

---

## 7. Academic Level and Qualification

Programs may be classified by academic level or qualification category, subject to the approved taxonomy in MOD-004 and future detailed taxonomy specifications.

Examples may include:

- certificate,
- diploma,
- undergraduate,
- postgraduate,
- master's,
- doctoral,
- professional qualification,
- technical/vocational qualification,
- other approved categories.

The directory must distinguish the **academic level** from the program's subject or discipline.

A program name alone must not be used as proof of academic level.

---

## 8. Discipline and Subject Classification

Programs may be associated with one or more subject, discipline, or field classifications.

Classification should support:

- broad discipline,
- narrower subject,
- interdisciplinary programs,
- emerging disciplines,
- institutional naming differences,
- historical classifications.

Taxonomy classification is a governed representation and must not overwrite the canonical program identity.

---

## 9. Institution–Program Relationship

An institution may:

- offer one program,
- offer many programs,
- stop offering a program,
- resume a previously offered program,
- rename a program,
- replace a program with a successor,
- offer equivalent or related programs under different names.

The existence of a program in the directory must not by itself imply that the institution currently offers it.

Current availability must be established through Program Offering state and evidence.

---

## 10. Program Offering Identity

A Program Offering is the governed representation of a program being made available under a defined set of conditions.

Material dimensions may include:

- institution,
- program,
- campus or delivery location,
- delivery mode,
- intake,
- academic period,
- availability state,
- award/credential,
- duration,
- other materially relevant attributes.

The platform must avoid creating unnecessary duplicate offerings when two records describe the same real-world offering.

---

## 11. Campus Relationship

Program Offerings may be:

- campus-specific,
- available at multiple campuses,
- available across a defined set of campuses,
- available online,
- available through distance education,
- delivered through another approved physical or virtual arrangement.

Campus relationships must follow MOD-008.

A program's relationship to an institution does not automatically imply availability at every campus of that institution.

---

## 12. Delivery Mode

The directory should distinguish relevant delivery modes, subject to future detailed taxonomy governance.

Possible conceptual categories include:

- on-campus,
- hybrid/blended,
- online,
- distance,
- evening,
- weekend,
- other approved delivery arrangements.

Delivery mode must not be inferred solely from marketing language when authoritative evidence is available.

---

## 13. Intake and Academic Period

A Program Offering may be associated with:

- admission intake,
- academic year,
- semester/term,
- session,
- rolling admission,
- other recognized academic period.

The platform must distinguish:

- a program that exists,
- a program currently accepting admissions,
- a program offered in a specific intake,
- and a historical offering.

This distinction is essential for accurate student discovery and historical preservation.

---

## 14. Program Lifecycle

Conceptual lifecycle states may include:

- Candidate
- Under Review
- Verified
- Planned
- Active
- Temporarily Suspended
- Inactive
- Discontinued
- Historical
- Superseded
- Rejected

These states are governed concepts, not implementation enum commitments.

A Program lifecycle state must not be used as a substitute for the availability state of an individual Program Offering.

---

## 15. Program Offering Lifecycle

Conceptual offering states may include:

- Discovered
- Submitted
- Under Review
- Planned
- Announced
- Open for Admission
- Active
- Temporarily Suspended
- Closed for Admission
- Completed
- Discontinued
- Historical
- Superseded
- Rejected

An offering may have a different state from its parent Program.

For example, a Program may remain active while a particular campus offering is temporarily suspended.

---

## 16. Historical and Temporal Representation

Historical program information must be preserved when supported by evidence.

The directory should be able to distinguish:

- formerly offered programs,
- renamed programs,
- replaced programs,
- programs that existed during a specific period,
- offerings from previous academic years,
- discontinued offerings,
- reopened offerings.

Historical records must not be silently overwritten by current information.

---

## 17. Planned Programs and Announcements

A proposed or announced program must not automatically be represented as an active offering.

The platform should distinguish:

- proposed,
- announced,
- approved where evidence exists,
- planned,
- accepting applications,
- operational,
- and historical/discontinued.

The evidence standard for each state must be governed through MOD-006.

---

## 18. Program Evidence and Verification

Program and offering records must follow the source and evidence governance established in MOD-006.

Relevant evidence may include:

- official institutional program pages,
- official admission prospectuses,
- official catalogs,
- official regulatory or accreditation records,
- institution-submitted information,
- credible secondary sources,
- archived institutional materials,
- discovery sources used only for candidate identification.

The platform must preserve traceability from published program information to supporting evidence.

---

## 19. Conflicting Program Information

Conflicts may occur between:

- program names,
- qualification levels,
- duration,
- campus availability,
- admission status,
- delivery mode,
- intake information,
- historical and current pages.

Conflicting information must not be silently merged.

The directory should preserve the competing assertions and apply the verification governance of MOD-006.

No universal source hierarchy should be assumed for every program attribute.

---

## 20. Program Matching and Duplicate Detection

Program matching should consider multiple signals, including where available:

- institution,
- official program name,
- qualification,
- academic level,
- discipline,
- campus,
- delivery mode,
- source,
- temporal validity,
- award/credential,
- program relationships.

Exact name matching must not be treated as sufficient identity proof.

Likewise, similar names must not automatically be treated as duplicate programs.

AI-assisted matching may be used as a discovery or review aid, but canonical identity decisions remain governed and evidence-based.

---

## 21. Program Renaming and Successor Programs

A program name change may represent:

- continuation under a new name,
- a materially changed program,
- replacement by a successor,
- consolidation,
- restructuring,
- or a genuinely new program.

The platform should preserve the relationship and history rather than deleting the previous representation.

Where evidence supports continuity, the historical name should remain linked to the continuing program.

---

## 22. Program Replacement and Discontinuation

When an institution replaces a program:

1. preserve the historical program/offering record;
2. record the relevant lifecycle event;
3. identify a successor where supported;
4. avoid redirecting historical information into an unrelated current program;
5. preserve evidence and temporal context.

A discontinued offering must not be presented as a current admission opportunity.

---

## 23. Program and Campus Changes

Changes such as relocation, campus closure, or movement of a program between campuses must not alter historical records retroactively.

The platform should be able to represent:

- program moved from Campus A to Campus B,
- program expanded to additional campuses,
- program removed from one campus but retained at another,
- campus closure affecting a specific offering,
- online continuation after physical offering closure.

These relationships must follow MOD-008 and MOD-005.

---

## 24. Admission Availability

Admission availability is a property of a relevant Program Offering and time period, not merely a property of the institution or program.

The platform should distinguish at least conceptually between:

- not offered,
- offered but not currently admitting,
- admissions announced,
- applications open,
- applications closed,
- admissions completed,
- status unknown.

Detailed admissions governance should be specified separately before implementation.

---

## 25. Program URLs and SEO

Program discovery URLs must follow the canonical URL principles established in MOD-002.

The system should distinguish:

- canonical program identity,
- program offering,
- campus context,
- historical representations,
- search/filter URLs,
- admission-period representations.

A program's URL must remain stable wherever practical even when current offering details change.

SEO representations, structured data, search indexes, and AI-generated summaries are derived representations and must not become canonical sources of program truth.

---

## 26. Search, Filters and Discovery

Program information may support discovery by:

- institution,
- location,
- campus,
- academic level,
- discipline,
- qualification,
- delivery mode,
- intake,
- admission status,
- other approved taxonomy dimensions.

Search results and filters are derived consumers of canonical program and offering data.

A search index must not become the authoritative program record.

---

## 27. AI-Assisted Program Intelligence

AI may assist with:

- extracting program information from sources,
- detecting possible program changes,
- identifying duplicate or related programs,
- matching aliases,
- classifying disciplines,
- identifying missing information,
- suggesting review priorities,
- summarizing verified program information.

AI-generated content must remain distinguishable from canonical verified data.

AI may propose an identity or relationship; governed evidence and review determine whether it becomes canonical.

---

## 28. Data Quality Requirements

Program and offering data should be evaluated across dimensions including:

- identity correctness,
- naming accuracy,
- qualification accuracy,
- classification accuracy,
- campus accuracy,
- availability accuracy,
- temporal accuracy,
- source traceability,
- freshness,
- completeness,
- consistency,
- duplication,
- conflict state.

Data quality should be attribute-sensitive rather than represented by one simplistic global score.

---

## 29. Governance and Change Events

Material program changes should be represented as events where appropriate, including:

- program created,
- program verified,
- program renamed,
- program reclassified,
- program activated,
- program suspended,
- program discontinued,
- program reopened,
- offering created,
- offering announced,
- admission opened,
- admission closed,
- campus relationship changed,
- delivery mode changed,
- award/credential changed,
- successor relationship established.

Events should preserve temporal and evidence context.

---

## 30. Special Cases

The governance must accommodate:

- joint programs,
- dual-degree programs,
- integrated programs,
- interdisciplinary programs,
- franchise or affiliated delivery arrangements,
- externally validated programs,
- programs offered by constituent institutions,
- programs offered across multiple campuses,
- online-only programs,
- distance programs,
- short courses,
- microcredentials,
- certificates and diplomas,
- professional qualifications,
- technical/vocational programs,
- programs whose names differ by institution,
- programs with uncertain or conflicting information.

Each special case must be handled according to evidence and approved taxonomy rather than hard-coded assumptions.

---

## 31. Relationship to Approved Documents

MOD-009 depends on and extends:

- **MOD-004:** taxonomy and classification;
- **MOD-005:** geography and location;
- **MOD-006:** source, evidence and verification;
- **MOD-007:** institution identity and lifecycle;
- **MOD-008:** campus and physical presence;
- **MOD-002:** information architecture and URL governance.

No program implementation should bypass these governing documents.

---

## 32. Explicit Non-Approval of Implementation

This document does **not** approve:

- database tables or schemas,
- SQL/NoSQL selection,
- programming language,
- framework,
- API endpoints,
- API provider,
- search engine,
- map/GIS technology,
- frontend components,
- CMS,
- scraping architecture,
- AI provider/model,
- automation platform,
- hosting architecture,
- deployment configuration,
- analytics implementation.

Those decisions require later specifications and/or ADRs.

---

## 33. Future Evolution

Future documentation may define:

- detailed program taxonomy,
- admission and intake governance,
- curriculum and course relationships,
- accreditation and recognition,
- fee and financial information,
- merit and eligibility,
- program comparison,
- program SEO specification,
- structured data,
- program search,
- data import,
- source monitoring,
- change detection,
- AI extraction and validation,
- APIs,
- implementation schemas,
- operational workflows.

These future areas must not retroactively weaken the identity, evidence, lifecycle, and historical principles established here without an explicit governed decision.

---

## 34. Approval State

This document is a **final candidate pending merge into `main`**.

Once merged into the repository's `main` branch, it becomes part of the approved project documentation unless superseded by a later governed decision, RFC, or ADR.
