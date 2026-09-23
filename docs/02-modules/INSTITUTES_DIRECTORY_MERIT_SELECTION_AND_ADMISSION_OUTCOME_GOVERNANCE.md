# MOD-011 — Institutes Directory: Merit, Selection and Admission Outcome Governance

**Document ID:** MOD-011  
**Title:** Institutes Directory — Merit, Selection and Admission Outcome Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-004, MOD-006, MOD-007, MOD-008, MOD-009, MOD-010  
**Related Documents:** MOD-006, MOD-009, MOD-010  
**Next Documents:** Fees and financial information governance, scholarships and financial aid governance, implementation specifications

---

## 1. Purpose

This document establishes the long-term conceptual governance for **Merit, Selection, Closing Merit, Selection Lists and Admission Outcomes** within the Institutes Directory.

The platform must distinguish between:

- eligibility,
- merit calculation,
- applicant ranking,
- selection,
- published merit lists,
- closing merit,
- admission offers,
- enrollment,
- and final admission outcomes.

These concepts are frequently treated as interchangeable in education directories, but they represent different real-world states and must remain separate.

This document is technology-neutral. It does not approve database schemas, ranking algorithms, application systems, payment systems, APIs, frontend implementation, or external admission integrations.

---

## 2. Core Principle

**Eligibility is not merit, merit is not selection, and selection is not enrollment.**

A candidate may satisfy eligibility requirements without being selected.

A published merit threshold may reflect a historical selection result without being a current eligibility requirement.

An admission offer does not necessarily prove that enrollment was completed.

The directory must preserve these distinctions.

---

## 3. Entity Boundaries

### 3.1 Eligibility

The conditions an applicant must satisfy to be considered, governed by MOD-010.

### 3.2 Merit

A selection-related measure, aggregate, score, rank, or threshold used by an institution or authorized admission process.

### 3.3 Applicant Ranking

An ordering or position among eligible applicants where the admission process produces such information.

### 3.4 Merit List

A published selection or ranking representation for a particular admission cycle, category, program offering, or stage.

### 3.5 Closing Merit

A historical or current threshold associated with the last selected candidate, last published list, or other explicitly defined selection boundary.

The directory must preserve the exact meaning supplied by the source.

### 3.6 Admission Offer

A selection decision or offer made to an applicant.

### 3.7 Enrollment

The applicant's completion of required steps to join the institution/program.

### 3.8 Admission Outcome

A final or intermediate result of the admission process.

The directory primarily represents publicly verifiable institutional information, not private applicant records.

---

## 4. Merit Context

Every merit value must have context.

Relevant dimensions may include:

- institution,
- program,
- program offering,
- campus,
- admission intake,
- academic year,
- selection stage,
- applicant category,
- quota,
- merit methodology,
- list number,
- source.

A merit number without context must not be presented as universally applicable.

---

## 5. Merit Types

The platform should distinguish conceptually between:

- minimum eligibility percentage,
- minimum qualifying score,
- merit score,
- aggregate score,
- merit position,
- merit rank,
- published cutoff,
- closing merit,
- waiting-list threshold,
- historical closing merit,
- predicted or estimated merit.

Predicted or estimated values must never be presented as official merit.

---

## 6. Merit Methodology

Where officially published, the directory may represent how merit is calculated.

Examples of components may include:

- previous academic qualification,
- subject marks,
- entry test,
- interview,
- portfolio,
- experience,
- category-specific components,
- other institution-defined factors.

The platform must distinguish the **published methodology** from any calculation independently performed by the platform.

A platform-generated calculation must not be presented as the institution's official merit calculation unless the method and authority are explicitly established.

---

## 7. Weightages

Where published, the directory may represent component weightages.

For example, an institution may publish a formula involving multiple academic or testing components.

Weightages are intake- and program-sensitive.

A historical formula must not automatically be applied to a later admission cycle.

If a source does not provide a reliable formula, the platform must not invent one.

---

## 8. Merit Lists

A merit list may represent:

