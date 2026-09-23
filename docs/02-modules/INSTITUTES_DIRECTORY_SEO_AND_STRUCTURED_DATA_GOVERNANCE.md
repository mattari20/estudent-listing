# MOD-020 — Institutes Directory: SEO and Structured Data Governance

**Document ID:** MOD-020  
**Title:** Institutes Directory — SEO and Structured Data Governance  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** FND-001, FND-002, ARC-001, ARC-002, ARC-003, MOD-002, MOD-003, MOD-004, MOD-005, MOD-006, MOD-007, MOD-008, MOD-009, MOD-010, MOD-011, MOD-012, MOD-013, MOD-014, MOD-015, MOD-016, MOD-017, MOD-018, MOD-019  
**Related Documents:** Institute directory information architecture, identity, lifecycle, data quality, search and discovery governance  
**Next Documents:** API and Data Access Governance; Ranking and Recommendation Methodology

---

## 1. Purpose

This document establishes long-term governance for public search-engine optimization (SEO), canonical URLs, indexability, structured data and machine-readable representations of the Institutes Directory.

SEO is a **derived publication layer**. It must follow canonical data and governance rules rather than become a source of truth.

---

## 2. Core Principle

The platform should optimize for:

- durable URLs;
- accurate information;
- useful search intent coverage;
- clear entity identity;
- transparent source and lifecycle states;
- machine-readable consistency;
- long-term backward compatibility.

SEO must never justify publishing unsupported facts.

---

## 3. Permanent Module Root

The permanent public module root is:

**`/institutes/`**

Future SEO structures must remain compatible with this root.

Changes to permanent URL structures require governed architectural review.

---

## 4. Canonical Identity

Public pages must correspond to canonical entities.

Examples include:

- institution;
- campus where independently useful;
- program;
- program offering;
- approved geographic discovery page;
- approved educational category.

A page must not create a false independent entity merely to capture a keyword.

---

## 5. Institution URLs

The canonical institution representation should follow the governed pattern:

**`/institutes/{institution-slug}/`**

The stable underlying institution identity must remain independent from the slug.

Slug changes must not create a new institution identity.

---

## 6. Campus URLs

Campus-specific public pages may be created where there is sufficient independent user value.

Campus URLs must remain subordinate to the institution/campus identity model and must not confuse a campus with a separate institution.

Multiple campuses should remain distinguishable.

---

## 7. Program URLs

Program pages may be published where program information is sufficiently meaningful and stable.

Program identity and program offering must remain separate.

A time-specific admission offering should not automatically create a permanently canonical program identity.

---

## 8. Admission URLs

Admission information may be represented as part of an institution/program page or through dedicated temporal representations where justified.

Historical admissions must not be mistaken for current admissions.

Short-lived campaign URLs should not replace durable canonical entity URLs.

---

## 9. Scholarship URLs

Scholarship pages may be published where they provide independent search value.

Scholarship information must retain scope and temporal context.

A scholarship page must not imply universal availability when the scholarship is restricted to a specific institution, program, campus, intake or applicant category.

---

## 10. Geographic SEO

Geographic pages may represent approved discovery dimensions such as:

- province/territory;
- district;
- city;
- locality/area;
- institution type;
- program/discipline;
- other governed combinations.

Geographic SEO pages must provide genuine useful content.

Mass-generated low-value combinations must not be created solely for keyword coverage.

---

## 11. Taxonomy SEO

Taxonomy pages may represent governed categories such as:

- institution type;
- education level;
- discipline;
- program family;
- delivery mode;
- other approved classifications.

Taxonomy labels must remain consistent with MOD-004.

---

## 12. Search and SEO Separation

Internal search results and public SEO landing pages are different representations.

Not every search result requires a crawlable public URL.

SEO pages should be created only where they have durable user/search value.

---

## 13. Canonical URL Governance

Each public entity representation should have one canonical URL.

Canonicalization must account for:

- aliases;
- parameterized URLs;
- duplicate paths;
- historical URLs;
- pagination;
- sorting;
- filtering;
- tracking parameters;
- alternate representations.

Canonical signals must not point unrelated entities to each other.

---

## 14. Slugs

Slugs should be:

- readable;
- stable;
- predictable;
- sufficiently unique;
- independent from temporary marketing language.

Slugs should not encode volatile information such as:

- current fee;
- admission year;
- ranking position;
- temporary campaign status.

---

## 15. Slug Changes

If a legitimate slug change occurs:

- the underlying identity remains stable;
- the new URL becomes canonical;
- the old URL should be handled according to a governed redirect/deprecation policy;
- historical references should remain understandable where appropriate.

URL history is part of long-term SEO continuity.

---

## 16. Historical and Superseded URLs

Historical pages may remain accessible when they provide meaningful historical information.

They must be clearly identified as historical where necessary.

Superseded URLs should not create competing current representations.

---

## 17. Indexability

Indexability should be governed by page value and data quality.

Potentially indexable:

- canonical institution pages;
- useful program pages;
- meaningful geographic discovery pages;
- substantive taxonomy pages;
- durable admission information;
- substantive scholarship information.

Potentially non-indexable or controlled:

- internal search results;
- thin filter combinations;
- duplicate parameter URLs;
- temporary utility states;
- low-value generated combinations.

No universal implementation rule is approved here.

---

## 18. Thin and Duplicate Content

The platform should avoid publishing large numbers of pages that contain little unique value.

Examples include combinations where:

- no meaningful records exist;
- information is nearly identical;
- only a filter parameter differs;
- content is automatically generated without substantive context.

