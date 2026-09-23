# MOD-014 — Institutes Directory: Accreditation, Recognition and Regulatory Status Governance

**Document ID:** MOD-014  
**Title:** Institutes Directory — Accreditation, Recognition and Regulatory Status Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-004, MOD-006, MOD-007, MOD-008, MOD-009, MOD-010, MOD-011, MOD-012, MOD-013  
**Related Documents:** MOD-006, MOD-007, MOD-009, MOD-010  
**Next Documents:** Reviews and reputation governance, rankings and external signals governance, data quality and operational governance

---

## 1. Purpose

This document governs how the directory represents accreditation, recognition, affiliation, authorization, licensing, regulatory status and related institutional or program-level claims.

These concepts must remain separate from institution identity, program identity and directory publication status.

A listing in eStudent.pk does not itself establish that an institution or program is accredited, recognized, licensed or authorized.

---

## 2. Core Principle

**Recognition, accreditation, affiliation, authorization and directory verification are different concepts.**

The platform must never infer one from another without evidence.

An institution may be recognized but a particular program may have separate accreditation requirements.

A program may be accredited for a defined period or campus while another offering is not.

---

## 3. Conceptual Status Types

The platform may represent, where evidence exists:

- recognized institution;
- authorized institution;
- licensed institution;
- accredited institution;
- accredited program;
- affiliated institution;
- affiliated program;
- professional recognition;
- regulatory approval;
- charter/establishment status;
- provisional status;
- expired status;
- suspended status;
- withdrawn status;
- status unknown.

These are governed concepts, not implementation enums.

---

## 4. Scope

A recognition or accreditation assertion may apply to:

- institution;
- campus;
- program;
- program offering;
- qualification;
- department;
- discipline;
- professional category;
- defined time period.

Scope must be retained.

An institution-level recognition assertion must not automatically become a program-level accreditation assertion.

---

## 5. Authority and Source

Each material status claim should identify the relevant authority or source.

Evidence may include:

- official regulatory authority;
- accreditation body;
- chartering authority;
- professional council;
- official institutional publication;
- recognized authoritative registry;
- historical official record.

Third-party directories may assist discovery but must not silently become the authoritative source of accreditation status.

---

## 6. Effective Period

Recognition and accreditation may be time-bound.

The platform should preserve:

- effective date;
- expiry date where applicable;
- renewal period;
- suspension period;
- withdrawal date;
- historical status.

An old accreditation record must not automatically be presented as current.

---

## 7. Program-Level Accreditation

Where program accreditation exists, the directory should distinguish:

- accrediting body;
- program;
- campus where relevant;
- qualification;
- accreditation status;
- effective period;
- conditions or limitations where officially stated.

Program accreditation must not be inferred from institutional recognition.

---

## 8. Campus Scope

Some approvals may be campus-specific.

The directory must support evidence that an approval applies to:

- all campuses;
- a named campus;
- selected campuses;
- a defined delivery mode;
- a specific program location.

Campus scope must follow MOD-008.

---

## 9. Affiliation

Affiliation is distinct from accreditation.

An institution may be affiliated with another university, board, authority or organization without that relationship itself proving accreditation of every program.

Affiliation should include:

- affiliated entity;
- scope;
- effective period;
- source;
- status.

---

## 10. Recognition vs Directory Verification

The directory's own verification state under MOD-006 must not be represented as government or professional recognition.

For example:

**eStudent Verified**

must not be presented as:

**Government Recognized**

unless the latter is independently evidenced.

---

## 11. Conflicting Status Claims

Conflicts may arise between institutional claims and regulatory/accreditation records.

The platform must preserve:

- source assertions;
- dates;
- scope;
- authority;
- conflict state;
- resolution status.

The platform must not silently convert a disputed claim into a verified fact.

---

## 12. Status Changes

Material events may include:

- accreditation granted;
- accreditation renewed;
- accreditation expired;
- accreditation suspended;
- accreditation withdrawn;
- recognition granted;
- recognition changed;
- affiliation established;
- affiliation ended;
- authorization changed.

Historical status should be preserved where useful and supported.

---

## 13. Unknown Status

Unknown or unavailable status must remain distinct from negative status.

**Unknown accreditation information ≠ Not accredited.**

Likewise:

**No evidence found ≠ evidence of absence.**

A negative claim requires appropriate evidence.

---

## 14. Student-Facing Representation

Public pages may summarize verified status information, but should preserve:

- authority;
- scope;
- date;
- program/campus context;
- source.

The platform should avoid broad statements when the underlying approval is narrower.

---

## 15. AI-Assisted Regulatory Intelligence

AI may assist with:

- finding possible regulatory records;
- extracting accreditation details;
- detecting expiry dates;
- comparing current and historical records;
- flagging conflicting claims;
- identifying records requiring review.

AI must not independently declare an institution or program accredited, recognized or licensed.

Canonical regulatory status requires governed evidence.

---

## 16. Search and SEO

Recognition and accreditation may support search filters and public summaries.

However, search indexes, SEO text and AI summaries remain derived representations.

They must not become the authoritative regulatory record.

---

## 17. Data Quality

Status data should be evaluated for:

- authority;
- scope;
- temporal accuracy;
- program/campus alignment;
- evidence quality;
- freshness;
- completeness;
- conflict state;
- traceability.

---

## 18. Explicit Non-Approval of Implementation

This document does not approve:

- regulatory APIs;
- database schemas;
- crawlers;
- search technology;
- AI providers;
- frontend implementation;
- notification systems;
- hosting or deployment architecture.

---

## 19. Future Evolution

Future documents may define:

- accreditation taxonomy;
- regulatory source registry;
- professional-body mappings;
- expiry monitoring;
- automated verification workflows;
- accreditation search;
- alerts;
- implementation schemas and APIs.

---

## 20. Approval State

This document is a **final candidate pending merge into `main`**. Once merged, it becomes approved project documentation unless superseded by a governed decision.
