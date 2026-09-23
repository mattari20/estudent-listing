# MOD-012 — Institutes Directory: Fees and Financial Information Governance

**Document ID:** MOD-012  
**Title:** Institutes Directory — Fees and Financial Information Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-004, MOD-006, MOD-007, MOD-008, MOD-009, MOD-010, MOD-011  
**Related Documents:** MOD-006, MOD-009, MOD-010, MOD-011  
**Next Documents:** Scholarships and financial aid governance, accreditation/recognition governance, implementation specifications

---

## 1. Purpose

This document establishes the long-term conceptual governance for **fees, costs and other financial information** associated with institutions, programs, program offerings and admission cycles.

The most important principle is:

> **Financial information is optional and must never be a hard dependency for the directory's core operation.**

An institution, campus, program, program offering or admission record must remain valid and discoverable even when verified cost information is unavailable.

Missing financial information is a **data availability state**, not a system failure.

This allows the directory to launch and operate correctly with partial financial coverage while progressively improving financial completeness over time.

This document is technology-neutral. It does not approve payment processing, financial transactions, database schemas, APIs, or implementation architecture.

---

## 2. Core Principle: Cost Information Must Be Non-Blocking

The directory must function correctly when:

- no fee information is available;
- only some fee components are available;
- historical fees are available but current fees are unknown;
- fees differ by campus;
- fees differ by applicant category;
- fees differ by intake;
- the institution publishes fees separately;
- the institution requires users to contact admissions for current fees;
- fee information is temporarily unavailable;
- conflicting fee information exists.

The absence of fee information must never cause:

- institution removal,
- program removal,
- offering removal,
- admission record removal,
- broken search,
- broken profile pages,
- invalid canonical URLs,
- incorrect assumptions that the program is free,
- or fabricated fee values.

---

## 3. Financial Information Is an Optional Attribute Layer

Financial information is a dependent information layer over the core academic model.

Conceptually:

**Institution → Campus → Program → Program Offering → Intake → Financial Information**

However, the financial layer is not required for the existence of any preceding entity.

For example:

- A Program may exist without fee information.
- An active Program Offering may exist without fee information.
- An Admission Intake may exist while fees remain unknown.
- A program may be fully searchable even when cost data is unavailable.

This principle must remain true throughout future implementation.

---

## 4. Financial Information States

The platform should distinguish conceptually between:

- Known
- Partially Known
- Unknown
- Not Published
- Not Applicable
- Awaiting Verification
- Conflicting
- Historical Only
- Stale
- Superseded

These states must not be collapsed into a single blank or zero value.

### Important distinction

**Unknown ≠ Free**

A missing fee must never be interpreted as zero.

---

## 5. Scope of Financial Information

Financial information may relate to:

- application fee,
- admission fee,
- tuition,
- semester fee,
- annual fee,
- credit-hour fee,
- laboratory fee,
- examination fee,
- registration fee,
- technology fee,
- library fee,
- hostel fee,
- transport fee,
- refundable deposit,
- security deposit,
- other officially published charges.

Different components should remain distinguishable.

The platform must avoid presenting one partial component as the total cost.

---

## 6. Fee Context

Every financial assertion should retain its context where applicable:

- institution,
- program,
- program offering,
- campus,
- intake,
- academic year,
- semester/term,
- applicant category,
- delivery mode,
- local/international status,
- credit-hour basis,
- source,
- publication/effective date.

A fee without context should not be presented as universally applicable.

---

## 7. Tuition vs Total Cost

The directory should distinguish:

**Tuition**

from:

**Total estimated educational cost**

and from:

**Initial payment / admission payment**

and from:

**Other optional or ancillary costs.**

The platform must not silently add or estimate components that are not supported by evidence.

Where a total is calculated from verified components, the result should remain identifiable as a derived calculation.

---

## 8. Fee Period and Frequency

Fees may be expressed as:

- one-time,
- per application,
- per admission,
- per semester,
- per year,
- per credit hour,
- per course,
- monthly,
- per term,
- or another institution-defined period.

The period must remain attached to the value.

A fee of the same numerical amount can have completely different meanings depending on its period.

