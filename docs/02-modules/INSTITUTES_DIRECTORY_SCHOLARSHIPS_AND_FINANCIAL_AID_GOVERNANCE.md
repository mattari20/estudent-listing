# MOD-013 — Institutes Directory: Scholarships and Financial Aid Governance

**Document ID:** MOD-013  
**Title:** Institutes Directory — Scholarships and Financial Aid Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-004, MOD-006, MOD-007, MOD-008, MOD-009, MOD-010, MOD-011, MOD-012  
**Related Documents:** MOD-006, MOD-009, MOD-010, MOD-011, MOD-012  
**Next Documents:** Accreditation and recognition governance, reviews and reputation governance, implementation specifications

---

## 1. Purpose

This document establishes the long-term conceptual governance for **Scholarships, Financial Aid, Fee Waivers, Grants and Other Student Financial Support** within the Institutes Directory.

The central principle is:

> **Scholarship and financial-aid information is optional enrichment and must never be a dependency for the core directory.**

An institution, program, offering or admission record remains valid and discoverable even when no scholarship information is available.

Missing scholarship data must not be interpreted as proof that an institution offers no scholarships.

---

## 2. Core Principle: Optional and Non-Blocking

The directory must continue functioning when:

- no scholarship data is available;
- only some scholarships are known;
- eligibility details are incomplete;
- scholarship deadlines are unknown;
- historical scholarship information exists but current information is unavailable;
- scholarship information differs by program or category;
- the institution does not publicly publish financial-aid information.

Missing scholarship information must never cause:

- institution removal,
- program removal,
- admission removal,
- search exclusion by default,
- invalid canonical URLs,
- or a claim that no financial aid exists.

---

## 3. Entity Boundaries

### 3.1 Scholarship

A defined financial-support opportunity with eligibility, benefit and other governed conditions.

### 3.2 Financial Aid

A broader category that may include scholarships, grants, waivers, loans, work-study or other approved forms of support.

### 3.3 Fee Waiver

A reduction or exemption from a defined fee.

### 3.4 Grant

Financial support provided under defined eligibility and award conditions.

### 3.5 Sponsorship

Financial support provided by an institution, organization, employer, government body or other sponsor.

### 3.6 Scholarship Offering

A specific availability of a scholarship during a defined period, program context or applicant category.

These concepts must not be collapsed into one generic “scholarship” field.

---

## 4. Scholarship Identity

A scholarship should have a stable conceptual identity independent of:

- annual application cycle,
- temporary deadline,
- webpage URL,
- benefit amount,
- current eligibility wording.

A scholarship may have multiple historical or future offerings.

A renamed scholarship should be evaluated for continuity rather than automatically treated as a new entity.

---

## 5. Scholarship Offering

A Scholarship Offering represents a specific availability of a scholarship under defined conditions.

Relevant dimensions may include:

- institution,
- program,
- campus,
- intake,
- academic year,
- applicant category,
- eligibility,
- benefit,
- application period,
- availability state.

This prevents annual or intake-specific records from unnecessarily duplicating the underlying scholarship identity.

---

## 6. Scholarship Lifecycle

Conceptual states may include:

- Candidate
- Under Review
- Announced
- Open
- Closing Soon
- Closed
- Awarded
- Suspended
- Cancelled
- Historical
- Superseded
- Rejected

These are governance concepts rather than implementation commitments.

---

## 7. Scholarship Scope

Scholarships may apply to:

- institution-wide students,
- a specific program,
- a specific campus,
- a discipline,
- a student category,
- a particular intake,
- new applicants,
- continuing students,
- postgraduate students,
- undergraduate students,
- international students,
- other officially defined groups.

The scope must be preserved.

An institution-wide scholarship should not be represented as automatically applicable to every program if official conditions say otherwise.

---

## 8. Eligibility Requirements

Scholarship eligibility may depend on:

- academic performance,
- admission status,
- financial need,
- domicile,
- category,
- program,
- level of study,
- gender or other officially defined eligibility category,
- disability or special status where officially applicable,
- extracurricular achievement,
- research criteria,
- continuing academic performance,
- other published requirements.

The directory must distinguish scholarship eligibility from ordinary program admission eligibility.

Meeting program eligibility does not imply scholarship eligibility.

---

## 9. Merit-Based vs Need-Based Support

