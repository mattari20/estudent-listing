# Project Constitution

## Document Information

| Field | Value |
| --- | --- |
| Document ID | FND-002 |
| Title | Project Constitution |
| Project | eStudent.pk — Institutes & Education Directory |
| Status | Approved foundation document |
| Documentation language | English |
| Repository | `estudent-listing` |
| Default branch | `main` |
| Last updated | 2026-09-07 |

This constitution governs the long-term eStudent.pk Institutes & Education Directory project. It is read with the [Master Project Index](MASTER_PROJECT_INDEX.md). It establishes durable rules, not a technology stack or implementation architecture.

## 1. Purpose and Authority

This document establishes governing principles, decision rules, and quality expectations for maintainers, contributors, reviewers, operators, contractors, and AI-assisted development agents.

Approved documentation is the source of truth for approved requirements, architecture, and decisions. Source code, tests, and operational evidence must not silently override approved documentation. Any conflict must be documented and resolved through the applicable decision or change process.

## 2. Project Vision

Build a trusted, discoverable, extensible Pakistan-focused education directory that helps people find accurate information about legitimate educational institutions and supports the long-term eStudent.pk ecosystem.

## 3. Long-Term Commitment

The project is intended to remain useful and understandable for 20+ years. Its public information architecture, data, documentation, operational knowledge, and decisions must support maintainability, controlled evolution, technology replacement, growth, integrations, and future education-related services.

## 4. Core Product Principles

- Trustworthiness, accuracy, usefulness, and correction paths take precedence over rapid content accumulation.
- Public information must distinguish authoritative or verified information from unverified, inferred, or user-submitted information.
- Prefer durable, understandable, replaceable designs over short-term complexity or unnecessary vendor dependence.
- Prefer small, reviewable, maintainable changes over large uncontrolled changes.
- Future capabilities are ideas until their requirements and decisions are approved.

## 5. Documentation-First Rule

Major capabilities must not enter coding until the relevant requirements and architecture or specification documents have been reviewed and approved. Every major implementation must be traceable to approved documentation.

Documentation must state its status and clearly separate requirements, decisions, implementation details, proposals, assumptions, risks, and future ideas. Documentation is not retrospective decoration for unapproved work.

## 6. Requirements vs Decisions vs Ideas

- **Requirements** define approved outcomes, constraints, and acceptance needs.
- **Decisions** record approved choices, rationale, alternatives, consequences, and status.
- **Implementation details** describe how approved work will be carried out; they are not governing requirements by default.
- **Proposals and assumptions** are explicitly tentative until validated or approved.
- **Future ideas** are non-binding and must never be presented as approved scope, architecture, or commitments.

No developer or AI agent may silently invent a major requirement or architecture. Existing approved decisions must not be casually overridden.

## 7. Architecture Principles

Architecture must establish boundaries, ownership, dependencies, quality concerns, and evolution paths before major implementation. Data quality, security, privacy, accessibility, performance, observability, and operational maintainability are architectural concerns from the beginning.

The project must avoid unnecessary coupling to current technologies. Future technologies may replace current ones without an unnecessary redesign of core public information architecture, data meaning, or public URLs.

## 8. Modularity and Domain Boundaries

Capabilities and domains must have clear responsibilities, ownership, and interfaces before material implementation. Cross-domain dependencies must be deliberate, documented, and minimized. Boundaries should support future education-related services while preserving the Institutes & Education Directory as a clearly understood module.

## 9. Data Integrity and Provenance

Future data design must prioritize integrity, provenance, verification status, timestamps, correction paths, change history, ownership, retention direction, and future evolution. Data collection at scale requires documented quality, review, and update processes.

Institutional information must retain enough context to distinguish source, verification state, freshness, and material changes. Unverified or user-submitted information must not be mixed indistinguishably into authoritative public records.

## 10. SEO and Public URL Governance

Public URLs are long-term assets. The approved permanent public module root is <https://estudent.pk/institutes/> and must remain stable unless the formal change process is followed. It is an approved architectural foundation, not a temporary naming preference.

A proposed URL migration requires documented impact analysis, an approved decision record, explicit approval, and migration validation. It must consider redirects, canonical URLs, internal links, search-engine indexation, backlinks, historical references, API consumers, application clients, and validation of the migration outcome.

## 11. Security and Privacy Principles

