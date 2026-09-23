# Institutes Directory — Institution Identity, Lifecycle and Status Governance

## Document Information

| Field | Value |
| --- | --- |
| Document ID | MOD-007 |
| Title | Institutes Directory — Institution Identity, Lifecycle and Status Governance |
| Project | eStudent.pk — Institutes & Education Directory |
| Repository | `estudent-listing` |
| Status | Final candidate — pending merge into `main` |
| Documentation language | English |
| Scope | Technology-neutral institution identity, lifecycle, naming, status, continuity, and duplicate-governance specification |
| Depends On | FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-001, MOD-002, MOD-003, MOD-004, MOD-005, MOD-006 |
| Target branch | `main` |

This document defines how the directory conceptually identifies an educational institution as a durable entity across names, locations, campuses, status changes, mergers, splits, closures, and historical periods.

It complements MOD-003 and MOD-006 and does not amend or override existing approved documents.

---

## 1. Purpose

Institution identity is the foundation on which campuses, programs, offerings, search, SEO, APIs, AI representations, and historical records depend.

This specification establishes durable rules for:

- stable institution identity;
- official and alternate names;
- historical names and aliases;
- institution lifecycle;
- operational and legal status;
- mergers, splits, successors, and predecessors;
- relocation versus identity change;
- duplicate detection and resolution;
- identity verification and evidence;
- relationship between institution and campus;
- canonical institution representation;
- historical continuity;
- future AI-assisted identity matching.

The objective is to preserve identity continuity without incorrectly merging distinct institutions or creating unnecessary duplicate identities.

---

## 2. Core Identity Principles

### MOD-ID-001 — Institution identity is independent of name

An institution is not defined solely by its current name.

A name may change while the underlying institution remains the same. Conversely, two institutions may use similar or identical names while remaining distinct entities.

### MOD-ID-002 — Institution identity is independent of location

Moving a campus or changing an address does not automatically create a new institution.

Geographic information remains governed separately under MOD-005.

### MOD-ID-003 — Institution identity is independent of classification

Changing institution type, category, affiliation, accreditation, or educational classification does not automatically create a new identity.

Classification is governed under MOD-004.

### MOD-ID-004 — Identity must remain stable

Once an institution has been established as a canonical directory entity, its identity should remain stable across ordinary name, location, status, classification, program, and source changes.

Public identifiers should not be casually reused for another institution.

---

## 3. Conceptual Institution Entity

The institution represents a durable educational organization or legally/operationally meaningful educational entity recognized by the directory's scope.

Conceptually:

[
Institution ightarrow {Names, Status, Campuses, Programs, Evidence, History}
]

The institution is distinct from:

- a campus;
- a building;
- a program;
- a program offering;
- a geographic place;
- a source;
- a website domain;
- a ranking;
- a search result;
- an AI-generated representation.

The exact physical data model remains unapproved.

---

## 4. Institution Identity Evidence

Under MOD-006, identity assertions require evidence.

Identity evidence may include:

- official institutional records;
- statutory or regulatory records;
- official government notifications;
- recognized accreditation records;
- formal affiliation records;
- institutional publications;
- historical institutional records;
- authoritative directories;
- other corroborating evidence.

Identity evidence must be evaluated for:

- exact entity match;
- authority;
- effective period;
- naming context;
- jurisdiction;
- relationship to predecessor or successor entities;
- contradiction from other credible sources.

---

## 5. Identity States

An institution may conceptually move through states such as:

1. **Candidate** — discovered but identity not yet established.
2. **Under Review** — evidence is being evaluated.
3. **Verified** — sufficient evidence establishes the canonical identity.
4. **Active** — currently operating within the directory's applicable scope.
5. **Inactive** — identity remains valid but current operations are not established as active.
6. **Temporarily Closed** — operations are paused or suspended with an expectation or possibility of continuation.
7. **Merged** — institution has become part of another entity through a documented merger or equivalent legal/organizational event.
8. **Split / Reorganized** — a documented event has produced successor or reorganized entities.
9. **Closed / Ceased** — institution has ceased operation or ceased to exist as an operating entity.
10. **Superseded** — a historical identity has been replaced by a successor identity for the relevant scope.
11. **Rejected** — candidate identity was determined not to represent a legitimate distinct institution under project scope.

These are conceptual states. Exact implementation transitions require later specification.

---

## 6. Status versus Identity

### MOD-ID-005 — Status changes do not automatically create new identity

An institution may change:

