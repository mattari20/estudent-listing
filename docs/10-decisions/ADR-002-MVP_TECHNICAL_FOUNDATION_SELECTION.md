# ADR-002 — MVP Technical Foundation Selection

**Document ID:** ADR-002  
**Title:** MVP Technical Foundation Selection  
**Version:** 1.0  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Created:** 2026-09-23  
**Last Updated:** 2026-09-23  
**Depends On:** ADR-001, DB-001, API-001, UI-001, SEO-001, TST-001  
**Decision Type:** Technical Architecture

## 1. Decision Summary

The MVP will use a **modular monolith** based on:

- **PHP 8.5** as the application runtime;
- **Laravel 13** as the application framework;
- **MySQL 8.4 LTS** as the relational data store;
- **server-rendered HTML using Laravel Blade** for the public experience;
- **progressive JavaScript enhancement** rather than a mandatory SPA;
- **Composer** for PHP dependency management;
- **Git/GitHub** as the source-control and change-history system.

The application will be designed so that search, AI, APIs, queues and other capabilities can evolve independently without changing the canonical domain model.

## 2. Current Runtime Basis

PHP 8.5 is a current supported PHP branch. The official PHP support schedule lists active support through 31 December 2027 and security support through 31 December 2029. PHP 8.5 is therefore selected rather than an end-of-life or older branch. citeturn0search0turn0search1

Laravel 13 was released in March 2026 and supports PHP 8.3–8.5. Its current support policy provides bug fixes for 18 months and security fixes for two years, with Laravel 13 security support through March 2028. citeturn2search1

MySQL 8.4 is an LTS release intended for stable production environments, with an LTS support model of five years of premier support plus three years of extended support. citeturn1search0

## 3. Why a Modular Monolith

The MVP should not begin as microservices.

A modular monolith provides:

- simpler deployment;
- lower operational overhead;
- clear domain boundaries;
- easier local development;
- simpler transactions;
- simpler SEO page generation;
- easier debugging;
- a practical path for a small initial team.

The internal module boundaries must remain explicit so that future components can be extracted only when justified.

## 4. Why Laravel

Laravel 13 provides:

- mature routing;
- request validation;
- authentication/authorization foundations;
- database abstraction;
- migrations;
- queues;
- caching;
- testing support;
- server-side rendering;
- API/resource support;
- long-term ecosystem familiarity.

Laravel 13 also provides current API and AI-related capabilities, but the project will not make those features a dependency for the core directory. citeturn2search1

## 5. Why Server-Rendered Public Pages

The public directory is primarily:

- content-heavy;
- SEO-sensitive;
- search/discovery oriented;
- accessible;
- institution-profile focused.

Server-rendered pages reduce unnecessary client-side complexity and provide a strong baseline for crawlability and first-load performance.

JavaScript should enhance interactions rather than become a prerequisite for viewing core institution information.

## 6. Frontend Direction

Initial frontend architecture:

- Laravel Blade;
- semantic HTML;
- centralized project CSS;
- small focused JavaScript modules;
- progressive enhancement;
- responsive/mobile-first layouts.

A large SPA framework is not required for the MVP.

If a future interactive capability justifies a richer frontend, it may be introduced inside a bounded area without replacing the public page architecture.

## 7. Database Decision

MySQL 8.4 LTS is selected for the MVP canonical data store.

The database design must follow DB-001.

The project should avoid database-specific features that make future migration unnecessarily difficult unless they provide a documented material benefit.

MySQL's LTS model is preferred over an Innovation release for the long-lived canonical store because LTS releases prioritize stability and longer support. citeturn1search0turn1search5

## 8. Search Decision

The initial search implementation should use the canonical relational data store and application-level search logic.

Do not introduce an external search engine at MVP launch unless testing demonstrates a clear requirement.

This keeps:

- deployment simpler;
- data consistency stronger;
- rebuilds straightforward;
- operating cost lower.

A dedicated search engine may be introduced later without changing canonical entity identity.

## 9. API Decision

