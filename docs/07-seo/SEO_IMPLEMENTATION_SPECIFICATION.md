# SEO-001 — SEO Implementation Specification

**Document ID:** SEO-001  
**Title:** SEO Implementation Specification  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, ARC-001 through ARC-004, DB-001, API-001, UI-001, MOD-002, MOD-006, MOD-007, MOD-019, MOD-020  
**Technology Status:** Technology-neutral

## 1. Purpose

Define the implementation-facing SEO rules for the Institutes Directory while preserving canonical identity, data quality, URL stability and long-term search-engine compatibility.

## 2. Permanent Public Root

The permanent public module root is:

`https://estudent.pk/institutes/`

This root must not be casually changed.

## 3. Canonical Institution URL

The canonical institution URL pattern is:

`/institutes/{institution-slug}/`

The slug is a public representation of a stable institution identity.

Identity must survive slug changes.

## 4. URL Principles

Public URLs should be:

- stable;
- readable;
- meaningful;
- concise;
- deterministic;
- independent of volatile data.

Do not place temporary admission dates, rankings, prices or other volatile values in the permanent institution identity URL.

## 5. Slug Governance

Slugs should normally be derived from governed names.

The implementation should support:

- stable slug assignment;
- collision handling;
- historical slug recognition;
- redirect handling;
- transliteration/alias considerations;
- reserved words;
- safe characters.

Changing a slug must not create a new institution identity.

## 6. Historical URLs

When a public URL changes, the previous URL should have a governed continuity strategy.

Where appropriate:

`old URL → permanent redirect → canonical URL`

Historical URLs must not be casually deleted when users or search engines may still depend on them.

## 7. Page Types

Potential indexable page types include:

- directory root;
- institution;
- campus where independently useful;
- program where independently useful;
- meaningful geographic discovery pages;
- meaningful taxonomy pages;
- high-value admissions/scholarship pages where content is substantial.

A page type should become indexable only when it provides genuine standalone value.

## 8. Thin-Page Prevention

Do not mass-create pages for every combination of:

- city;
- institution type;
- program;
- keyword;
- filter.

Filter combinations should not automatically become indexable landing pages.

## 9. Canonicalization

Every indexable page should have a clear canonical URL.

Canonicalization must prevent duplicate representations caused by:

- query parameters;
- sorting;
- filtering;
- tracking parameters;
- alternate navigation paths;
- duplicate slugs;
- historical URLs.

## 10. Search and Filter URLs

Search and filter states are discovery mechanisms.

They should not automatically become canonical indexable pages.

Only governed, high-value landing pages should be deliberately exposed for indexing.

## 11. Metadata

Indexable pages should generate metadata from governed content.

At minimum, implementation should support:

- title;
- meta description where appropriate;
- canonical URL;
- robots/indexability state;
- relevant social metadata.

Metadata must not invent facts.

## 12. Title Construction

Titles should identify the actual page/entity and useful context.

Institution titles should prioritize:

- official/current institution identity;
- meaningful location or category context where useful;
- eStudent directory context where appropriate.

Avoid keyword stuffing and repetitive boilerplate.

## 13. Meta Descriptions

Descriptions should summarize actual page content.

They may include:

- institution type;
- location;
- programs;
- admissions;
- other available information.

Unknown information must not be fabricated to make a description appear complete.

## 14. Structured Data

Structured data should represent governed page facts.

Potential entity types may include appropriate schema representations for:

- educational organizations;
- web pages;
- breadcrumbs;
- programs/offers where valid;
- events/admissions where appropriate;
- user-generated ratings where genuinely eligible.

The exact schema vocabulary and implementation details require validation during build.

## 15. Structured Data Integrity

Structured data must not contain claims that are not supported by visible/governed page content.

External rankings must remain attributed.

Community ratings must remain genuinely user-generated and must satisfy applicable structured-data requirements before publication.

## 16. Breadcrumbs

Public pages should use breadcrumbs consistent with the canonical information architecture.

Examples:

`Institutes → Institution`

`Institutes → Province → City → Institution`

`Institutes → Institution → Program`

Breadcrumb structure must not imply an identity relationship that does not exist.

## 17. Internal Linking

Internal links should improve discovery and establish meaningful relationships.

Priority links include:

- directory → institution;
- institution → campus;
- institution → programs;
- program → institutions;
- geography → institutions;
- institution → relevant admissions/scholarships;
- related governed entities.

Avoid automated link generation based solely on keyword matching.

## 18. Indexability Rules

A page should be indexable when it is:

- useful;
- sufficiently complete for its purpose;
- canonical;
- stable;
- governed;
- not a duplicate;
- not misleading.

Pages may be excluded where they are:

- thin;
- duplicate;
- temporary;
- purely internal;
- unresolved;
- low-value parameter combinations.

## 19. Verification and Indexability

Unverified information must not automatically be treated as false.

