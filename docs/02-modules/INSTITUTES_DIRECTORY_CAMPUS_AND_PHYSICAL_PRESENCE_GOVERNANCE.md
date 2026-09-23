# Institutes Directory — Campus and Physical Presence Governance

## Document Information

| Field | Value |
| --- | --- |
| Document ID | MOD-008 |
| Title | Institutes Directory — Campus and Physical Presence Governance |
| Project | eStudent.pk — Institutes & Education Directory |
| Repository | `estudent-listing` |
| Status | Final candidate — pending merge into `main` |
| Documentation language | English |
| Scope | Technology-neutral governance of campuses, branches, physical sites, virtual delivery, campus lifecycle, and institution-to-campus relationships |
| Depends On | FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-001, MOD-002, MOD-003, MOD-004, MOD-005, MOD-006, MOD-007 |
| Target branch | `main` |

This document defines the conceptual governance of campuses and physical educational presence within an institution.

It complements MOD-005 for geography and MOD-007 for institution identity. It does not amend or override existing approved documents.

---

## 1. Purpose

An institution may operate through one or many campuses, branches, teaching sites, administrative offices, affiliated locations, temporary facilities, or online delivery arrangements.

The directory must distinguish the durable institution from its physical and operational presence so that:

- multi-campus institutions are represented correctly;
- campuses do not become accidental duplicate institutions;
- location changes remain historically traceable;
- programs can be associated with the appropriate place;
- campus-specific information remains distinguishable;
- virtual and distance delivery are not incorrectly assigned physical geography;
- future search, maps, nearby discovery, SEO, APIs, and AI can consume reliable campus information.

---

## 2. Core Campus Principles

### MOD-CAMP-001 — Campus is distinct from institution identity

A campus is a location or operational presence associated with an institution.

A campus does not normally constitute a separate institution merely because it has:

- a separate address;
- a local name;
- a different phone number;
- separate facilities;
- different programs;
- a separate website section.

Institution identity remains governed by MOD-007.

### MOD-CAMP-002 — Campus is distinct from geography

A campus is an institutional concept.

Geographic entities describe where the campus is located.

The campus must not be conflated with:

- city;
- district;
- locality;
- postal area;
- administrative boundary.

MOD-005 governs geographic representation.

### MOD-CAMP-003 — Campus is distinct from a building

A campus may occupy:

- one building;
- multiple buildings;
- a defined educational site;
- a larger university estate;
- multiple nearby facilities treated as one operational campus.

A building, block, office, hostel, laboratory, or administrative facility should not automatically become a separate campus.

---

## 3. Types of Physical Presence

The directory should conceptually distinguish physical presence types such as:

- Main or principal campus;
- constituent campus;
- regional campus;
- branch campus;
- satellite campus;
- teaching centre;
- study centre;
- extension centre;
- temporary teaching site;
- administrative site;
- examination centre;
- research or specialized site;
- other institutionally recognized physical presence.

These categories are conceptual and should be governed by evidence and institutional context rather than by naming alone.

---

## 4. Campus Identity

### MOD-CAMP-004 — Campus identity should be stable

Once a distinct campus is established, its identity should remain stable across ordinary changes to:

- address;
- facilities;
- programs;
- contact details;
- geographic classification;
- campus name;
- operational status.

A relocation does not automatically create a new campus identity.

Where a genuinely new independent campus is established, it should receive a distinct campus identity.

---

## 5. Campus Naming

A campus may have:

- official name;
- common name;
- local name;
- historical name;
- abbreviation;
- transliteration variants;
- parent-institution naming;
- geographic descriptor.

Campus names should be temporally scoped where necessary.

A local nickname must not automatically replace the official campus name.

---

## 6. Main, Principal and Other Campuses

An institution may identify one location as:

- main campus;
- principal campus;
- headquarters;
- central campus;
- city campus;
- administrative headquarters.

The directory should preserve the institution's own terminology where relevant while maintaining a separate conceptual distinction between:

- institutional identity;
- campus identity;
- administrative importance;
- physical location.

No single label should be assumed universally across all institutions.

