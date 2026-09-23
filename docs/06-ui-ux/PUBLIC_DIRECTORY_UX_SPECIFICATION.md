# UI-001 — Public Directory UX Specification

**Document ID:** UI-001  
**Title:** Public Directory UX Specification  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, ARC-001 through ARC-004, DB-001, API-001, MOD-002, MOD-019, MOD-020, MOD-023  
**Technology Status:** Technology-neutral

## 1. Purpose

Define the public user experience for the first Institutes Directory release while preserving the approved domain, URL, SEO, accessibility, data-quality and future-extension boundaries.

This document defines information and interaction behavior, not a frontend framework or component library.

## 2. UX Principles

The public experience should be:

- mobile-first;
- fast and understandable;
- search-oriented;
- accessible;
- trustworthy;
- transparent about missing information;
- consistent across institution and program pages;
- extensible without redesigning the domain model.

## 3. Primary Public Journey

The primary journey is:

`/institutes/ → Search/Discovery → Results → Institution → Campus/Program → Relevant Detail`

Users must be able to reach a useful institution profile without requiring every optional data domain to be populated.

## 4. Directory Home

The directory root should provide:

- clear directory purpose;
- prominent search;
- major discovery options;
- institution-type exploration;
- geographic exploration;
- useful explanatory content;
- links to governed directory sections.

The home page should not become a collection of thin keyword landing pages.

## 5. Search Experience

Search should accept:

- institution names;
- aliases;
- acronyms;
- spelling variations;
- city/location terms;
- institution types;
- program-related terms where supported.

Search should provide clear feedback when:

- no results exist;
- the query is ambiguous;
- results are filtered;
- some information is unavailable.

Search relevance must not be presented as institutional quality ranking.

## 6. Search Results

Each result should provide enough information to distinguish institutions, including where available:

- institution name;
- institution type;
- primary location;
- relevant campus;
- useful program context;
- lifecycle/trust indication where appropriate.

Results should lead to stable canonical entity pages.

## 7. Filters

Initial filters may include:

- institution type;
- province/territory;
- district;
- city;
- education level;
- discipline/program attributes where reliable.

Filters must map to governed data.

Missing information must not silently become an exclusion unless the user explicitly requests a known-value-only filter.

## 8. Institution Profile

The institution profile is the primary public entity page.

Canonical URL pattern:

`/institutes/{institution-slug}/`

The profile should present, where available:

1. institution identity;
2. type/classification;
3. overview;
4. location;
5. campuses;
6. programs;
7. admissions;
8. fees;
9. scholarships;
10. accreditation/recognition;
11. community information;
12. external signals;
13. related discovery.

Unavailable sections should be handled transparently rather than displaying fabricated content.

## 9. Institution Header

The profile header should establish identity immediately.

It may contain:

- official/current name;
- commonly used name;
- institution type;
- primary location;
- trust/status indicator;
- official website link where governed;
- key identity information.

The header must not imply ranking unless a separately governed ranking representation exists.

## 10. Campus Presentation

Campus information should remain visibly distinct from institution identity.

A campus section may show:

- campus name;
- campus type;
- location;
- address;
- geographic context;
- programs offered at that campus;
- campus-specific contact information where public.

Multiple campuses must remain individually understandable.

## 11. Program Presentation

Programs should be grouped under the institution while preserving the distinction between Program and Program Offering.

A program view may show:

- program name;
- level;
- award;
- discipline;
- available campuses;
- delivery mode;
- current offering information.

Users should be able to understand when a program is not currently offered even though the program identity remains historical.

## 12. Admissions Presentation

Admission information should emphasize time context.

Where available:

- current intake;
- opening date;
- closing date;
- status;
- eligibility;
- application destination;
- relevant program/offering.

Historical admissions should be visually distinguishable from current information.

## 13. Fees Presentation

Fees are optional.

The UI should distinguish:

- known;
- partially known;
- unknown;
- not published;
- historical;
- stale;
- conflicting where appropriate.

The absence of a fee section must not imply that an institution has no fees or that fee information is unavailable from the institution itself.

## 14. Scholarships Presentation

Scholarship information should be separate from fees.

Where available, show:

- scholarship name;
- eligibility;
- benefit;
- application period;
- renewal/duration;
- applicable institution/program scope.

Unknown scholarship information must not be rendered as “no scholarships”.

## 15. Accreditation / Recognition

The interface should clearly identify the type and authority of a status.

For example, accreditation, recognition, affiliation and licensing should not be displayed as interchangeable badges.

The source/authority and applicable scope should be available where appropriate.

## 16. Trust and Data Freshness

The public experience should communicate meaningful data status without overwhelming users.

Possible labels include:

- Verified;
- Current;
- Historical;
- Community reported;
- Information unavailable.

