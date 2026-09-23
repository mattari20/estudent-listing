# ADR-001 — MVP Architecture Freeze and Implementation Baseline

**Document ID:** ADR-001  
**Title:** MVP Architecture Freeze and Implementation Baseline  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001 through ARC-004, DB-001, API-001, UI-001, SEO-001, TST-001, MOD-001 through MOD-026  
**Decision Type:** Architecture / Implementation Readiness

## 1. Decision

The Institutes Directory is ready to move from documentation-first architecture work into controlled MVP implementation.

The approved architecture baseline is now frozen for the MVP at the conceptual and governance level.

Implementation may begin, provided that technical choices remain traceable to approved requirements and do not silently alter frozen domain meanings.

## 2. Frozen Foundations

The following are protected during MVP implementation:

- permanent public root: `/institutes/`;
- stable institution identity;
- stable campus identity;
- Program vs Program Offering distinction;
- Institution vs Campus distinction;
- governed Geography;
- Source → Evidence → Assertion → Verification provenance;
- lifecycle/history;
- optional-data semantics;
- canonical vs derived data boundary;
- Search vs Ranking vs Recommendation separation;
- public URL continuity;
- SEO canonicalization;
- security/privacy boundaries;
- moderation/community separation;
- future API compatibility principles;
- AI as a replaceable capability rather than a canonical dependency.

## 3. MVP Scope

The first implementation should prioritize:

1. canonical institution data;
2. campus and geography;
3. program/program offering foundations;
4. provenance and verification;
5. public institution discovery;
6. search and filters;
7. canonical SEO pages;
8. operational review/correction;
9. testing and release controls.

The following remain non-blocking future capabilities:

- advanced AI;
- public ranking;
- personalized recommendations;
- full review ecosystem;
- large-scale automated crawling;
- mobile apps;
- broad partner APIs;
- advanced comparison;
- predictive analytics.

## 4. Technology Selection

Technical implementation choices may now be evaluated and selected against the approved requirements.

Selection must consider:

- maintainability;
- replaceability;
- long-term support;
- security;
- performance;
- scalability;
- developer availability;
- operational complexity;
- migration capability;
- total lifecycle cost.

No particular technology is selected by this ADR.

## 5. Change Control

Normal implementation changes remain allowed.

Changes are categorized as:

- bug fix;
- non-breaking enhancement;
- data correction;
- operational improvement;
- architectural change;
- breaking change;
- emergency/security change.

Changes that alter frozen concepts require an ADR or RFC before implementation.

## 6. Protected Public Contracts

The following require explicit compatibility review before alteration:

- permanent public URL meaning;
- stable entity identifiers;
- public API contracts once released;
- lifecycle semantics;
- canonical data meaning;
- historical continuity;
- provenance semantics.

## 7. Data Migration Rule

Any migration must preserve, where applicable:

- stable identity;
- relationships;
- provenance;
- verification;
- historical state;
- effective periods;
- canonical URL continuity.

Destructive migration is not acceptable merely for implementation convenience.

## 8. MVP Architecture Rule

The first implementation should be modular enough that future capabilities can consume the canonical foundation without rewriting the core.

Future layers should extend, not replace:

- canonical data;
- access boundaries;
- search;
- SEO;
- governance.

## 9. AI Rule

AI may be introduced at any stage where it provides measurable utility, but core directory functionality must remain operable without a specific AI provider.

AI outputs must remain governed and attributable.

## 10. Search Rule

Search implementation may evolve.

However:

- search relevance is not institutional ranking;
- search indexes remain derived;
- search must be rebuildable from canonical data;
- missing optional data must not automatically become negative data.

## 11. SEO Rule

SEO implementation may evolve without changing the permanent directory root or stable entity identity.

URL changes require migration planning, redirect handling and regression testing.

## 12. Testing Rule

No MVP production release should bypass the quality gates defined in TST-001.

Critical failures affecting:

- identity;
- security;
- privacy;
- data corruption;
- permanent URLs

are release blockers.

## 13. Documentation Rule

Implementation documentation must remain synchronized with actual decisions.

If implementation exposes a requirement gap, do not silently modify the architecture.

Instead:

1. record the gap;
2. assess impact;
3. update the appropriate specification;
4. create an ADR/RFC when required;
5. then implement.

## 14. Definition of Architecture Freeze

Architecture freeze means the MVP's conceptual contracts are protected.

It does **not** mean:

- no bug fixes;
- no UI refinement;
- no performance optimization;
- no technology replacement;
- no non-breaking improvements.

It means changes affecting foundational meaning require controlled review.

## 15. Implementation Entry Gate

Before the first production code is accepted, the implementation team should have:

- selected technology against requirements;
- documented the selected stack;
- defined local/development/staging/production boundaries;
- established source-control workflow;
- established secrets/security handling;
- established backup/recovery approach;
- established initial test execution;
- established migration discipline.

These are implementation details, not changes to the frozen domain.

## 16. Baseline Documents

The implementation baseline consists of:

- FND-001 — Master Project Index
- FND-002 — Project Constitution
- ARC-001 — System Architecture
- ARC-002 — Domain Model and Bounded Contexts
- ARC-003 — Data Architecture and Lifecycle
- ARC-004 — Implementation Readiness and MVP Blueprint
- DB-001 — Canonical Data Model Blueprint
- API-001 — Initial API Contract Blueprint
- UI-001 — Public Directory UX Specification
- SEO-001 — SEO Implementation Specification
- TST-001 — MVP Testing Strategy
- MOD-001 through MOD-026 — approved module governance

## 17. Decision Rationale

The project has now completed the documentation necessary to protect the most important long-term contracts before coding.

Beginning implementation earlier would risk choosing technology around incomplete requirements.

Beginning implementation now allows technical choices to be made against a defined domain, data lifecycle, access boundary, UX, SEO and testing strategy.

## 18. Consequences

### Positive

- lower architectural ambiguity;
- reduced rework risk;
- protected public URLs;
- protected entity identity;
- clearer implementation scope;
- safer future AI/API/mobile expansion;
- better long-term maintainability.

### Trade-offs

- some future features remain intentionally deferred;
- implementation must follow governance;
- major architectural shortcuts require formal review.

## 19. Next Phase

The project now enters:

**Phase: MVP Technical Foundation and Implementation**

The next work should be implementation planning and technical stack selection, followed by the first controlled build.

## 20. Approval State

This ADR is a **final candidate pending merge into `main`**.

Once merged, the MVP architecture freeze becomes the governing implementation baseline until superseded by a later approved ADR.