---

## 9. Fee Ranges and Variable Costs

Institutions may publish:

- fixed fees,
- minimum/maximum ranges,
- variable credit-hour fees,
- category-dependent fees,
- estimated costs,
- fee schedules.

Ranges must not be converted into a single value without preserving the original meaning.

Estimated or approximate values must be clearly distinguished from fixed official charges.

---

## 10. Category-Specific Financial Information

Fees may vary for:

- local students,
- international students,
- overseas students,
- quota/category groups,
- scholarship recipients,
- sponsored students,
- different program tracks,
- different delivery modes.

Category-specific information must not overwrite general information.

The platform should preserve the scope of each financial assertion.

---

## 11. Campus-Specific Fees

The same program may have different costs at different campuses.

Therefore financial information may be associated with:

- institution,
- campus,
- program,
- program offering.

Institution-level fee information must not automatically be applied to every campus.

---

## 12. Intake and Academic-Year Sensitivity

Fees may change between:

- admission intakes,
- academic years,
- semesters,
- fee schedules.

A historical fee must not automatically be presented as current.

Where the source specifies an effective period, that period must be retained.

---

## 13. Fee Changes and History

Material financial changes should be preserved where evidence permits.

Examples:

- tuition increase,
- revised admission fee,
- new fee component,
- removed fee component,
- changed payment schedule,
- revised category fee.

The directory should preserve historical values where useful for:

- audit,
- student research,
- historical comparison,
- trend analysis,
- source verification.

Historical values must be clearly marked as historical.

---

## 14. Conflicting Fee Information

Conflicts may occur between:

- official fee schedules,
- admission notices,
- prospectuses,
- program pages,
- campus pages,
- older PDFs,
- third-party directories.

The platform must not silently combine conflicting values.

Resolution should consider:

- authority,
- publication date,
- effective period,
- specificity,
- program/campus context,
- applicant category,
- evidence quality.

Unresolved conflicts may remain explicitly marked as conflicting or under review.

---

## 15. Source and Evidence Governance

All financial assertions must follow MOD-006.

Possible sources include:

- official fee schedules,
- official prospectuses,
- official admission notices,
- official program pages,
- official admission portals,
- institution-submitted data,
- authoritative regulatory sources where relevant,
- historical official publications.

Third-party information may support discovery or corroboration but should not silently become canonical financial truth.

---

## 16. Freshness

Financial information is generally more volatile than institution identity and may be more volatile than program identity.

The platform should distinguish:

- source freshness,
- financial assertion freshness,
- academic-year validity,
- intake validity,
- effective date,
- superseded status.

A current program page does not automatically make an old fee schedule current.

---

## 17. Missing Financial Information

When cost information is unavailable, the platform should be able to communicate states such as:

- Fee information not available.
- Current fee not published.
- Fee information requires verification.
- Historical fee available; current fee unavailable.
- Contact institution for current fee information.

The exact public wording can be defined later.

The system must **not**:

- display zero,
- invent an estimate,
- copy an unrelated program's fee,
- use an old fee as current without evidence,
- remove the program,
- block search or discovery.

---

## 18. Partial Financial Information

A record may contain only some components.

For example:

- application fee known;
- tuition unknown;
- hostel fee unknown.

The platform should publish the verified components without pretending that the complete cost is known.

Completeness must be attribute-level.

---

## 19. Derived Cost Calculations

Future features may calculate values such as:

- estimated first-semester cost,
- estimated annual cost,
- component totals,
- multi-year estimates.

Derived calculations must:

1. use identifiable source values;
2. preserve the calculation period;
3. identify assumptions;
4. distinguish estimates from official totals;
5. avoid filling missing values with invented assumptions.

A derived estimate must never replace the underlying official financial assertions.

---

## 20. Search and Discovery

Financial information may support filters such as:

- fee information available,
- fee range,
- tuition basis,
- local/international fee category,
- academic year,
- program,
- campus.

However:

**Financial filters must not exclude records simply because fee data is missing unless the user explicitly requests records with known financial information.**

The default directory should remain inclusive.

---