Trust labels must describe data governance, not advertise institutional quality.

## 17. Community Content

Future reviews, ratings and recommendations must be visually distinguished from canonical institutional facts.

Community content should identify its nature and, where governed, contributor context and experience period.

The absence of reviews must not imply positive or negative reputation.

## 18. External Rankings

Third-party rankings must remain attributed to their publisher.

The UI should preserve:

- publisher;
- edition/year;
- scope;
- rank/score/band;
- relevant methodology context where useful.

External ranking information must not appear to be an eStudent-generated fact.

## 19. Search vs Ranking vs Recommendation

The interface must use distinct language and visual treatment for:

- search results;
- eStudent rankings;
- personalized recommendations.

A search result order must never silently become an institutional ranking.

## 20. Comparison

Comparison is a future capability.

The UI architecture should leave room for comparison without making it a core MVP dependency.

Comparison must present factual dimensions side-by-side rather than silently converting every difference into an overall winner.

## 21. Nearby Discovery

Nearby discovery is geographic.

It should use location context to identify nearby institutions/campuses.

Distance or proximity must not be represented as quality.

## 22. Recommendations

Future recommendations should show understandable reasons based on explicit user preferences and governed data.

Examples of reason categories:

- location match;
- program match;
- delivery mode;
- admission timing;
- stated budget where voluntarily provided.

Recommendations must disclose meaningful data limitations.

## 23. Missing Data UX

Missing information should be explicit and calm.

Examples:

- “Information not available”
- “Not published”
- “Awaiting verification”
- “Historical information”
- “Last verified…”

Do not fill important missing facts with AI-generated guesses.

## 24. Error and Empty States

The UX should provide useful recovery paths for:

- no search results;
- invalid filters;
- unavailable institution;
- temporarily unavailable information;
- missing optional sections.

Users should be able to broaden or revise discovery without losing the overall directory context.

## 25. Navigation

The public structure should support:

- directory root;
- geography navigation;
- institution type;
- institution profile;
- campus;
- program;
- relevant admissions;
- related institutions/programs.

Breadcrumbs should reflect the canonical information architecture.

## 26. Internal Linking

Internal links should connect genuinely related governed content.

Examples:

- institution → campuses;
- institution → programs;
- program → institutions offering it;
- campus → programs;
- geography → institutions;
- institution → related discovery.

Links should not be generated merely to increase page count.

## 27. Accessibility

The public interface should target broadly accessible interaction, including:

- keyboard navigation;
- readable typography;
- meaningful headings;
- semantic structure;
- accessible forms;
- understandable error states;
- non-color-only status indicators;
- accessible mobile interactions.

Specific accessibility compliance target can be selected during implementation.

## 28. Mobile-First Behavior

Mobile must be a first-class experience, not a compressed desktop layout.

Priority should be:

1. search;
2. identification;
3. essential facts;
4. programs;
5. admissions;
6. navigation to deeper information.

Large tables and comparison features should use mobile-appropriate interaction patterns.

## 29. Performance UX

The interface should prioritize fast access to useful information.

Performance planning should account for:

- search response;
- institution page rendering;
- media;
- mobile networks;
- progressive enhancement.

Exact performance budgets will be defined in implementation/testing documentation.

## 30. Forms and User Contributions

Future contribution flows may support:

- corrections;
- institution submissions;
- reviews;
- recommendations.

Forms must clearly distinguish a submission from a verified canonical fact.

Submission does not equal publication.

## 31. Advertising and Sponsored Content

If monetization is introduced, paid placement must be clearly distinguishable from neutral search, ranking or recommendation.

Sponsored content must not silently alter the meaning of governed discovery results.

## 32. AI UX

Future AI features may include:

- natural-language search;
- summaries;
- comparison assistance;
- recommendations;
- data-update assistance.

AI-generated output must be clearly grounded in governed information and must not replace canonical source data.

## 33. Future Extensibility

The UX should leave room for:

- student accounts;
- saved institutions;
- alerts;
- admission tracking;
- comparison;
- recommendation;
- mobile applications;
- voice search;
- AI agents.

These are future capabilities and do not block MVP.

## 34. MVP Screen Set

The initial implementation should prioritize:

1. Directory home
2. Search/results
3. Institution profile
4. Campus section
5. Program section
6. Basic geography/filter discovery
7. Required system/empty/error states

## 35. Explicit Non-Approval

This document does not select:

- frontend framework;
- component library;
- CSS framework;
- design system vendor;
- map provider;
- analytics provider;
- accessibility testing vendor.

## 36. Next Documents

- SEO-001 — SEO Implementation Specification
- TST-001 — MVP Testing Strategy

## Approval State

This document is a **final candidate pending merge into `main`**.