- first list,
- second list,
- subsequent list,
- waiting list,
- reserved-category list,
- open/general list,
- program-specific list,
- campus-specific list,
- other institution-defined selection stages.

Each list should retain:

- relevant intake,
- publication date,
- program/offering,
- category where applicable,
- selection stage,
- source,
- temporal validity.

---

## 9. Closing Merit

Closing merit is highly context-sensitive.

The platform should preserve:

- what list or stage it belongs to,
- what category it represents,
- what program/offering it represents,
- what campus it represents,
- what intake it represents,
- whether it is official or derived,
- the source and publication date.

Statements such as “the closing merit is X” must be avoided when the underlying context is missing.

---

## 10. Historical Merit

Historical merit information may be valuable to students for research and planning.

Historical values must remain explicitly historical.

The platform must not imply that a previous year's closing merit is:

- a current eligibility threshold,
- a current guaranteed admission threshold,
- or a current official prediction.

Historical merit may be used as a derived informational reference only when its historical context is retained.

---

## 11. Current Merit Information

Current merit information may include:

- currently published merit lists,
- current selection thresholds,
- current list positions,
- current closing merit where officially published.

Current information requires appropriate freshness and evidence controls under MOD-006 and MOD-010.

---

## 12. Selection States

Conceptual selection states may include:

- Not Yet Published
- Selection Process Started
- Merit List Published
- Provisionally Selected
- Selected
- Waitlisted
- Not Selected
- Offer Issued
- Offer Accepted
- Enrollment Pending
- Enrolled
- Withdrawn
- Rejected
- Cancelled

These are conceptual governance states, not implementation commitments.

The directory should not infer a private applicant's state from public merit information.

---

## 13. Merit List vs Individual Outcome

A published merit list may show that candidates were selected or ranked.

It does not necessarily prove:

- that every selected candidate enrolled,
- that every seat was filled,
- that an applicant accepted the offer,
- or that the final cohort remained unchanged.

The platform must not convert a public list into private applicant outcome data.

---

## 14. Waiting Lists

Waiting-list information should be treated separately from confirmed selection.

Where officially published, the directory may represent:

- waiting-list existence,
- waiting-list position or threshold,
- list date,
- program/campus/category context,
- subsequent movement where reliably documented.

A waiting-list position does not guarantee admission.

---

## 15. Quotas and Categories

Merit may differ by:

- open merit,
- regional quota,
- reserved category,
- domicile,
- international category,
- special category,
- other institution-defined group.

The platform must retain category context.

A merit value for one category must not be generalized to all applicants.

---

## 16. Campus-Specific Merit

Where merit is published separately by campus, the directory must preserve campus context.

A multi-campus institution may have different merit thresholds for the same or similarly named program.

The institution-level program record must not overwrite campus-specific merit information.

---

## 17. Program and Intake Relationship

Merit belongs to a defined admission context.

Conceptually:

**Institution → Campus → Program → Program Offering → Intake → Selection/Merit Context**

A merit value should not be detached from its relevant program offering or intake unless the source explicitly defines it at a broader scope.

---

## 18. Admission Outcome and Enrollment

The directory may represent publicly documented institutional outcomes such as:

- selected,
- provisionally selected,
- offer issued,
- enrollment deadline,
- enrollment status of a published selection stage.

It should not collect or expose private applicant-level information unless a separate lawful and governed system explicitly authorizes it.

Public selection information and private admission records are different domains.

---

## 19. Merit Changes and Revised Lists

Institutions may:

- revise merit lists,
- correct published results,
- add candidates,
- withdraw candidates,
- publish later lists,
- change thresholds,
- extend deadlines.

The platform should preserve the history of material changes when evidence is available.

A revised list must not silently erase the existence of the earlier publication.

---

## 20. Source and Evidence Governance

All material merit and selection assertions should follow MOD-006.

Evidence may include:

- official merit lists,
- official admission portals,
- official notices,
- official prospectuses,
- authorized admission systems,
- archived institutional publications.

Secondary sources may assist discovery or corroboration.

Where sources conflict, the platform should preserve the conflict and document the resolution rather than silently choosing a value.

---