The initial API will live within the modular application boundary.

It will follow API-001.

A separate API service is not required for MVP.

Future public, partner or mobile APIs can evolve from the same governed access layer.

## 10. AI Decision

AI is not part of the mandatory technical foundation.

The architecture will provide integration boundaries for future:

- data extraction;
- duplicate detection;
- search assistance;
- summaries;
- recommendations;
- monitoring.

AI providers must remain replaceable.

No provider is selected by this ADR.

## 11. Dependency Management

PHP dependencies will be managed through Composer.

Dependencies must:

- have a defined purpose;
- be maintained;
- be version constrained;
- be reviewed before upgrades;
- have a replacement/removal path where practical.

Unnecessary packages should not be added merely because they are available.

## 12. Configuration and Secrets

Application configuration must be separated from source code.

Secrets must never be committed to Git.

Environment-specific configuration must support development, testing and production separation.

## 13. Application Structure

The codebase should be organized around clear modules/domain responsibilities rather than a flat collection of unrelated files.

At minimum, conceptual boundaries should exist for:

- Institution;
- Campus;
- Geography;
- Program;
- Offering;
- Provenance;
- Verification;
- Search;
- SEO;
- Administration.

The exact directory structure will be defined during implementation.

## 14. Public Subfolder Deployment

The application must be deployable under:

`/institutes/`

without changing the permanent public URL contract.

The deployment configuration must ensure that application internals are not publicly exposed.

The exact web-server/rewrite configuration will be documented and tested during implementation.

## 15. Security Foundation

The implementation must use framework-supported secure patterns for:

- authentication;
- authorization;
- request validation;
- CSRF protection;
- output escaping;
- database parameterization;
- secure file handling.

Security-sensitive functionality must not be implemented through ad-hoc shortcuts.

## 16. Testing Foundation

The selected framework must support the testing strategy in TST-001.

Tests will be introduced with the implementation rather than postponed until after MVP functionality is complete.

## 17. Upgrade Strategy

Framework and runtime upgrades must be planned.

The project should avoid long periods of unsupported runtime versions.

Laravel follows an annual major-release cadence, so dependency upgrades should be treated as normal maintenance rather than rare emergency migrations. citeturn2search1

PHP minor-version upgrades must be tested before production adoption because PHP documents backward-incompatible changes between minor versions. citeturn0search3

## 18. Long-Term Replaceability

The following must remain replaceable:

- AI provider;
- search implementation;
- cache;
- queue backend;
- analytics;
- external APIs;
- map provider;
- hosting environment.

The canonical domain model must not depend directly on a replaceable provider.

## 19. Explicitly Deferred Technology

The MVP does not require:

- microservices;
- Kubernetes;
- external search cluster;
- vector database;
- SPA framework;
- dedicated AI infrastructure;
- event-streaming platform;
- complex distributed architecture.

These may be introduced only when requirements justify them.

## 20. Technical Decision Boundary

This ADR selects the MVP technical foundation, but does not freeze every implementation detail.

Later technical decisions may define:

- authentication implementation;
- queue backend;
- cache backend;
- search optimization;
- asset build tooling;
- deployment topology;
- observability;
- backup infrastructure.

Material changes require the normal change-control process.

## 21. Consequences

### Positive

- strong fit for content-heavy SEO directory;
- relatively simple deployment;
- mature ecosystem;
- clear modular architecture;
- relational consistency;
- straightforward future API/AI integration;
- lower initial operational complexity.

### Trade-offs

- Laravel and PHP upgrades require planned maintenance;
- a modular monolith requires discipline to prevent module coupling;
- very large search workloads may eventually require a dedicated search system;
- very high traffic may eventually require additional infrastructure.

## 22. Implementation Entry

Following approval of this ADR, implementation may begin with:

1. application skeleton;
2. environment configuration;
3. database connection;
4. module boundaries;
5. migration system;
6. base testing setup;
7. first canonical Institution/Campus implementation.

## 23. Approval State

This ADR is a **final candidate pending merge into `main`**.
