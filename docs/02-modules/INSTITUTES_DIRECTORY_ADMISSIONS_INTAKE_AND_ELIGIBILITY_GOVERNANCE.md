# MOD-010 — Institutes Directory: Admissions, Intake and Eligibility Governance

**Document ID:** MOD-010  
**Title:** Institutes Directory — Admissions, Intake and Eligibility Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-001, MOD-002, MOD-003, MOD-004, MOD-005, MOD-006, MOD-007, MOD-008, MOD-009  
**Related Documents:** MOD-004, MOD-006, MOD-007, MOD-008, MOD-009  
**Next Documents:** Admissions data specification, fees and financial information governance, merit governance, implementation specifications

---

## 1. Purpose

This document establishes the long-term conceptual governance for **Admissions, Intakes, Eligibility and Application Availability** within the Institutes Directory.

The platform must distinguish between:

- a program,
- a program offering,
- an admission cycle or intake,
- eligibility requirements,
- admission availability,
- and an individual student's application or admission outcome.

The directory is primarily an information and discovery system. It must not imply that the platform itself is the official admissions authority unless a future approved integration explicitly establishes such a role.

This document is technology-neutral and does not approve implementation architecture, database design, APIs, application workflows, payment systems, or external admission integrations.

---

## 2. Core Principle

Admission information is **time-sensitive and offering-specific**.

The existence of a Program or Program Offering does not automatically mean:

- applications are open,
- the institution is accepting applications,
- the applicant is eligible,
- seats are available,
- or admission is guaranteed.

Admission information must therefore be represented as a governed, evidence-backed and temporally valid layer associated with the relevant Program Offering.

---

## 3. Entity Boundaries

The following concepts must remain distinct.

### 3.1 Program

The enduring academic program governed by MOD-009.

### 3.2 Program Offering

A specific manifestation of a program under defined conditions.

### 3.3 Admission Cycle / Intake

A defined period or opportunity during which applications may be accepted for one or more offerings.

### 3.4 Eligibility Requirement

A rule or condition that describes whether an applicant may qualify to apply or be considered.

### 3.5 Admission Availability

The current or historical state indicating whether applications are being accepted.

### 3.6 Application

An applicant's submission to an institution or authorized admissions system.

An application is not the same as a directory record.

### 3.7 Admission Outcome

An institution's decision regarding an application.

The directory must not infer or fabricate individual admission outcomes.

---

## 4. Admission Cycle and Intake Identity

An intake should represent a specific admission opportunity or period.

Conceptual dimensions may include:

- institution,
- program offering,
- campus where applicable,
- intake name,
- academic year,
- semester/term,
- application opening date,
- application closing date,
- admission phase,
- relevant publication date,
- status.

The same Program Offering may have multiple historical or future intakes.

An intake must not be duplicated merely because the same information appears across multiple source pages.

---

## 5. Admission Lifecycle

Conceptual admission states may include:

- Planned
- Announced
- Scheduled
- Applications Open
- Applications Closing Soon
- Applications Closed
- Admission Processing
- Merit/Selection Published
- Enrollment/Joining Period
- Completed
- Cancelled
- Postponed
- Suspended
- Unknown

These states are conceptual governance categories and do not constitute an implementation enum.

The actual state must be supported by evidence and temporal context.

---

## 6. Date Governance

Admission dates are high-sensitivity information because students may rely on them for real decisions.

The platform should distinguish:

- announcement date,
- application opening date,
- application deadline,
- late application deadline,
- test date,
- interview date,
- merit publication date,
- fee submission deadline,
- enrollment deadline,
- orientation/start date.

Dates must not be silently copied between intakes.

If a date is uncertain, outdated, conflicting or inferred, the uncertainty must remain visible to the governance layer.

---

## 7. Current vs Historical Admissions

The directory must clearly distinguish:

- current admissions,
- future announced admissions,
- admissions for a specific upcoming intake,
- recently closed admissions,
- historical admissions,
- cancelled/postponed admissions,
- unknown status.

Historical admission information should be preserved when useful for audit, research, student planning, trend analysis or source verification.

Historical data must not be presented as current admission availability.

---

## 8. Eligibility Governance

Eligibility requirements may include:

- prior qualification,
- required academic level,
- subject prerequisites,
- minimum marks or grades,
- entry test requirements,
- age requirements where officially applicable,
- domicile or residency requirements where applicable,
- experience requirements,
- professional registration,
- language requirements,
- category-specific requirements,
- other officially stated conditions.

Eligibility must be associated with the relevant program, offering, intake or applicant category where applicable.

The platform must not assume that an institution-wide rule applies to every program.

---

## 9. Minimum Marks and Thresholds

Numerical thresholds must be treated as source-backed attributes.

Examples include:

