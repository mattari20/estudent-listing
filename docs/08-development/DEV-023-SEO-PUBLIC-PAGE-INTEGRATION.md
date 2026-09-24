# DEV-023 — SEO Public Page Integration

Status: Implemented
Branch: dev/022-024-integration-hardening

## Implemented
- `/institutes/` has a canonical URL.
- Institution profiles expose stable canonical URLs.
- Title and description metadata are generated from governed institution data.
- Open Graph title, description and URL are available through the shared layout.
- Institution profiles emit basic EducationalOrganization JSON-LD containing only stable identity and URL fields.
- Search result pages with a query are not given a query-specific canonical URL.

## Boundary
Structured data remains a representation of canonical data. No AI-generated claims, rankings, reviews, fees or accreditation claims are inserted into schema.

## Long-term requirement
Future SEO expansion must preserve permanent URLs, avoid thin pages, and remain grounded in governed fields.
