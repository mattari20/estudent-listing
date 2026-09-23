# MOD-016 — Institutes Directory: Rankings and External Signals Governance

**Document ID:** MOD-016  
**Title:** Institutes Directory — Rankings and External Signals Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-006, MOD-007, MOD-009, MOD-014, MOD-015  
**Related Documents:** MOD-006, MOD-014, MOD-015  
**Next Documents:** Data quality and operational governance, search/discovery governance, SEO and structured-data specifications

---

## 1. Purpose

This document governs external rankings, league tables, awards, reputation indicators, comparative datasets and other third-party signals associated with institutions or programs.

External signals must remain distinct from eStudent's canonical facts and must not automatically become eStudent rankings.

---

## 2. Core Principle

**A third-party ranking is an external claim, not an eStudent fact.**

The directory may publish or reference external rankings when source, edition, methodology and scope are clear.

Different ranking systems may produce different results because their methodologies differ.

The platform must preserve the source's identity and context rather than presenting a ranking as an objective universal truth.

---

## 3. External Signal Types

Possible external signals include:

- university rankings;
- program rankings;
- subject rankings;
- accreditation ratings;
- awards;
- reputation surveys;
- research indicators;
- employment indicators;
- external review datasets;
- government classifications;
- other published comparative measures.

Each type should retain its own meaning.

---

## 4. Ranking Identity

A ranking record should conceptually preserve:

- publisher;
- ranking name;
- edition/year;
- methodology reference;
- scope;
- population;
- geographic coverage;
- subject/program scope;
- publication date;
- source.

A new annual edition should not overwrite historical editions.

---

## 5. Rank vs Score

The platform must distinguish:

- rank;
- score;
- band;
- tier;
- percentile;
- indicator;
- award;
- classification.

A score from one publisher must not be compared directly with a score from another publisher without explicit methodology.

---

## 6. Institution and Program Scope

A ranking may apply to:

- institution;
- campus;
- program;
- subject;
- discipline;
- geographic region;
- category.

The scope must remain attached to the signal.

An institution-level ranking must not be represented as a ranking of every program.

---

## 7. Historical Rankings

Historical rankings should be preserved where useful.

The platform should retain:

- edition;
- publication date;
- rank/score;
- methodology context;
- source;
- historical status.

Historical rank must not be presented as current rank.

---

## 8. Missing Ranking Data

No ranking record does not mean:

- unranked quality;
- poor quality;
- low reputation;
- low accreditation;
- low academic standard.

It may simply mean that the institution or program was not included, not assessed, or no data was captured.

The platform must distinguish absence of data from negative evidence.

---

## 9. Methodology and Context

Where possible, the platform should link or reference methodology information.

Users should be able to understand:

- what was measured;
- who was included;
- which edition applies;
- whether the signal is institution- or program-specific;
- important limitations.

The platform must not remove methodology context when presenting an external ranking.

---

## 10. External Rankings vs eStudent Search

External rankings may be one discovery signal among many.

Search and comparison systems must not silently transform an external ranking into an eStudent quality score.

If a future internal ranking is created, it requires separate governance and methodology.

---

## 11. Reputation Signals

Reputation indicators may originate from:

- surveys;
- review systems;
- external datasets;
- published reports.

These signals must remain attributable to their source.

User reviews under MOD-015 must not automatically become an external ranking.

---

## 12. Awards and Distinctions

Awards may be represented with:

- awarding body;
- award name;
- recipient;
- category;
- year;
- scope;
- evidence.

An award should not automatically be interpreted as accreditation, regulatory recognition or overall institutional quality.

---

## 13. Conflicting External Signals

Different sources may produce different results.

The platform should preserve multiple signals rather than silently selecting one as the definitive ranking.

Where comparison is presented, the source and methodology must remain visible.

---

## 14. AI-Assisted External Signal Intelligence

AI may assist with:

- extracting ranking results;
- identifying editions;
- matching institutions;
- detecting changes;
- identifying missing editions;
- summarizing methodologies;
- flagging possible conflicts.

AI must not invent rankings or convert external signals into canonical institutional facts.

---

## 15. Search, Comparison and Recommendations

External signals may be used as derived inputs for future search or recommendation features.

Any future recommendation system must preserve the distinction between:

- source ranking;
- verified factual data;
- user preferences;
- derived recommendation logic.

No single external ranking should silently become the platform's universal ordering.

---

## 16. SEO and Structured Data

Ranking information may appear in public pages where useful, but SEO representations must preserve:

- source;
- edition;
- scope;
- historical/current state.

Structured data must not misrepresent a third-party ranking as an official eStudent ranking.

---

## 17. Data Quality

External signal records should be evaluated for:

- source identity;
- edition accuracy;
- scope;
- methodology availability;
- institution/program matching;
- publication date;
- historical validity;
- completeness;
- traceability.

---

## 18. Explicit Non-Approval of Implementation

This document does not approve:

- ranking algorithms;
- internal scoring;
- recommendation algorithms;
- database schemas;
- external API integrations;
- scraping systems;
- AI providers;
- search implementation;
- frontend components;
- hosting/deployment architecture.

---

## 19. Future Evolution

Future documents may define:

- external source registry;
- ranking import governance;
- internal comparison methodology;
- recommendation governance;
- ranking-change monitoring;
- source licensing considerations;
- structured-data implementation;
- APIs.

---

## 20. Approval State

This document is a **final candidate pending merge into `main`**. Once merged, it becomes approved project documentation unless superseded by a governed decision.