- operating status;
- recognition status;
- affiliation;
- accreditation;
- administrative control;
- classification;
- ownership or governance;
- legal name.

Each change must be evaluated independently from identity continuity.

A status change should create a new institution only when evidence establishes a genuinely distinct entity.

---

## 7. Official Names

The directory should distinguish:

- current official name;
- legal/statutory name where different;
- commonly used name;
- short name;
- acronym;
- historical official names;
- translated names;
- transliteration variants;
- recognized aliases.

Names should have temporal applicability where necessary.

A name used in an old source must not automatically be treated as the current canonical name.

---

## 8. Name Changes

### MOD-ID-006 — Name change requires continuity assessment

When an institution changes its name, the directory should determine whether:

1. the same underlying institution continued;
2. the legal entity changed;
3. a merger or reorganization occurred;
4. a successor institution was established;
5. the available evidence is insufficient.

A documented name change should preserve the historical name as an alias or historical identity representation where appropriate.

The old name should remain discoverable for historical searches without becoming the current canonical name.

---

## 9. Duplicate Institution Detection

Duplicate detection must distinguish:

- spelling variation;
- abbreviation;
- transliteration difference;
- campus naming;
- department naming;
- parent institution naming;
- historical name;
- genuinely separate institutions.

Potential duplicate signals may include:

- official registration identifiers where available;
- legal name;
- authoritative address;
- governing body;
- founding/history;
- official domain;
- regulatory affiliation;
- campus relationships;
- program and accreditation context;
- source evidence.

No single matching signal should automatically merge institutions.

---

## 10. Duplicate Resolution

When two records may represent the same institution:

1. preserve both candidate records while under review;
2. compare identity evidence;
3. determine whether they represent one entity or multiple entities;
4. document the resolution;
5. preserve historical aliases and references;
6. redirect or reconcile derived representations where appropriate;
7. never silently delete an established identity without a traceable reason.

If evidence remains inconclusive, the directory should preserve uncertainty rather than perform an irreversible merge.

---

## 11. Institution versus Campus

### MOD-ID-007 — Campus is subordinate to institution identity

A campus represents a physical or operational location associated with an institution.

An institution may have:

- one campus;
- multiple campuses;
- regional campuses;
- specialized campuses;
- temporary locations;
- historical locations;
- online or distance delivery without a physical campus for a particular offering.

A campus should not automatically become a separate institution merely because it has a distinct address or local name.

Campus governance is connected to MOD-005.

---

## 12. Relocation

Moving an institution or campus should normally preserve institution identity.

A relocation may involve:

- historical location closure;
- new active location;
- changed geographic associations;
- changed contact information;
- changed campus metadata.

The relocation event and its effective period should remain historically traceable.

If evidence indicates that the former site and new site represent separate institutional entities, identity review is required rather than assuming relocation.

---

## 13. Multi-Campus Identity

An institution operating across multiple locations should retain one institution identity when the evidence establishes a common underlying organization.

Each campus may have distinct:

- address;
- geography;
- coordinates;
- contact details;
- facilities;
- program offerings;
- operating status.

This separation prevents multi-campus institutions from being incorrectly duplicated as unrelated institutions.

---

## 14. Mergers

### MOD-ID-008 — Merger continuity must be explicit

A merger may result in:

- continuation of one existing institution;
- creation of a new successor institution;
- absorption of one institution by another;
- broader legal or organizational reorganization.

The directory must not assume one universal merger rule.

The evidence should establish:

- predecessor institutions;
- successor institution where applicable;
- effective date;
- legal/organizational nature of the event;
- continuity or discontinuity of identity;
- historical references.

Historical identities should remain traceable even when they are no longer active.

---

## 15. Institutional Splits and Reorganizations

An institution may be divided, reorganized, or transformed into multiple successor entities.

The directory should preserve:

- predecessor identity;
- successor identities;
- effective periods;
- documented relationship;
- relevant historical names;
- evidence supporting the relationship.

A split must not be modeled merely as deleting the old institution and creating unrelated new records.

---

## 16. Acquisition and Administrative Control Changes

Changes in ownership, trusteeship, administration, sponsorship, affiliation, or governing authority do not automatically imply a new institution.

The identity decision must consider the legal and organizational facts and the continuity of the educational entity.

Where the governing organization changes but the institution remains the same, the relationship should be represented as a historical change rather than an identity replacement.

---

## 17. Closure and Reopening

### MOD-ID-009 — Closure does not erase historical identity

When an institution ceases operations:

- its historical identity should remain preserved;
- its previous names and campuses should remain traceable;
- its closure evidence and effective period should be retained where appropriate;
- active public discovery should reflect its current status.

If the institution later reopens, the directory should determine whether the same identity resumed or a genuinely new entity was established.

Reopening must not automatically create a duplicate institution.

---

## 18. Temporary Closure and Suspension

Temporary closure, suspension, inactivity, or interruption of operations should be distinguishable from permanent cessation where evidence supports that distinction.

The directory should preserve:

- start date where known;
- expected or confirmed status;
- evidence;
- review requirements;
- reopening or continuation information.

Exact public display rules require later UI/content specifications.

---

## 19. Successor and Predecessor Relationships

The directory should conceptually support relationships such as:

- predecessor of;
- successor of;
- merged into;
- absorbed by;
- formed from;
- split into;
- renamed from;
- reorganized as.

These relationships are historical and must not be confused with ordinary parent/child organization structures.

---

## 20. Identity History

Institution history should support reconstruction of:

- former names;
- former statuses;
- former campuses;
- major organizational changes;
- predecessor/successor relationships;
- source and evidence;
- effective periods.

Historical information should remain attributable and should not be presented as current merely because it is preserved.

---

## 21. Canonical Institution Representation

### MOD-ID-010 — One canonical identity per distinct institution

For each distinct institution established under project scope, the directory should maintain one canonical institution identity.

Multiple public representations may exist for search, geography, taxonomy, programs, or historical discovery, but they should resolve to the appropriate canonical institution.

This principle supports:

- stable URLs;
- SEO continuity;
- duplicate prevention;
- program relationships;
- campus relationships;
- API continuity;
- AI knowledge integrity.

---

## 22. Canonical URL Relationship

Institution identity must remain aligned with MOD-002.

The canonical institution profile remains conceptually anchored under:

`/institutes/{institution-slug}/`

The slug is a public representation, not the identity itself.

If an institution's name changes, the public URL may require compatibility handling, but the underlying institution identity must remain stable unless a formal identity decision establishes discontinuity.

URL migration rules remain governed by MOD-002 and the project's change-management process.

---

## 23. Identifier Stability

Internal or public identifiers should be designed for long-term stability.

An identifier assigned to an institution should not be recycled for a different institution after closure, merger, rename, or other lifecycle event.

If a new institution is created, it should receive a distinct identity even when it occupies the same physical location as a predecessor.

---

## 24. Identity versus Program

Programs do not define institution identity.

An institution may:

- add programs;
- discontinue programs;
- change program names;
- change program delivery modes;
- offer programs at multiple campuses.

Such changes do not normally create a new institution.

Program identity and offering governance require separate future specifications.

---

## 25. Identity versus Accreditation and Recognition

Recognition, accreditation, affiliation, licensing, and regulatory status are important institutional attributes but are not automatically institution identity.

The directory must preserve:

- relevant authority;
- status;
- effective period;
- evidence;
- verification state.

A change in recognition or accreditation status must not silently create or delete an institution identity.

---

## 26. Identity versus Geography

Geography provides contextual location information.

Administrative boundary changes, city renaming, district creation, or postal changes must not automatically create a new institution.

MOD-005 governs geographic continuity and temporal interpretation.

---

## 27. Identity Verification

Identity verification should consider:

- exact entity match;
- authoritative records;
- legal/statutory identity;
- institutional continuity;
- historical names;
- location history;
- campus relationships;
- governing authority;
- predecessor/successor evidence;
- conflicting sources.

Identity confidence may assist review, but confidence is not a substitute for verification under MOD-006.

---

## 28. AI-Assisted Identity Matching

AI may assist with:

- duplicate candidate detection;
- name normalization;
- transliteration matching;
- historical-name matching;
- source comparison;
- possible merger detection;
- possible successor detection;
- anomaly identification.

AI-generated identity matches are candidate assessments.

They must not automatically merge, split, rename, close, or reactivate canonical institution identities without governed evidence and appropriate review.

---

## 29. Identity Change Events

Material identity-related events should be represented as events or historical changes conceptually, including:

- name change;
- legal status change;
- merger;
- absorption;
- split;
- reorganization;
- relocation;
- closure;
- reopening;
- successor formation;
- major governing-entity change.

Event modeling and physical implementation require later specification.

---

## 30. Data Quality Requirements

Institution identity governance should support:

1. **Uniqueness** — distinct institutions are not unnecessarily duplicated.
2. **Stability** — identity survives ordinary changes.
3. **Traceability** — identity decisions are evidence-backed.
4. **Historical continuity** — past identities remain reconstructable.
5. **Temporal correctness** — changes have appropriate time context.
6. **Source provenance** — identity claims have supporting evidence.
7. **Conflict visibility** — uncertain cases remain identifiable.
8. **Non-recycling** — identifiers are not reassigned.
9. **Relationship integrity** — predecessor/successor links remain coherent.
10. **Derived consistency** — search, SEO, AI, and other representations resolve to the correct canonical identity.

Quality must not be reduced to a single numerical score.

---

## 31. Identity Review and Governance

Identity-affecting actions require stronger review than routine descriptive edits.

Examples include:

- merging duplicate institutions;
- splitting one institution into several identities;
- declaring an institution closed;
- assigning a successor;
- changing legal identity;
- resolving ambiguous institutional names;
- replacing a canonical identity.

Such actions should preserve evidence, rationale, reviewer/process context, and historical state.

---

## 32. Security and Abuse Considerations

Identity data may be targeted by:

- duplicate submissions;
- fraudulent institution claims;
- impersonation;
- malicious redirects;
- misleading name changes;
- unauthorized edits.

Future implementation must use appropriate access control, auditability, moderation, evidence requirements, and change protection.

This document does not approve specific authentication or moderation mechanisms.

---

## 33. Relationship to Existing Approved Documents

### FND-001 — Master Project Index

MOD-007 supports the project's long-term requirement for stable institutional profiles, data quality, search, SEO, verification, historical continuity, and future AI capabilities.

### FND-002 — Project Constitution

MOD-007 applies the constitution's principles of durable identifiers, provenance, backward compatibility, documentation-first development, and historical traceability.

### ARC-001 — System Architecture

MOD-007 defines institution identity responsibilities without selecting implementation technologies.

### ARC-002 — Domain Model and Bounded Contexts

MOD-007 reinforces institution identity as a distinct domain concept separated from campus, geography, programs, taxonomy, and derived representations.

### ARC-003 — Data Architecture and Lifecycle

MOD-007 establishes identity lifecycle and historical requirements that future physical data architecture must support.

### MOD-003 — Information Specification

MOD-007 provides detailed identity and lifecycle governance for MOD-003's stable institution identity, naming, status, relationships, and temporal principles.

### MOD-004 — Taxonomy and Classification

Institution identity remains separate from educational classification and taxonomy.

### MOD-005 — Geography and Location

Institution identity remains separate from physical and administrative geography, including relocation and boundary changes.

### MOD-006 — Data Source, Evidence and Verification Governance

Identity assertions, lifecycle events, and duplicate decisions require source, evidence, verification, and auditability under MOD-006.

MOD-007 does not amend or override these documents.

---

## 34. Explicit Non-Approval of Implementation

This document does not approve:

- database tables or schemas;
- identifier formats or generated IDs;
- application code;
- matching algorithms;
- deduplication algorithms;
- AI models;
- search implementation;
- URL routing implementation;
- redirect implementation;
- APIs;
- moderation dashboards;
- authentication;
- workflow engines;
- scraping systems;
- deployment architecture.

All such decisions require later appropriate documentation and approval.

---

## 35. Current Status

**Proposed for approval on merge:**

- Stable institution identity principles.
- Separation of identity from name, geography, taxonomy, campus, program, and status.
- Institution lifecycle concepts.
- Name and alias governance.
- Duplicate detection and resolution principles.
- Merger, split, successor, predecessor, relocation, closure, and reopening principles.
- Historical continuity and identifier stability.
- Canonical identity and URL relationship.
- Identity verification and AI-assisted matching boundaries.

**Remains unapproved implementation scope:**

- Physical identity storage;
- identifier-generation algorithms;
- matching/deduplication implementation;
- lifecycle workflow engines;
- AI providers/models;
- APIs;
- UI;
- moderation systems;
- authentication;
- deployment and operational tooling.

---

## 36. Future Evolution

Future specifications may define:

1. Institution identity source/authority matrix.
2. Identity review workflow.
3. Duplicate detection and merge policy.
4. Institution lifecycle transition matrix.
5. Legal entity versus educational institution modeling.
6. Accreditation and recognition status governance.
7. Historical identity and archival policy.
8. Canonical identifier implementation.
9. Program and campus lifecycle specifications.
10. Identity-related API contracts.
11. AI identity-resolution governance.
12. Public status and historical-display rules.

Material changes must follow the project's documented change-management process.