## 21. SEO and Public Pages

A program or institution must retain its canonical public page even when financial information is unavailable.

Financial completeness must not determine whether a canonical page exists.

Search-engine representations may state that current fee information is unavailable where appropriate.

Historical fee pages may remain accessible when useful, provided their historical status is clear.

---

## 22. AI-Assisted Financial Intelligence

AI may assist with:

- extracting fee tables,
- identifying fee components,
- detecting changes,
- comparing fee schedules,
- identifying stale financial pages,
- finding missing fee information,
- flagging conflicts,
- generating review queues,
- summarizing verified financial information.

AI must not independently invent:

- fee values,
- total costs,
- missing components,
- current validity,
- or official financial policies.

AI-derived estimates must be explicitly separated from official financial information.

---

## 23. Student Cost Transparency

Where verified information exists, the platform may eventually present a structured financial summary showing:

- what is known,
- what is unknown,
- what is one-time,
- what is recurring,
- what is estimated,
- what is historical,
- and what source supports each material value.

The objective is transparency rather than false precision.

---

## 24. Financial Information and Scholarships

Scholarships and financial aid are separate from ordinary fee information.

A scholarship may:

- reduce tuition,
- cover specific components,
- cover a percentage,
- depend on merit,
- depend on category,
- depend on continuing performance.

Scholarship governance should be documented separately.

The presence or absence of scholarship information must not change the canonical fee record.

---

## 25. Financial Information and Admission

Admission information and financial information are related but distinct.

For example:

- admissions may be open while current fees are unavailable;
- fees may be published before admissions open;
- an application fee may be known while tuition remains unknown;
- historical fees may exist for a closed intake.

Neither layer should invalidate the other.

---

## 26. Data Quality Requirements

Financial data should be evaluated across:

- numerical accuracy,
- component accuracy,
- period accuracy,
- program alignment,
- campus alignment,
- category alignment,
- academic-year alignment,
- source authority,
- freshness,
- completeness,
- conflict state,
- traceability.

A single overall completeness percentage must not hide missing critical attributes.

---

## 27. Special Cases

The governance should accommodate:

- credit-hour pricing,
- package pricing,
- annual fee schedules,
- semester-based pricing,
- variable laboratory charges,
- clinical/professional charges,
- hostel and transport charges,
- refundable deposits,
- international student pricing,
- scholarships and discounts,
- institution-wide fee schedules,
- program-specific fee schedules,
- campus-specific fee schedules,
- fees published only inside documents,
- fees available only through an official portal,
- institutions that do not publicly publish fees.

---

## 28. Relationship to Approved Documents

MOD-012 extends:

- **MOD-009:** Program and Program Offering Governance;
- **MOD-010:** Admissions, Intake and Eligibility Governance;
- **MOD-011:** Merit, Selection and Admission Outcome Governance;
- **MOD-006:** Source, Evidence and Verification Governance;
- **MOD-008:** Campus and Physical Presence Governance.

The conceptual chain is:

**Institution → Campus → Program → Program Offering → Intake → Admission → Merit/Selection → Financial Information**

However, the financial layer is **optional and non-blocking**.

---

## 29. Explicit Non-Approval of Implementation

This document does **not** approve:

- payment processing,
- financial transactions,
- payment gateways,
- database schemas,
- programming language,
- framework,
- API design,
- scraping architecture,
- search implementation,
- AI provider/model,
- financial calculators,
- notification systems,
- hosting/deployment architecture.

These require later specifications and governed decisions.

---

## 30. Future Evolution

Future documentation may define:

- detailed fee data specification,
- fee component taxonomy,
- financial comparison,
- cost estimation rules,
- scholarship and financial aid governance,
- historical fee analytics,
- source monitoring,
- fee-change alerts,
- student affordability tools,
- APIs and implementation schemas.

Future implementation must preserve the central rule that **missing financial data never makes the underlying educational record invalid**.

---

## 31. Approval State

This document is a **final candidate pending merge into `main`**.

Once merged into the repository's `main` branch, it becomes part of the approved project documentation unless superseded by a later governed decision, RFC, or ADR.
