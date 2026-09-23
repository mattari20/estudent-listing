# MOD-015 — Institutes Directory: Reviews, Feedback and Reputation Governance

**Document ID:** MOD-015  
**Title:** Institutes Directory — Reviews, Feedback and Reputation Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-006, MOD-007, MOD-008, MOD-009, MOD-014  
**Related Documents:** MOD-006, MOD-014  
**Next Documents:** Rankings and external signals governance, trust and moderation operations

---

## 1. Purpose

This document governs student/user reviews, feedback, ratings, reputation signals and institution responses.

Reviews are user-generated information and must remain separate from verified institutional facts.

A review must never silently modify canonical institution, campus, program, admission, fee or accreditation data.

---

## 2. Core Principle

**User opinion is not canonical institutional fact.**

Reviews may describe experiences, perceptions or opinions. They can be useful discovery signals but must not be treated as authoritative evidence for factual claims without independent verification.

Likewise, the absence of reviews does not establish positive or negative quality.

---

## 3. Review Scope

Reviews may concern:

- institution;
- campus;
- program;
- facilities;
- teaching experience;
- administration;
- admissions experience;
- student services;
- accommodation;
- transport;
- other clearly defined student-facing experiences.

The target of a review must be identifiable where practical.

---

## 4. Review Identity and History

A review should conceptually retain:

- author/account identity representation;
- target;
- publication time;
- content;
- rating if supported;
- edit history;
- moderation state;
- response history.

Edits and moderation actions should not destroy auditability.

---

## 5. Ratings

If ratings are supported, their meaning must be clearly defined.

A numerical rating should not automatically be interpreted as academic quality, accreditation quality, employability or institutional ranking.

Different dimensions should remain distinct where used.

---

## 6. Verified Facts vs User Claims

Reviews may contain factual claims.

Examples:

- fee amount;
- admission deadline;
- campus facility;
- accreditation status.

Such claims must not become canonical facts merely because a user submitted them.

Material factual claims should be separately verified under MOD-006 before being promoted into canonical data.

---

## 7. Institution Responses

Institutions may be permitted to respond to reviews where a future governed workflow supports verified institutional identity.

An institutional response is still a response, not automatic proof that the institution's statement is fact.

---

## 8. Moderation

Moderation should distinguish:

- published;
- pending review;
- hidden;
- removed;
- disputed;
- spam;
- abusive;
- duplicate;
- potentially fraudulent;
- legally sensitive;
- escalated.

Moderation must be governed by transparent rules and auditability.

---

## 9. Fraud and Manipulation

The platform should address:

- fabricated reviews;
- coordinated review manipulation;
- duplicate submissions;
- incentives improperly influencing ratings;
- impersonation;
- automated submissions;
- retaliation;
- review bombing.

Detection may use rules or future automated assistance, but final governance should remain auditable.

---

## 10. Privacy and Personal Information

Reviews must not expose unnecessary personal or sensitive information.

The platform should provide mechanisms for handling:

- personal contact information;
- private academic records;
- private application information;
- financial information;
- allegations involving identifiable individuals.

Detailed privacy and security governance should be specified separately.

---

## 11. Reputation Signals

Derived reputation indicators may be calculated from reviews only when methodology is explicitly governed.

Such signals must preserve:

- source population;
- review count;
- time period;
- methodology;
- limitations.

A reputation signal must not be presented as an official institutional quality rating.

---

## 12. Missing Reviews

No reviews does not mean:

- good institution;
- bad institution;
- low quality;
- high quality;
- unverified institution.

The platform should clearly distinguish absence of data from negative evidence.

---

## 13. Conflicting Experiences

Different students may report different experiences.

The platform should preserve diversity of experience rather than treating one review as universally representative.

Summaries should avoid claiming that one user's experience describes every student.

---

## 14. AI-Assisted Review Intelligence

AI may assist with:

- spam detection;
- duplicate detection;
- topic extraction;
- sentiment/topic summaries;
- moderation queues;
- identifying claims requiring verification.

AI must not fabricate reviews, manipulate ratings or convert unverified allegations into canonical facts.

AI-generated summaries must remain distinguishable from original user submissions.

---

## 15. Search and SEO

Reviews may support discovery and user research.

However:

- review content must not replace canonical institution information;
- search indexes must preserve moderation state;
- removed/private content must not remain publicly represented through derived caches;
- SEO summaries must not turn allegations into factual claims.

---

## 16. Data Quality

Review-related quality dimensions may include:

- authenticity signals;
- target accuracy;
- temporal validity;
- moderation state;
- duplicate state;
- author/account integrity;
- content completeness;
- claim verification state.

Review count alone is not a measure of institutional quality.

---

## 17. Relationship to Accreditation and Facts

A negative review cannot establish that an institution is unrecognized.

A positive review cannot establish accreditation.

A review about teaching quality cannot establish official academic status.

Canonical regulatory and institutional facts remain governed by authoritative evidence under MOD-006 and MOD-014.

---

## 18. Explicit Non-Approval of Implementation

This document does not approve:

- rating algorithms;
- moderation software;
- user identity systems;
- database schemas;
- review APIs;
- AI providers;
- recommendation engines;
- frontend implementation;
- hosting/deployment architecture.

---

## 19. Future Evolution

Future documents may define:

- review submission policy;
- moderation policy;
- verified-student mechanisms;
- institution claim/response workflow;
- reputation methodology;
- anti-abuse systems;
- privacy/security controls;
- implementation schemas.

---

## 20. Approval State

This document is a **final candidate pending merge into `main`**. Once merged, it becomes approved project documentation unless superseded by a governed decision.
