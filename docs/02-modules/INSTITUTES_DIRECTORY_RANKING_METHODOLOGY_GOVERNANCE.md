# MOD-022 — Institutes Directory — Ranking Methodology Governance

**Document ID:** MOD-022  
**Title:** Institutes Directory — Ranking Methodology Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** MOD-016, MOD-017, MOD-018, MOD-019, MOD-021

## 1. Purpose
Defines governance for any future eStudent institutional, campus, program or offering ranking.

## 2. Core Separation
Institution ranking, search-result relevance, external rankings and personalized recommendations are separate derived representations.

## 3. Ranking Scope
Every ranking must declare:
- entity scope;
- geography;
- discipline/program scope where applicable;
- time period;
- population;
- methodology;
- data cutoff;
- eligibility for inclusion.

## 4. Eligible Signals
Potential signals may include:
- verified institutional attributes;
- program availability;
- accreditation/recognition evidence;
- admissions information;
- community ratings;
- external ranking signals;
- data-quality indicators;
- other explicitly governed measures.

No signal is automatically a quality measure merely because it is available.

## 5. Community Ratings
MOD-017 ratings may contribute as one signal. Sample size, contributor context, verification, recency and manipulation controls must be considered. Raw averages must not automatically determine rank.

## 6. External Rankings
External rankings remain attributed external signals. They must not be copied into an eStudent ranking without an explicit methodology.

## 7. Missing Data
Missing data must not silently become zero. Methodologies must define how incomplete records are treated and disclose material coverage limitations.

## 8. Normalization
Different scales require governed normalization before combination. The methodology must be documented and reproducible.

## 9. Weighting
Weights, thresholds, minimum samples and time-decay rules require explicit approval. This document approves none of them.

## 10. Statistical Reliability
Small samples and unstable signals must not dominate results. Future methodology should address uncertainty, sample size and robustness.

## 11. Temporal Integrity
Ranking outputs must retain their period and data snapshot. Historical rankings must not be presented as current.

## 12. Anti-Manipulation
Fraud, coordinated submissions, duplicates, self-promotion and other governed abuse must be excluded or handled according to approved rules.

## 13. Transparency
Published rankings should explain major methodology elements, source categories, period and limitations.

## 14. No Hidden Ranking
Search ordering, advertising, sponsored placement and personalized discovery must not silently masquerade as an objective ranking.

## 15. AI Boundary
AI may assist calculation preparation, anomaly detection and explanation, but must not invent inputs or silently alter methodology.

## 16. Change Governance
Changes to ranking methodology require versioning, documentation and an effective date. Historical results should remain reproducible where practical.

## 17. Non-Approval of Implementation
No formula, weight, score, database, algorithm, AI provider or ranking engine is approved by this document.

## Approval State

This document is a **final candidate pending merge into `main`**. Once merged, it becomes approved project documentation unless superseded by a governed decision.
