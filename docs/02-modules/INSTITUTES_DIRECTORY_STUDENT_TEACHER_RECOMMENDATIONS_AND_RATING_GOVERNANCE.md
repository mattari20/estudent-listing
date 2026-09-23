# MOD-017 — Institutes Directory: Student and Teacher Recommendations and Rating Governance

**Document ID:** MOD-017  
**Title:** Institutes Directory — Student and Teacher Recommendations and Rating Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-006, MOD-007, MOD-008, MOD-009, MOD-014, MOD-015, MOD-016  
**Related Documents:** MOD-006, MOD-014, MOD-015, MOD-016  
**Next Documents:** Ranking methodology governance, trust and anti-abuse governance, data quality and operational governance

---

## 1. Purpose

This document establishes governance for **student and teacher recommendations, ratings and community experience signals** within the Institutes Directory.

The platform may allow students and teachers to recommend institutions, campuses and programs and provide structured ratings based on their experience.

These community signals may later contribute to derived ranking, comparison and discovery systems, but they must remain separate from canonical institutional facts.

---

## 2. Core Principle

**Community recommendation is a user-generated signal, not an institutional fact.**

A student's or teacher's recommendation represents an individual experience or assessment.

It must not automatically establish:

- accreditation;
- recognition;
- official program availability;
- fee amount;
- admission eligibility;
- merit;
- employment outcomes;
- institutional legal status.

Such factual claims require independent evidence under MOD-006.

---

## 3. Student and Teacher Perspectives

The system should distinguish, where appropriate, between:

- student recommendations;
- teacher recommendations;
- alumni recommendations;
- other approved contributor categories.

The contributor category must remain visible in aggregated analysis.

A teacher recommendation must not automatically receive a higher or lower rating weight merely because of role unless a future approved methodology explicitly establishes such treatment.

---

## 4. Recommendation Target

A recommendation may target:

- institution;
- campus;
- program;
- program offering;
- specific student service or experience category.

The target must be explicit.

A recommendation about one campus must not automatically become a recommendation about every campus of the institution.

---

## 5. Rating Dimensions

A future structured rating system may include dimensions such as:

- teaching/learning experience;
- faculty/support;
- facilities;
- student services;
- campus environment;
- administration;
- admissions experience;
- value for money;
- academic experience;
- overall recommendation.

Dimensions must remain distinct.

An overall rating must not be treated as an objective measure of every institutional attribute.

---

## 6. Recommendation Question

The platform may include a direct recommendation question such as:

> **Would you recommend this institution/program to another student?**

Possible conceptual responses may include:

- Recommend;
- Do not recommend;
- Neutral/uncertain;
- Prefer not to answer.

A recommendation response and numerical rating are separate signals.

---

## 7. Rating Scale

If a numerical rating is used, its scale and meaning must be defined consistently.

For example, a future system may use a bounded scale for structured dimensions.

The exact scale is intentionally not approved by this document.

Ratings must not be interpreted beyond the dimension being rated.

---

## 8. Optional Written Explanation

Contributors may optionally explain their recommendation.

Written feedback may provide context for:

- positive experiences;
- negative experiences;
- specific strengths;
- areas for improvement;
- program/campus differences.

Written feedback remains user-generated content and is governed by MOD-015.

---

## 9. Verified Contributor Signals

Future verification mechanisms may distinguish:

- verified student;
- verified teacher;
- verified alumni;
- unverified contributor.

Verification must be treated as a confidence/provenance signal, not proof that every statement made by the contributor is factually correct.

A verified contributor can still express an opinion.

---

## 10. Experience Period

Recommendations should, where practical, preserve the contributor's experience period.

Examples:

- current academic year;
- previous academic year;
- graduation period;
- teaching period;
- specific intake.

This is important because institutions, programs, fees, facilities and management may change over time.

An old recommendation should not silently represent the current experience.

---

## 11. Program and Campus Context

Recommendations should retain relevant context such as:

- institution;
- campus;
- program;
- level;
- delivery mode;
- experience period.

A teacher recommending one department should not automatically be treated as recommending every department.

---

## 12. Anti-Manipulation Principle

The system must be designed to reduce:

- fake recommendations;
- coordinated rating manipulation;
- duplicate submissions;
- institutional self-promotion;
- competitor attacks;
- incentivized manipulation;
- automated submissions;
- retaliation;
- review bombing.

Future anti-abuse rules must be documented separately and auditable.

---

## 13. Institutional Participation

Institutions may eventually be permitted to:

- claim profiles;
- respond to recommendations;
- provide factual corrections;
- identify outdated information.

Institutional responses must remain distinguishable from independent student/teacher recommendations.

Institutions must not be allowed to silently edit or delete independent community ratings.

---

## 14. Recommendation vs Canonical Data

If a contributor states:

> “This program is accredited.”

That statement remains a user claim unless independently verified.

If a contributor states:

> “The fee was PKR X.”

That value does not become the canonical fee merely because it was submitted.

Community signals may trigger verification workflows under MOD-006.

---

## 15. Aggregation

Future aggregation may calculate:

- recommendation rate;
- average rating;
- rating distribution;
- dimension-specific ratings;
- contributor-category breakdown;
- time-period breakdown;
- verified-contributor breakdown;
- confidence/coverage indicators.