SEO expansion must follow information quality, not keyword volume.

---

## 19. Metadata

Public pages may provide metadata appropriate to their entity type, including:

- title;
- description;
- canonical URL;
- language;
- relevant social sharing metadata.

Metadata should describe the actual page/entity.

Unsupported superlatives and fabricated claims must not be used for SEO.

---

## 20. Structured Data Principle

Structured data should represent the same governed facts visible and supportable on the page.

It must not introduce claims that the page cannot substantiate.

Structured data is a machine-readable representation, not an independent truth source.

---

## 21. Structured Data Scope

Future structured data may represent appropriate concepts for:

- educational organizations;
- institutions;
- campuses;
- programs;
- courses where applicable;
- events where genuinely applicable;
- locations;
- offers or financial information where governed;
- ratings/reviews where policy and evidence requirements are satisfied.

Exact vocabulary selection remains an implementation decision.

---

## 22. Community Ratings Structured Data

Community ratings governed by MOD-017 may be exposed only where:

- the rating is genuinely user-generated;
- aggregation is calculated from governed records;
- sample/context is valid;
- manipulation controls are applied;
- the representation accurately describes the underlying signal.

Community ratings must never be represented as official institutional ratings.

---

## 23. External Rankings Structured Data

External rankings governed by MOD-016 must retain publisher and temporal context.

An external rank must not be converted into an eStudent-owned ranking merely through structured data.

---

## 24. Data Quality and SEO

MOD-018 governs the quality state behind SEO representations.

SEO publication must respect:

- verification;
- freshness;
- conflicts;
- lifecycle;
- historical status;
- source evidence.

Unknown information should not be silently converted into negative or positive claims.

---

## 25. AI-Generated SEO Content

AI may assist with:

- summaries;
- metadata drafts;
- categorization;
- internal linking suggestions;
- structured-data generation assistance.

AI-generated content must be grounded in canonical governed data.

AI must not invent:

- fees;
- accreditation;
- rankings;
- admission status;
- eligibility;
- facilities;
- programs;
- institutional achievements.

---

## 26. AI Search Summaries

Future AI-generated public summaries should preserve distinctions between:

- verified facts;
- source-backed claims;
- community opinions;
- external signals;
- derived interpretations;
- unknown information.

Summaries should not hide material uncertainty.

---

## 27. Internal Linking

Internal links should reflect meaningful entity relationships.

Examples:

- institution → campuses;
- institution → programs;
- program → institution;
- program → admission information;
- program → scholarships;
- institution → location;
- institution → related educational categories.

Links should not be generated merely to inflate page counts.

---

## 28. Breadcrumbs

Where useful, public pages may expose a stable conceptual hierarchy.

Examples:

**Institutes → Province → City → Institution**

or

**Institutes → Institution → Program**

Breadcrumb structure must reflect the real information architecture.

---

## 29. Sitemap Governance

Future sitemap representations should contain durable, canonical public URLs.

They should not intentionally enumerate every temporary query/filter combination.

Sitemap inclusion should follow canonical/indexability governance.

---

## 30. Robots and Crawl Controls

Future crawl-control policies should protect:

- duplicate query URLs;
- internal search states;
- temporary utility URLs;
- low-value combinations;
- private/administrative representations.

Crawl controls must not accidentally block valuable canonical content.

---

## 31. SEO Freshness

Material canonical changes should eventually propagate to:

- page content;
- metadata;
- structured data;
- canonical signals;
- sitemap representations;
- relevant internal links.

The required propagation timing is implementation-specific and not approved here.

---

## 32. SEO and Historical Continuity

Long-term SEO should preserve useful historical relationships.

When an institution:

- changes name;
- relocates;
- merges;
- splits;
- closes;
- reopens;

URL continuity should follow the identity governance in MOD-007 rather than treating every change as a new SEO entity.

---

## 33. Search Engine Representation vs Canonical Data

Search-engine crawlers may see:

- current data;
- historical data;
- community signals;
- external signals;
- derived summaries.

Only governed canonical data establishes the underlying entity facts.

---

## 34. Structured Data Validation

Future implementation should validate that structured data:

- matches visible content;
- matches canonical data;
- respects lifecycle;
- respects source/evidence state;
- does not contain unsupported claims;
- remains internally consistent.

---

## 35. SEO Analytics

Future monitoring may track:

- indexed pages;
- indexing changes;
- crawl errors;
- canonical conflicts;
- search visibility;
- organic queries;
- page performance;
- structured-data issues;
- stale pages;
- orphan pages.

Analytics should inform maintenance rather than determine institutional quality.

---

## 36. SEO Quality

SEO quality should be evaluated through:

- factual accuracy;
- usefulness;
- coverage;
- discoverability;
- technical consistency;
- freshness;
- canonical integrity;
- structured-data correctness.

SEO success must not be defined only by page count or keyword volume.

---

## 37. Explicit Non-Approval of Implementation

This document does not approve:

- a specific search engine;
- a specific search engine optimization tool;
- a specific schema vocabulary implementation;
- sitemap software;
- crawler software;
- CMS;
- rendering framework;
- hosting platform;
- AI provider.

---

## 38. Future Evolution

Future documents may define:

- API/data access;
- structured-data implementation;
- sitemap implementation;
- SEO monitoring;
- page-generation governance;
- canonical/redirect implementation;
- ranking and recommendation methodology.

---

## 39. Approval State

This document is a **final candidate pending merge into `main`**. Once merged, it becomes approved project documentation unless superseded by a governed decision.