The platform should distinguish conceptually between:

- merit-based support,
- need-based support,
- merit-cum-need support,
- category-based support,
- achievement-based support,
- research funding,
- institutional waiver,
- externally funded support.

A scholarship classification should be based on evidence rather than name alone.

---

## 10. Benefit Representation

Benefits may include:

- full tuition coverage,
- partial tuition reduction,
- fixed monetary award,
- percentage reduction,
- application-fee waiver,
- admission-fee waiver,
- stipend,
- hostel support,
- transport support,
- book allowance,
- research funding,
- living allowance,
- other defined benefits.

The platform must distinguish:

- maximum possible benefit,
- fixed benefit,
- percentage benefit,
- conditional benefit,
- estimated benefit.

It must not convert a partial benefit into “fully funded” without evidence.

---

## 11. Duration and Renewal

Scholarship support may be:

- one-time,
- one semester,
- one academic year,
- renewable,
- conditional on continued performance,
- limited to a defined number of semesters,
- dependent on continued funding.

The duration and renewal conditions must be preserved.

---

## 12. Application Period

Scholarships may have separate:

- announcement dates,
- opening dates,
- deadlines,
- selection dates,
- award dates,
- renewal dates.

Scholarship deadlines must not automatically be copied from admission deadlines.

Historical scholarship deadlines must remain historical.

---

## 13. Scholarship Availability vs Admission Availability

A program may have:

- admissions open but no known scholarship currently open;
- scholarship applications open before admission;
- scholarship available only after enrollment;
- scholarship information available without a current admission cycle.

These states must remain independent.

---

## 14. Financial Aid Beyond Scholarships

The directory may eventually represent:

- grants,
- bursaries,
- fee waivers,
- student loans,
- work-study,
- assistantships,
- research funding,
- sponsorships,
- government support,
- external funding.

Each financial-support type should have its own governed meaning.

---

## 15. Source and Evidence Governance

Scholarship and financial-aid information must follow MOD-006.

Evidence may include:

- official scholarship pages,
- official financial-aid offices,
- official prospectuses,
- official notices,
- official admission portals,
- government or regulatory sources where applicable,
- institution-submitted information,
- historical official publications.

Third-party listings may support discovery or corroboration but must not silently become canonical financial-aid truth.

---

## 16. Freshness and Temporal Validity

Scholarship information can change significantly by year or intake.

The platform should preserve:

- announcement date,
- effective academic year,
- intake,
- application period,
- award period,
- renewal period,
- superseded status.

An old scholarship should not automatically appear as a current opportunity.

---

## 17. Missing Scholarship Information

When scholarship information is unavailable, the platform should be able to represent states such as:

- Scholarship information not available.
- Current scholarship information not published.
- Financial-aid information requires verification.
- Historical scholarship information available; current information unavailable.

The platform must not say:

- “No scholarship available”

unless reliable evidence supports that conclusion.

---

## 18. Partial Scholarship Information

A scholarship record may have:

- name known,
- eligibility known,
- benefit unknown,
- deadline unknown.

The known information may still be useful.

Completeness should be assessed at attribute level.

Missing one field must not invalidate the entire scholarship record.

---

## 19. Conflicting Scholarship Information

Conflicts may occur between:

- official scholarship pages,
- prospectuses,
- admission notices,
- financial-aid offices,
- older documents,
- external scholarship listings.

Conflicts should be preserved and governed using:

- source authority,
- publication date,
- effective period,
- scope,
- specificity,
- evidence quality.

Unresolved conflicts may remain explicitly marked rather than silently merged.

---

## 20. Scholarship Amounts and Currency

Where financial amounts are published, the platform should retain:

- amount,
- currency,
- period,
- benefit type,
- conditions,
- source.

Currency conversion, if introduced later, must be treated as a derived representation and must not replace the source currency.

The platform must not invent current exchange-rate assumptions when representing official scholarship amounts.

---

## 21. Scholarship vs Fee Information

Scholarship information and fee information are separate layers.

For example:

- a program may have verified tuition but unknown scholarships;
- a scholarship may cover part of tuition;
- a scholarship may provide a stipend without reducing tuition;
- a fee waiver may apply only to the application fee.

The system must not subtract scholarship values from official fees unless a clearly governed derived calculation is introduced.