Security must use least privilege, secure defaults, input validation, output encoding, protection against common web attacks, secrets management, auditability, and appropriate review. Security controls are designed into approved work, not deferred as optional hardening.

Personal or user data may be collected only for a defined purpose with appropriate governance, access controls, retention direction, and correction or deletion handling where applicable. This constitution makes no legal or regulatory compliance claims; relevant obligations require later assessment and approval.

## 12. Performance and Scalability Principles

Performance, availability, data quality, and controlled growth are long-term architectural concerns, not post-launch work. Before scale-oriented implementation, relevant specifications must establish appropriate measurable expectations for public use, records, search, media, integrations, and operations without prematurely selecting technology.

## 13. Accessibility and User Experience Principles

Accessibility and inclusive user experience must be considered from the beginning of requirements, architecture, content, design, implementation, and testing. Public experiences should remain understandable, usable, accurate, and resilient for people with varied devices, connectivity, languages, and abilities.

## 14. AI Readiness and AI Governance

AI is an assistive capability, not an uncontrolled source of truth for core institutional data. AI-generated information requires appropriate validation, provenance, review, quality controls, and governance before presentation as authoritative public information.

AI providers must be replaceable where practical. Core platform data, approved decisions, and public URLs must not become unnecessarily dependent on any one provider. AI features require documented accountability, permitted data sources, safety and review processes, and removal or replacement paths before public use.

## 15. API and Integration Principles

When implementation begins, APIs and integrations must be designed as governed contracts with versioning, authentication, authorization, rate limiting, and lifecycle or deprecation policies. External data exchange and partner access are deliberate capabilities, not incidental exposure of internal implementation.

## 16. Backward Compatibility and Migration

Backward compatibility is preferred wherever practical for public URLs, identifiers, documented contracts, records, and integrations. Breaking changes require explicit documentation, impact analysis, migration planning, approval, appropriate communication, and validation.

Irreversible operations, data deletion, database migrations, public URL changes, security changes, authentication changes, and major SEO architecture changes require additional review before execution.

## 17. Versioning and Deprecation

Versioning and deprecation rules must be defined before public or internal contracts are exposed. Deprecation must state scope, rationale, replacement, compatibility effects, timeline or exit criteria, owner, and validation plan. Superseded decisions and contracts remain historically traceable; they must not be silently overwritten or erased.

## 18. Testing and Quality Assurance

Testing grows with the system and covers appropriate unit, integration, functional, security, performance, accessibility, regression, and operational concerns. Approved work must define proportionate acceptance and verification criteria. Failures, gaps, and environment limitations must be reported honestly.

## 19. Development Workflow

1. Capture requirements or proposals in the appropriate documentation area.
2. Separate facts, requirements, options, assumptions, risks, decisions, and future ideas.
3. Review and approve the relevant architecture and specifications.
4. Record material decisions and approved changes.
5. Create an approved implementation and testing plan.
6. Implement in small, reviewable changes linked to approved documentation.
7. Verify, review, release, and retain operational evidence as appropriate.

## 20. Git and Repository Governance

Git history and pull requests are the reviewable record of important changes. Changes must be scoped, explain their purpose, identify affected documentation or decisions, and avoid unrelated modifications. Reviews assess correctness, governance impact, quality, compatibility, and required approvals.

## 21. Codex / AI-Assisted Development Rules

AI coding agents, including Codex, must:

- inspect relevant existing documentation before modifying work;
- identify affected files and avoid unrelated modifications;
- follow approved requirements, decisions, and architecture;
- report files changed, tests or checks performed, and assumptions made;
- not create files outside requested scope unless an approved specification explicitly requires them;
- never delete or rewrite unrelated functionality without authorization; and
- stop and report a conflict between existing code and approved documentation rather than silently choosing a solution.

AI assistance does not transfer accountability from human reviewers and project governance. Generated output receives the same appropriate review as other contributions.

## 22. Change Management

Material changes require documented rationale, scope, alternatives, risks, affected documentation, compatibility impact, migration approach, validation plan, and approval. A material change affects approved scope, architecture, public contracts or URLs, data meaning or integrity, security, privacy, operations, or long-term maintenance.

## 23. Architecture Decision Records

Important and durable architectural decisions must use `ADR-xxx` documents. An ADR records context, decision, status, rationale, alternatives, consequences, affected documents, and supersession links where applicable. ADRs preserve historical traceability and must not be silently rewritten to conceal a previous decision.

## 24. Request for Change Process