---

## 7. Campus Address and Contact Information

Campus-specific information may include:

- physical address;
- postal address;
- postal code;
- telephone numbers;
- email addresses;
- official webpage;
- admissions contact;
- campus office contact;
- coordinates.

These attributes must remain distinguishable from institution-wide contact information.

Campus information should not automatically overwrite institution-level information.

---

## 8. Campus and Geography

Each physical campus should be associated with the most specific verified geographic context supported by evidence.

This may include:

- country;
- province or territory;
- division;
- district;
- tehsil/subdivision;
- city/municipality;
- locality/area;
- physical address;
- postal information;
- coordinates.

The directory must support partial geographic knowledge without guessing missing levels.

MOD-005 remains the governing specification for geographic hierarchy, temporal geography, aliases, coordinates, and geographic uncertainty.

---

## 9. Multiple Campuses in One City

An institution may operate multiple campuses in the same city.

They should remain distinct when they have materially distinct:

- physical sites;
- campus identities;
- services;
- programs;
- contacts;
- operating contexts.

The city must not be used as a substitute for campus identity.

---

## 10. Multiple Institutions at One Location

Different institutions may legitimately operate from the same physical address or complex.

Examples may include:

- institutions sharing a building;
- affiliated institutions;
- colleges operating within university premises;
- separate organizations using a common education complex.

Shared location is not sufficient evidence that two institution identities should be merged.

---

## 11. Campus Lifecycle

A campus may conceptually move through states such as:

1. Candidate;
2. Under Review;
3. Verified;
4. Active;
5. Temporarily Closed;
6. Relocating;
7. Inactive;
8. Permanently Closed;
9. Historical;
10. Superseded;
11. Reopened.

These are conceptual states only. Exact transitions require later operational specification.

---

## 12. Campus Opening

A new campus should require evidence establishing:

- relationship to the institution;
- campus identity;
- physical or operational location;
- applicable effective date;
- supporting source/evidence;
- verification state.

An announcement of a planned campus should not automatically be treated as an active campus.

---

## 13. Planned and Announced Campuses

The directory should distinguish:

- proposed campus;
- announced campus;
- under construction;
- operational campus.

Public representations must not imply that a planned or announced location is already operational.

Exact publication rules require later content/UI specifications.

---

## 14. Campus Relocation

When a campus moves:

- the campus identity normally remains stable;
- the former location becomes historical;
- the new location becomes current after the applicable effective point;
- geographic associations are updated;
- source/evidence and verification history are retained.

A relocation must not silently erase the former location.

If the old site continues operating as a distinct campus, the situation requires separate campus-identity assessment.

---

## 15. Campus Split or Consolidation

A campus may be:

- split into multiple campuses;
- consolidated with another campus;
- absorbed into another campus;
- converted into a specialized centre;
- closed while the institution continues elsewhere.

Such events should preserve historical relationships and effective periods.

A consolidation must not destroy historical campus identity.

---

## 16. Temporary Facilities

Institutions may use temporary premises during:

- construction;
- disaster recovery;
- relocation;
- renovation;
- emergency operations;
- institutional expansion.

Temporary facilities should be distinguished from permanent campuses where the evidence supports that distinction.

The temporary status should not be hidden by presenting the facility as a permanent campus.

---

## 17. Administrative Sites and Non-Teaching Locations

An institution may maintain:

- registrar offices;
- admission offices;
- administrative headquarters;
- liaison offices;
- research centres;
- hostels;
- laboratories;
- libraries;
- examination centres.

Not every institutional site is a teaching campus.

The directory should avoid converting every physical facility into a campus.

---

## 18. Virtual, Online and Distance Delivery

An institution may provide programs through:

- online delivery;
- distance education;
- hybrid delivery;
- distributed teaching;
- partner locations.

A virtual delivery mode is not itself a physical campus.

A program offering delivered online or across multiple locations should not be forced into one physical campus relationship merely to simplify the directory.

This preserves the distinction established by MOD-005.

---

## 19. Program Relationship

Programs and program offerings may be:

- campus-specific;
- offered at multiple campuses;
- institution-wide;
- online;
- distance-based;
- delivered through a combination of locations.

Campus association must therefore be flexible enough to represent the actual delivery context.

Program and Program Offering governance will be defined in later specifications.

---

## 20. Campus-Specific Attributes

Some information belongs specifically to a campus, such as:

- campus address;
- campus contact;
- campus facilities;
- campus operating hours;
- campus-specific programs;
- campus-specific admissions;
- campus-specific coordinates;
- campus-specific services.

Such attributes should not automatically be promoted to institution-wide facts.

---

## 21. Campus Evidence and Verification

Under MOD-006, campus assertions require identifiable evidence.

Evidence may include:

- official institutional campus pages;
- official prospectuses;
- regulatory records;
- government records;
- accreditation/inspection records;
- institutional communications;
- geographic evidence;
- field verification;
- other corroborating evidence.

Campus verification should consider:

- identity match;
- exact physical site;
- operational status;
- effective period;
- source authority;
- conflicting evidence;
- relationship to the parent institution.

---

## 22. Campus Duplicate Detection

Potential duplicate campuses may result from:

- address spelling variations;
- renamed campuses;
- transliteration;
- old and new web pages;
- map duplicates;
- branch naming differences;
- temporary versus permanent facilities.

Duplicate resolution must be evidence-based.

Two campuses at similar addresses must not be merged automatically.

---

## 23. Campus and Institution Status

Institution status and campus status are independent.

Examples:

- Institution active; one campus closed.
- Institution active; one campus temporarily closed.
- Institution active; new campus under construction.
- Institution inactive; historical campuses retained.
- Institution merged; historical campuses preserved.
- Institution active; online programs with no physical campus relationship.

A campus state must therefore not automatically determine the institution's overall status.

---

## 24. Campus and Legal Identity

A campus does not normally have independent legal identity merely because it is geographically separate.

Where a legally separate educational entity operates at a related location, it should be represented as its own institution if the evidence establishes a distinct identity.

This prevents physical proximity from becoming an identity rule.

---

## 25. Campus History

Historical campus records should preserve:

- previous names;
- previous locations;
- opening and closure periods;
- relocation events;
- institutional relationships;
- evidence;
- verification history.

Historical campus information should remain distinguishable from current operational information.

---

## 26. Campus URL Relationship

Campus discovery and profile representations must remain subordinate to the permanent institution identity and the URL governance of MOD-002.

A campus may have a future public representation where useful, but campus URLs must not replace the canonical institution URL.

An institution with multiple campuses must retain one canonical institution identity and profile.

Specific campus routing and URL patterns require later SEO and implementation specifications.

---

## 27. Search and Nearby Discovery

Campus information may support:

- city searches;
- locality searches;
- nearby discovery;
- map representations;
- campus-specific filters;
- program-location discovery.

These are derived discovery functions.

Search and proximity results must resolve back to governed campus and institution information.

---

## 28. Maps and Coordinates

Campus coordinates are governed evidence about physical location.

Maps, pins, boundaries, route information, and proximity results are derived representations.

A mapping service must never silently become the authoritative source of campus identity or institutional status.

No map provider or geospatial technology is approved by this document.

---

## 29. AI-Assisted Campus Discovery

AI may assist with:

- detecting possible new campuses;
- identifying relocation;
- comparing addresses;
- finding duplicate campus records;
- extracting campus information;
- detecting closure or opening signals;
- suggesting geographic assignments.

AI output is candidate information.

It requires supporting evidence and governed verification before becoming canonical.

---

## 30. Campus Change Events

Material campus events should conceptually include:

- opening;
- announcement;
- relocation;
- temporary closure;
- reopening;
- permanent closure;
- renaming;
- consolidation;
- split;
- conversion to another site type;
- change in institutional relationship.

Event history must remain traceable.

---

## 31. Campus Data Quality Requirements

Campus governance should support:

1. **Identity stability**
2. **Institution relationship integrity**
3. **Physical-location accuracy**
4. **Geographic hierarchy integrity**
5. **Temporal correctness**
6. **Source provenance**
7. **Verification**
8. **Non-duplication**
9. **Campus-specific attribute separation**
10. **Historical continuity**
11. **Program-location correctness**
12. **Derived-representation consistency**

Quality must not be reduced to a single numerical score.

---

## 32. Special Cases

The model should remain capable of representing:

- institutions with no physical campus;
- institutions with many campuses;
- institutions sharing campuses;
- institutions operating in rented premises;
- institutions temporarily relocated;
- institutions with specialized research sites;
- institutions with examination-only centres;
- institutions with administrative offices separate from teaching locations;
- online and distance institutions;
- institutions operating across jurisdictions.

These cases require evidence-based classification rather than rigid assumptions.

---

## 33. Relationship to Existing Approved Documents

### FND-001 — Master Project Index

MOD-008 supports institution profiles, location discovery, maps, nearby search, programs, SEO, verification, APIs, AI, and long-term data evolution.

### FND-002 — Project Constitution

MOD-008 applies durable boundaries, provenance, backward compatibility, historical traceability, and documentation-first governance.

### ARC-001 — System Architecture

MOD-008 defines campus responsibilities without selecting implementation technology.

### ARC-002 — Domain Model and Bounded Contexts

MOD-008 reinforces separation between institution, campus, geography, program, and derived representations.

### ARC-003 — Data Architecture and Lifecycle

MOD-008 establishes campus lifecycle and historical requirements for future physical data architecture.

### MOD-002 — Information Architecture and URLs

MOD-008 preserves the canonical institution URL while allowing future governed campus discovery surfaces.

### MOD-003 — Information Specification

MOD-008 provides detailed campus governance for relationships, lifecycle, temporal context, evidence, and canonical publication.

### MOD-005 — Geography and Location

MOD-008 consumes MOD-005's geographic hierarchy, location, coordinates, historical geography, and uncertainty principles.

### MOD-006 — Data Source, Evidence and Verification Governance

Campus identity, location, lifecycle, and change assertions follow MOD-006 source, evidence, verification, freshness, and auditability rules.

### MOD-007 — Institution Identity, Lifecycle and Status Governance

MOD-008 establishes campus as a subordinate but independently governed physical/operational concept associated with the institution.

MOD-008 does not amend or override these documents.

---

## 34. Explicit Non-Approval of Implementation

This document does not approve:

- database schemas;
- campus ID formats;
- GIS technology;
- map providers;
- geocoding providers;
- routing engines;
- spatial indexes;
- programming languages or frameworks;
- APIs;
- frontend components;
- scraping/crawling systems;
- AI providers/models;
- automated campus creation;
- deployment architecture.

All implementation decisions require later documentation and approval.

---

## 35. Current Status

**Proposed for approval on merge:**

- Campus as a distinct concept from institution identity and geography.
- Campus identity stability and lifecycle.
- Multi-campus and shared-location principles.
- Campus opening, relocation, closure, reopening, split, and consolidation governance.
- Physical presence type distinctions.
- Separation of teaching campuses from administrative and specialized sites.
- Virtual and distance delivery separation.
- Campus-specific attributes and program relationships.
- Campus evidence, verification, duplicate, historical, search, map, and AI principles.

**Remains unapproved implementation scope:**

- Physical data structures;
- GIS and spatial technology;
- campus identifier implementation;
- APIs;
- UI;
- automated ingestion;
- AI providers/models;
- map/geocoding providers;
- deployment and operational tooling.

---

## 36. Future Evolution

Future specifications may define:

1. Campus identity and duplicate-resolution workflow.
2. Campus lifecycle transition matrix.
3. Campus type/classification matrix.
4. Institution-to-campus relationship rules.
5. Program-to-campus offering rules.
6. Campus-specific SEO and URL policy.
7. Geocoding and coordinate verification policy.
8. Map and nearby-discovery governance.
9. Campus submission and correction workflow.
10. Campus monitoring and change detection.
11. Historical campus archival policy.
12. Physical-site versus campus modeling rules.

Material changes must follow the project's documented change-management process.