---

## 22. Student-Facing Affordability Information

Future features may present:

- fee information,
- known scholarships,
- potential financial support,
- estimated net cost.

However, a net-cost figure must clearly identify:

- source fees,
- scholarship assumptions,
- eligibility assumptions,
- time period,
- whether the calculation is official or derived.

A student should never be shown a guaranteed “final cost” based solely on estimated scholarship eligibility.

---

## 23. Search and Discovery

Scholarship information may support discovery by:

- scholarship availability,
- support type,
- merit/need basis,
- program,
- campus,
- academic level,
- applicant category,
- application period.

By default, missing scholarship data must not exclude institutions or programs from general search.

A separate user-selected filter such as “records with known scholarships” may be introduced later.

---

## 24. SEO and Public Pages

Scholarship information may have dedicated public representations where useful.

However:

- canonical institution URLs remain stable;
- canonical program URLs remain stable;
- temporary scholarship cycles must not unnecessarily replace permanent program identity;
- historical scholarship information may remain accessible with clear historical labeling.

SEO representations remain derived from governed data.

---

## 25. AI-Assisted Scholarship Intelligence

AI may assist with:

- extracting scholarship details,
- identifying eligibility requirements,
- detecting changed deadlines,
- detecting new scholarship announcements,
- comparing annual scholarship offerings,
- identifying missing information,
- detecting conflicts,
- generating review queues,
- summarizing verified scholarship information.

AI must not independently invent:

- scholarship availability,
- benefit amounts,
- eligibility,
- deadlines,
- award guarantees.

AI outputs remain proposals or derived summaries until governed by evidence and verification.

---

## 26. Notifications

Future systems may notify students about:

- newly announced scholarships,
- scholarship deadlines,
- changed eligibility,
- changed benefits,
- reopened applications.

Notifications should be based on appropriately verified information and should clearly distinguish current from historical information.

---

## 27. Data Quality Requirements

Scholarship data should be evaluated across:

- identity accuracy,
- scope accuracy,
- eligibility accuracy,
- benefit accuracy,
- deadline accuracy,
- period accuracy,
- category accuracy,
- source authority,
- freshness,
- completeness,
- conflict state,
- traceability.

A missing scholarship record should not be interpreted as proof of absence.

---

## 28. Special Cases

The governance should accommodate:

- institutional scholarships,
- government scholarships,
- externally funded scholarships,
- research assistantships,
- teaching assistantships,
- merit awards,
- need-based aid,
- fee waivers,
- tuition remission,
- continuing-student scholarships,
- admission-specific scholarships,
- automatically considered scholarships,
- scholarships requiring separate applications,
- scholarships with limited seats,
- renewable scholarships,
- conditional scholarships.

---

## 29. Relationship to Approved Documents

MOD-013 extends:

- **MOD-009:** Program and Program Offering Governance;
- **MOD-010:** Admissions, Intake and Eligibility Governance;
- **MOD-011:** Merit, Selection and Admission Outcome Governance;
- **MOD-012:** Fees and Financial Information Governance;
- **MOD-006:** Source, Evidence and Verification Governance.

The conceptual relationship is:

**Program → Offering → Admission → Fees + Scholarships/Financial Aid**

Fees and financial aid remain separate but related information layers.

---

## 30. Explicit Non-Approval of Implementation

This document does **not** approve:

- payment processing,
- financial transactions,
- scholarship application processing,
- loan systems,
- database schemas,
- programming language,
- framework,
- APIs,
- search implementation,
- AI provider/model,
- notification provider,
- automation platform,
- hosting/deployment architecture.

These require later specifications and governed decisions.

---

## 31. Future Evolution

Future documentation may define:

- detailed scholarship taxonomy,
- financial-aid data specification,
- scholarship comparison,
- affordability calculations,
- scholarship eligibility tools,
- application-link governance,
- source monitoring,
- scholarship change detection,
- deadline alerts,
- APIs and implementation schemas.

Future implementation must preserve the central principle that **missing scholarship information never invalidates the underlying educational record and never proves that no scholarship exists**.

---

## 32. Approval State

This document is a **final candidate pending merge into `main`**.

Once merged into the repository's `main` branch, it becomes part of the approved project documentation unless superseded by a later governed decision, RFC, or ADR.