Significant proposed changes should use `RFC-xxx` documents before approval. An RFC must state the problem, proposed change, scope, alternatives, assumptions, risks, impact analysis, compatibility and migration considerations, validation, and requested decision. Accepted material changes update affected governing documentation and use an ADR when a durable architectural decision is made.

## 25. Production and Release Discipline

No production deployment may occur without appropriate testing and review for the approved scope. Release readiness must consider compatibility, migration, security, data quality, rollback or recovery direction, monitoring, and communication where relevant. Urgency does not waive recording of material decisions and risks.

## 26. Monitoring, Observability and Auditability

Operationally important work must be designed to be observable and auditable at a level appropriate to its risk. The project must be able to investigate material data changes, security-relevant actions, integration behavior, availability or performance concerns, and release outcomes without exposing unnecessary personal data.

## 27. Data Updates and Verification

Data updates require documented sources, verification direction, timestamps, and accountable review appropriate to their importance. Publicly displayed information must communicate verification and freshness appropriately when the relevant product specification is approved. Correction and dispute paths must be designed before large-scale public data operations.

## 28. Content Trust and Editorial Governance

Editorial processes must protect users from misleading, thin, duplicate, outdated, or falsely authoritative content. Content sources, claims, verification state, corrections, and material editorial changes require traceability appropriate to their impact. Governance for submissions, reviews, ratings, or claims must be approved before those capabilities are introduced.

## 29. Disaster Recovery and Business Continuity Direction

Later operational specifications must establish proportionate backup, recovery, continuity, restoration validation, incident communication, and ownership direction. These plans must protect the continuity and integrity of the public information service without prematurely selecting hosting or infrastructure.

## 30. Long-Term Maintainability

The repository, documentation, code, tests, operational evidence, and ownership knowledge must remain understandable to future developers and AI agents who did not participate in the original work. Prefer explicit contracts, bounded complexity, documented rationale, and replaceable dependencies. Technical debt and documentation drift must be identified, prioritized, and addressed through governed changes.

## 31. Future Expansion Rules

Future expansion may support broader education-related services only through documented requirements and approved decisions. It must preserve the stable `/institutes/` module foundation, clear domain boundaries, data trust, public URL governance, and compatibility obligations. No future capability is approved merely because it appears in planning or foundation documentation.

## 32. Governance Hierarchy

When applicable documents conflict, the following hierarchy governs, subject to the most recent explicitly approved and applicable decision at the same level:

1. Project Constitution.
2. Approved Architecture Decisions / ADRs.
3. Approved Requirements and Specifications.
4. Approved Implementation Plans.
5. Source Code.
6. Tests and Operational Evidence.

Source code is not allowed to silently override approved documentation. A conflict at any level must be documented and resolved through the appropriate ADR, RFC, correction, or change-management process before contradictory work is treated as authoritative.

## 33. Constitution Amendment Rules

This constitution may be amended only through a documented, reviewed, and approved change. The proposal must state the rationale, affected rules, alternatives, risks, compatibility effects, implementation or migration impact, and effective status. Amendments preserve historical traceability and do not silently rewrite the reason for a prior rule.

## 34. Non-Negotiable Rules

Before modifying the repository, developers and AI agents must confirm that:

1. Approved documentation governs; code does not silently override it.
2. Major capabilities require approved requirements and architecture or specifications before coding begins.
3. Requirements, decisions, implementation details, proposals, assumptions, and ideas remain clearly separated.
4. Major requirements or architecture are never invented silently.
5. Material changes have documented impact analysis and approval.
6. Important architecture uses ADRs; significant proposals use RFCs.
7. Superseded decisions remain traceable.
8. `https://estudent.pk/institutes/` is stable unless the formal URL-change process is followed.
9. Data provenance, verification, integrity, freshness, and history are protected; AI is not an uncontrolled data authority.
10. Security, privacy, performance, accessibility, testing, and auditability are built into approved work from the beginning.
11. Breaking changes require explicit documentation, migration planning, and approval; compatibility is preferred wherever practical.
12. Changes are scoped, reviewable through Git and pull requests, and avoid unrelated edits.

## 35. Current Status

**Approved foundation document; documentation-first project phase.** This constitution does not approve application code, a technology stack, database schema, API endpoints or implementation, user-interface designs, hosting infrastructure, detailed SEO templates, a specific AI provider, legal policies, or regulatory compliance claims. Those matters require later documentation, review, and approval.