Aggregates must retain enough context to prevent misleading conclusions.

For example, a rating based on five responses should not be represented identically to one based on thousands of responses.

---

## 16. Sample Size and Coverage

Any aggregate community signal should consider:

- number of responses;
- contributor diversity;
- experience period;
- target specificity;
- verification coverage;
- response distribution.

A small number of ratings must not automatically be treated as representative of the entire student or teacher population.

---

## 17. Time Decay and Freshness

Community experience may change over time.

Future ranking or recommendation systems may apply governed temporal treatment to older responses.

Any time-decay methodology must be explicitly documented and must not silently erase historical information.

---

## 18. Ranking Influence

Student and teacher recommendations **may contribute to future eStudent ranking or comparison systems**.

However:

- community ratings must be treated as one signal among multiple signals;
- raw rating averages must not automatically become the ranking;
- small samples must not dominate large, well-supported datasets;
- manipulation-detected records must not contribute normally;
- contributor category and context may be retained;
- time period must remain relevant;
- verified institutional facts must remain separate.

A future ranking methodology must explicitly define how community signals affect ranking.

This document intentionally does **not** approve a ranking formula, weight, score or winner.

---

## 19. Recommendation Quality Signal

A future system may derive a confidence/quality representation based on factors such as:

- sample size;
- contributor verification;
- consistency;
- recency;
- target specificity;
- anti-abuse checks.

Such a quality signal must not be confused with the recommendation rating itself.

---

## 20. Conflicting Recommendations

Different contributors may reasonably report different experiences.

The system should preserve this diversity.

It should not automatically remove minority experiences because they differ from the aggregate.

Aggregates should describe the response population rather than claiming universal truth.

---

## 21. AI-Assisted Community Intelligence

AI may assist with:

- spam detection;
- duplicate detection;
- topic extraction;
- moderation;
- identifying potentially coordinated activity;
- summarizing recurring themes;
- identifying factual claims requiring verification;
- detecting unusual rating patterns.

AI must not:

- fabricate recommendations;
- manufacture ratings;
- suppress legitimate negative feedback merely because it is negative;
- convert opinions into facts;
- manipulate ranking outcomes without an approved transparent methodology.

---

## 22. Privacy and Safety

Community submissions must follow future privacy and security governance.

The system should avoid unnecessary disclosure of:

- personal contact information;
- private academic records;
- application information;
- private employment information;
- sensitive personal information.

Where a review contains sensitive allegations, additional moderation and escalation rules may apply.

---

## 23. Search and Discovery

Community signals may support discovery features such as:

- highly recommended by students;
- highly recommended by teachers;
- dimension-specific experience signals;
- community feedback availability.

These representations must clearly communicate that they are community-derived signals.

They must not be presented as government, regulatory or institutional ratings.

---

## 24. SEO and Public Representation

Public pages may display community recommendation information where useful.

SEO representations must preserve:

- rating context;
- response count;
- contributor category;
- time period where relevant;
- moderation state.

Search engines must not receive misleading structured data that represents community opinion as official institutional quality.

---

## 25. Data Quality Requirements

Community recommendation data should be evaluated across:

- target accuracy;
- contributor provenance;
- experience period;
- response completeness;
- duplication;
- moderation state;
- manipulation indicators;
- sample size;
- recency;
- traceability.

The platform should not use a single rating number as a substitute for these dimensions.

---

## 26. Relationship to Reviews

MOD-015 governs general reviews and feedback.

MOD-017 adds structured recommendation and rating governance.

A written review may exist without a recommendation.

A recommendation may exist without a written review.

Both may contribute to future community-derived signals under governed rules.

---

## 27. Relationship to Rankings

MOD-016 governs external rankings and signals.

MOD-017 establishes that eStudent may later use community recommendations as an additional internal signal.

The future ranking system must keep separate:

- official verified facts;
- external rankings;
- community recommendations;
- reviews;
- derived calculations;
- user preferences.

No one signal should silently become the complete definition of institutional quality.

---

## 28. Explicit Non-Approval of Ranking Formula

This document does **not** approve:

- rating weights;
- student vs teacher weights;
- minimum sample size;
- ranking formula;
- normalization method;
- time-decay formula;
- anti-manipulation score;
- recommendation score;
- final institutional ranking.

Those decisions require a dedicated future ranking methodology document.

---

## 29. Explicit Non-Approval of Implementation

This document does not approve:

- database schema;
- account verification architecture;
- moderation software;
- rating UI;
- APIs;
- AI provider;
- recommendation engine;
- ranking engine;
- search implementation;
- notification system;
- hosting/deployment architecture.

---

## 30. Future Evolution

Future documents may define:

- detailed recommendation form;
- student/teacher verification;
- rating dimensions;
- anti-abuse policy;
- moderation workflows;
- aggregation methodology;
- ranking methodology;
- recommendation-to-ranking data pipeline;
- privacy/security controls;
- implementation schemas.

The user's core proposal is therefore preserved as a first-class future capability:

> **Students and teachers may recommend institutions/programs, provide ratings, and these community signals may contribute to future eStudent rankings under a transparent and separately governed methodology.**

---

## 31. Approval State

This document is a **final candidate pending merge into `main`**. Once merged, it becomes approved project documentation unless superseded by a governed decision.