## 21. Freshness and Temporal Validity

Merit information is highly time-sensitive.

The platform must distinguish:

- publication date,
- effective admission cycle,
- list stage,
- validity period where stated,
- later revision,
- historical status.

A current program page must not automatically make historical merit information current.

---

## 22. Search and Discovery

Merit information may support discovery by:

- program,
- campus,
- admission cycle,
- academic year,
- category,
- merit range where appropriate,
- published closing merit,
- selection stage.

Search and filters are derived representations.

They must not create new official merit values.

---

## 23. Student-Facing Explanations

The platform may explain concepts such as:

- eligibility,
- aggregate,
- merit,
- closing merit,
- merit list,
- waiting list.

Explanations should clearly distinguish official facts from general educational guidance.

A generic explanation must not be presented as institution-specific policy.

---

## 24. Comparison and Historical Analysis

Future comparison features may compare merit information across:

- years,
- campuses,
- programs,
- categories,
- admission cycles.

Comparisons must preserve source, year, category and methodology context.

A numerical difference does not by itself establish why merit changed.

---

## 25. AI-Assisted Merit Intelligence

AI may assist with:

- extracting merit values from official lists,
- identifying list stages,
- matching merit data to programs and intakes,
- detecting revised lists,
- identifying missing context,
- comparing historical records,
- flagging conflicts,
- generating review queues,
- summarizing verified merit information.

AI must not independently:

- invent merit values,
- convert historical merit into current eligibility,
- predict guaranteed admission,
- claim official selection status,
- infer private applicant outcomes,
- or present estimates as institutional results.

AI-derived estimates, if a future governed feature is approved, must be clearly labeled as estimates and separated from official values.

---

## 26. Data Quality Requirements

Merit and selection data should be evaluated across:

- numerical accuracy,
- contextual accuracy,
- program alignment,
- campus alignment,
- intake alignment,
- category alignment,
- methodology accuracy,
- publication date,
- revision history,
- source authority,
- freshness,
- completeness,
- traceability.

A single global quality score must not replace attribute-level quality governance.

---

## 27. Special Cases

The governance should accommodate:

- multiple merit lists,
- rolling selection,
- waiting lists,
- quota-specific merit,
- campus-specific merit,
- centralized admission systems,
- test-based selection,
- interview-based selection,
- portfolio-based selection,
- professional programs,
- programs with no published merit,
- programs with qualitative selection,
- programs where selection methodology changes between cycles.

Where no official merit number exists, the platform must not fabricate one.

---

## 28. Relationship to Approved Documents

MOD-011 extends:

- **MOD-009:** Program and Program Offering Governance;
- **MOD-010:** Admissions, Intake and Eligibility Governance;
- **MOD-006:** Source, Evidence and Verification Governance;
- **MOD-007:** Institution Identity and Lifecycle;
- **MOD-008:** Campus and Physical Presence.

The conceptual chain is:

**Institution → Campus → Program → Program Offering → Intake → Eligibility → Merit/Selection → Admission Outcome**

---

## 29. Explicit Non-Approval of Implementation

This document does **not** approve:

- ranking algorithms,
- predictive models,
- database schemas,
- SQL/NoSQL technology,
- programming language,
- framework,
- API design,
- admissions portal integration,
- private applicant data systems,
- payment systems,
- search implementation,
- AI provider/model,
- automation platform,
- hosting/deployment architecture.

These require later specifications and governed decisions.

---

## 30. Future Evolution

Future documentation may define:

- detailed merit data specification,
- merit calculation representation,
- closing-merit governance,
- historical merit analytics,
- scholarships and financial aid,
- fees and financial information,
- student eligibility tools,
- program comparison,
- admission prediction boundaries,
- source monitoring,
- automated change detection,
- APIs and implementation schemas.

Any future predictive or recommendation feature must preserve the distinction between official institutional data and derived estimates.

---

## 31. Approval State

This document is a **final candidate pending merge into `main`**.

Once merged into the repository's `main` branch, it becomes part of the approved project documentation unless superseded by a later governed decision, RFC, or ADR.