- minimum percentage,
- minimum grade,
- minimum GPA,
- subject-specific marks,
- entry-test score,
- aggregate threshold.

The directory must distinguish:

- minimum eligibility threshold,
- competitive merit,
- historical closing merit,
- recommended target,
- published selection cutoff.

These concepts must not be conflated.

A historical merit value must never automatically become a current eligibility threshold.

---

## 10. Subject Prerequisites

Some programs may require specific prior subjects or combinations of subjects.

The governance should support:

- required subjects,
- acceptable subject alternatives,
- subject combinations,
- group/category requirements,
- qualification-specific prerequisites,
- exceptions explicitly published by the institution.

The system must not infer equivalence between different qualifications without governed evidence.

---

## 11. Qualification Equivalence

Applicants may hold qualifications from different boards, institutions, systems or countries.

Equivalence requirements must be represented separately from ordinary eligibility.

Where an authority officially determines equivalence, that evidence should be distinguishable from institutional preference or informal interpretation.

The directory should not independently declare an educational qualification equivalent unless supported by authoritative evidence.

---

## 12. Applicant Categories

Admission requirements may vary by category.

Possible conceptual categories include:

- open/general applicants,
- local applicants,
- international applicants,
- overseas applicants,
- reserved categories,
- quota categories,
- sponsored applicants,
- transfer applicants,
- diploma holders,
- special admission categories.

Category-specific eligibility must not overwrite the general requirement.

---

## 13. Entry Test Requirements

A program or intake may require:

- institution-specific test,
- standardized test,
- professional test,
- interview,
- audition,
- portfolio,
- practical assessment,
- other selection mechanism.

The directory should distinguish:

- requirement exists,
- test name,
- test provider,
- minimum qualifying condition where officially stated,
- test date where relevant,
- whether the test is mandatory or conditional.

A test requirement must not be inferred solely from historical practice.

---

## 14. Eligibility vs Merit

Eligibility and merit are separate concepts.

**Eligibility** answers whether an applicant meets stated conditions to be considered.

**Merit** relates to selection or ranking among eligible applicants.

A student meeting minimum eligibility does not necessarily have admission assurance.

Future merit governance should define how merit lists, closing merit and historical selection information are represented.

---

## 15. Seats and Capacity

Where authoritative information exists, the directory may represent:

- announced seats,
- seat categories,
- quota allocations,
- capacity statements.

Seat information is highly time-sensitive and must be associated with the relevant intake and category.

The platform must not infer available seats merely because an admission page exists.

---

## 16. Admission Fees and Financial Requirements

Admission-related financial information may include:

- application fee,
- admission processing fee,
- test fee,
- deposit,
- initial fee requirement.

These values must be distinguished from:

- tuition,
- semester fees,
- hostel charges,
- transport,
- refundable deposits,
- other institutional costs.

Detailed financial governance should be specified separately.

---

## 17. Admission Source and Evidence

Admission information must follow MOD-006.

Preferred evidence may include:

- official admission notices,
- official prospectuses,
- official admission portals,
- official program pages,
- official institutional announcements,
- regulatory or authoritative notices where applicable,
- archived official materials for historical records.

Secondary sources may help discovery and corroboration but should not silently replace stronger evidence.

Each material admission assertion should remain traceable to its supporting evidence.

---

## 18. Conflicting Admission Information

Conflicts may occur between:

- official webpages,
- admission notices,
- prospectuses,
- social announcements,
- portal pages,
- third-party listings,
- archived pages.

The platform must not silently choose one value without governance.

For time-sensitive information, the conflict should be evaluated using:

- publication date,
- stated validity period,
- authority,
- specificity,
- directness,
- currentness,
- evidence quality.

The conflict and resolution should remain auditable.

---

## 19. Admission Status Freshness

Admission data requires more aggressive freshness governance than stable institution identity.

A source may remain historically valid while its admission deadline is no longer current.

Therefore:

- source freshness,
- assertion freshness,
- intake validity,
- and current publication status

must be treated as distinct concepts.

Stale admission information must not be presented as active without renewed evidence.

---

## 20. Application Destination

The directory may provide a link or reference to an official application destination when verified.

The canonical application destination may be:

- institution admission portal,
- official application page,
- authorized external admission system.

The directory should distinguish an informational program page from an actual application destination.

Third-party application links must not be presented as official unless evidence supports that relationship.

---

## 21. Application Process Information

The directory may describe high-level application steps such as:

1. review eligibility,
2. prepare required documents,
3. submit application,
4. pay required application fee,
5. complete test/interview where applicable,
6. monitor selection/merit information,
7. complete enrollment requirements.

Such summaries are derived representations and must remain consistent with verified institutional instructions.

The platform must not silently replace official application instructions with AI-generated procedures.

---

## 22. Documents and Requirements

Admission documentation may include:

- identification documents,
- academic certificates,
- transcripts,
- photographs,
- domicile/residency evidence,
- equivalence certificates,
- test results,
- experience documents,
- category-specific evidence.

Requirements may vary by program and intake.

The directory must preserve this scope rather than assuming one universal document list.

---

## 23. Admission Deferrals, Extensions and Changes

Admission cycles may be:

- extended,
- postponed,
- reopened,
- cancelled,
- replaced,
- amended.

Changes must be recorded as temporal events where appropriate.

A later deadline should not erase the original published deadline from historical evidence.

---

## 24. Program Offering Changes During Admissions

If an institution changes a program offering during an admission cycle, the directory should preserve:

- the original offering state,
- the change event,
- the effective date,
- the updated state,
- supporting evidence.

Examples include:

- campus change,
- delivery mode change,
- eligibility change,
- deadline change,
- program title change,
- intake postponement.

---

## 25. Search and Student Discovery

Admission information may support discovery by:

- currently open admissions,
- upcoming intakes,
- academic level,
- discipline,
- location,
- campus,
- eligibility category,
- qualification,
- delivery mode,
- deadline range.

Search and filters are derived representations.

The search layer must not become the authoritative source of admission truth.

---

## 26. SEO and Public Representation

Admission pages may be valuable search destinations, but SEO representations must remain subordinate to verified canonical data.

The platform should distinguish:

- permanent program identity,
- offering identity,
- intake/admission identity,
- historical admission pages,
- search/filter representations.

Temporary admission states should not cause unnecessary permanent URL instability.

Historical admission pages may remain discoverable where they provide useful archival value, while clearly indicating historical status.

---

## 27. AI-Assisted Admissions Intelligence

AI may assist with:

- extracting admission dates,
- identifying changes in notices,
- detecting possible expired deadlines,
- comparing current and previous notices,
- extracting eligibility requirements,
- detecting conflicting information,
- identifying missing fields,
- generating review queues,
- summarizing verified admission information.

AI must not independently declare:

- an admission deadline,
- eligibility,
- seat availability,
- official admission status,
- qualification equivalence,
- or application authority

without governed evidence.

AI outputs are proposals or derived representations unless explicitly verified.

---

## 28. Data Quality Requirements

Admission data should be evaluated across:

- date accuracy,
- status accuracy,
- eligibility accuracy,
- source authority,
- temporal validity,
- freshness,
- completeness,
- campus/program alignment,
- category alignment,
- conflict state,
- traceability.

Because admission information changes rapidly, freshness should be attribute-specific.

---

## 29. Notifications and Monitoring

Future systems may notify users about:

- newly announced admissions,
- approaching deadlines,
- deadline changes,
- admission reopening,
- program changes,
- eligibility changes.

Notifications must be generated from verified or appropriately qualified information.

The platform must avoid presenting uncertain or stale data as confirmed alerts.

---

## 30. Relationship to Program and Offering Governance

MOD-010 extends MOD-009.

The conceptual relationship is:

**Institution → Campus → Program → Program Offering → Admission Intake → Eligibility / Availability**

A Program may exist without a current admission cycle.

A Program Offering may exist without currently accepting applications.

An Intake may be announced before applications open.

Eligibility may vary by intake or applicant category.

These distinctions must be preserved.

---

## 31. Special Cases

The governance should accommodate:

- rolling admissions,
- multiple intakes per year,
- single annual intake,
- open admissions,
- late admissions,
- conditional admissions,
- transfer admissions,
- international admissions,
- quota-based admissions,
- professional program admissions,
- short-course admissions,
- online admissions,
- distance admissions,
- joint-program admissions,
- admissions through centralized systems.

Each case requires evidence appropriate to the specific institution, program and intake.

---

## 32. Explicit Non-Approval of Implementation

This document does **not** approve:

- database schema,
- SQL/NoSQL technology,
- programming language,
- framework,
- API design,
- admissions portal integration,
- payment gateway,
- notification provider,
- search engine,
- frontend implementation,
- scraping architecture,
- AI provider/model,
- automation platform,
- hosting/deployment architecture.

These require later specifications and governed decisions.

---

## 33. Future Evolution

Future documentation may define:

- detailed admissions data specification,
- eligibility rule model,
- merit governance,
- fee and financial governance,
- application-link governance,
- admission source monitoring,
- change detection,
- notification rules,
- historical admission analytics,
- program comparison,
- student eligibility tools,
- AI-assisted admissions monitoring,
- APIs and implementation schemas.

No future implementation should weaken the evidence, temporal, identity and verification principles established here without an explicit governed decision.

---

## 34. Approval State

This document is a **final candidate pending merge into `main`**.

Once merged into the repository's `main` branch, it becomes part of the approved project documentation unless superseded by a later governed decision, RFC, or ADR.