However, high-impact public claims should have appropriate verification/provenance controls before being used as authoritative SEO content.

The implementation must distinguish discovery records from canonical public facts.

## 20. Lifecycle and SEO

Institution lifecycle states should affect indexability appropriately.

Examples:

- active institution → normal canonical page;
- temporarily closed → page may remain useful with clear status;
- historical institution → preserve useful historical page where justified;
- merged/superseded → preserve continuity and explain successor relationship where appropriate;
- rejected candidate → do not publish as canonical institution.

## 21. Program SEO

Program pages should only be indexable where the program has meaningful standalone public value.

Program identity and current offering must remain distinct.

A historical program should not be presented as a current admission opportunity.

## 22. Admissions SEO

Admission pages are time-sensitive.

Current admission content should clearly expose:

- cycle;
- dates;
- status;
- applicable program/offering.

Historical admission pages may remain accessible when useful, but must not be mistaken for current opportunities.

## 23. Fees and Scholarship SEO

Fee and scholarship information may enrich pages but must not be required for an institution page to exist.

Missing data should not produce misleading SEO text.

Historical or stale financial information must be labelled appropriately.

## 24. Geography SEO

Geographic landing pages should be created only where they serve genuine discovery intent and contain useful content.

Geography must be governed rather than generated from arbitrary strings.

## 25. Sitemaps

Sitemaps should contain canonical public URLs that are intended for indexing.

Do not include:

- duplicate URLs;
- non-canonical filter URLs;
- unresolved internal records;
- intentionally non-indexable pages.

Sitemap generation should derive from governed public visibility.

## 26. Robots and Crawl Controls

Crawl controls should protect:

- internal tools;
- private operations;
- duplicate query states;
- non-public data;
- unnecessary crawl traps.

Robots controls must not be used as a substitute for canonicalization.

## 27. Pagination and Crawlability

Large listings should support predictable navigation.

Pagination must preserve access to canonical entities.

The implementation should avoid infinite crawl paths caused by unrestricted combinations of filters and sorting.

## 28. Images and Media

Public media should have:

- meaningful filenames where appropriate;
- useful alternative text;
- stable references;
- appropriate dimensions/optimization;
- source/provenance where required.

Images must not be used to imply facts that are not represented in governed data.

## 29. Performance and Core UX

SEO implementation must support a fast, accessible experience.

Performance work should prioritize:

- mobile delivery;
- useful content visibility;
- stable layout;
- efficient media;
- efficient page generation;
- search/listing responsiveness.

Exact performance budgets belong in TST-001 and later implementation specifications.

## 30. SEO and Search

Internal directory search is a user-discovery function.

It must not be confused with external search-engine indexability.

Search result pages should not automatically become SEO landing pages.

## 31. SEO and Ranking

Search-engine SEO must remain separate from eStudent institutional ranking.

An SEO landing page does not imply institutional quality.

Ranking methodology remains governed by MOD-022.

## 32. SEO and Recommendations

Recommendation pages should not be treated as neutral canonical institutional pages.

If future personalized pages are introduced, indexability must be explicitly governed.

## 33. AI-Generated Content

AI may assist with:

- summaries;
- metadata drafting;
- classification;
- content maintenance.

AI-generated public content must remain grounded in canonical data.

AI must not invent:

- fees;
- admissions;
- accreditation;
- merit;
- rankings;
- institutional claims.

## 34. Freshness

SEO representations should reflect appropriate freshness for their content.

Time-sensitive sections such as admissions should receive more frequent review than stable identity information.

Stale information must not silently appear current.

## 35. Duplicate Content

Duplicate pages should be controlled at the canonical identity and information-architecture level.

Do not solve duplication by creating large numbers of near-identical pages.

## 36. Search Engine Monitoring

Future operations should monitor:

- indexing;
- crawl errors;
- canonical issues;
- structured-data errors;
- broken URLs;
- traffic/search trends;
- important page changes.

Search Console integration is a future operational capability.

## 37. SEO Change Management

Changes affecting:

- URL structure;
- canonicalization;
- indexability;
- structured data;
- metadata generation;
- sitemap behavior

must be tested before release.

Major URL changes require impact assessment and migration planning.

## 38. MVP SEO Requirements

The MVP must include:

1. permanent `/institutes/` root;
2. stable institution URLs;
3. canonical tags;
4. metadata;
5. breadcrumbs;
6. internal linking;
7. sitemap readiness;
8. crawl controls;
9. basic structured-data readiness;
10. historical URL/redirect strategy.

## 39. Explicit Non-Approval

This document does not select:

- SEO plugin;
- framework;
- rendering strategy;
- structured-data library;
- analytics provider;
- search engine;
- hosting/CDN.

## 40. Next Document

**TST-001 — MVP Testing Strategy** is the final planned implementation-facing governance document before the architecture freeze.

## Approval State

This document is a **final candidate pending merge into `main`**.
