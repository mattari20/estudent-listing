# DEV-296 — SEO and Structured Data Foundation

## Status

Implemented on branch dev-296-seo-structured-data-foundation.

## Scope

This batch establishes the first implementation-level SEO publication boundary for the Institutes Directory.

### Included

- Central SEO/structured-data service for institution profiles.
- EducationalOrganization structured data grounded in the loaded institution and active campus records.
- BreadcrumbList structured data for institution profiles.
- Canonical, robots, Open Graph and Twitter metadata defaults at the module layout level.
- XML sitemap endpoint containing the Institutes root and active institution profile URLs only.
- Module-scoped robots text endpoint protecting search, login and operator paths while advertising the module sitemap.
- Regression coverage for structured data, sitemap lifecycle filtering, robots controls and canonical/noindex behavior.

## Safety boundaries

- Structured data is generated from canonical page data already exposed by the public profile.
- Inactive institutions are excluded from the sitemap.
- Internal search results remain controlled and are not treated as canonical entity pages.
- No ranking, rating, accreditation or admission claims are invented by the SEO layer.
- Sitemap and robots output do not expose operator routes as crawlable content.

## Next SEO work

This batch does not create program/campus/geographic landing pages. Those require separate information-architecture and data-quality gates before becoming indexable canonical entities.
