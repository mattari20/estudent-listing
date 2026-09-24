# DEV-300 — Production Data Population and Structural Readiness Gate

**Document ID:** DEV-300  
**Title:** Production Data Population and Structural Readiness Gate  
**Status:** Final candidate — pending merge into `main`  
**Owner:** eStudent.pk  
**Depends On:** DEV-217–226, DEV-227–299  
**Scope:** Read-only production-data readiness and structural verification

---

## 1. Purpose

DEV-300 establishes a controlled readiness boundary before real institute data is populated at scale.

The directory already has governed import, validation, identity-resolution, promotion, geography-integrity, public-search, API, SEO, and nearby-discovery layers.

The next operational requirement is to determine whether the current canonical structure can safely receive and expose real data.

This batch does **not** import fabricated or unverified institute data.

---

## 2. Core Principle

**Real-data population must be evidence-backed, staged, reviewable and non-destructive.**

The readiness layer therefore measures canonical structure without changing it.

It must not:

- create records;
- update records;
- delete records;
- activate records;
- deactivate records;
- repair relationships automatically;
- invent missing geography;
- infer official institute data;
- publish imported candidates automatically.

---

## 3. Readiness Scope

The read-only readiness report covers:

- institution population;
- active institution population;
- campus population;
- active campus population;
- program population;
- active program population;
- program-offering population;
- active offering population;
- admission-intake population;
- active relationship integrity;
- structural blocking findings.

---

## 4. Structural Findings

The readiness service checks for:

1. active institutions without an active campus;
2. active campuses without active canonical geography;
3. active programs without an active institution;
4. active program offerings without an active program;
5. active program offerings without an active campus;
6. admission intakes without an active program offering.

These are structural findings, not content-quality scores.

---

## 5. Readiness Interpretation

The report exposes:

- population counts;
- integrity findings;
- total blocking findings;
- `structurally_ready`;
- `mutation_performed`.

A structurally ready result means that the defined relationship checks returned no blocking findings.

It does **not** mean that all real-world data is complete, verified, current, or suitable for publication.

Data completeness and evidence verification remain separate governance concerns.

---

## 6. Real-Data Import Boundary

Existing institute import operations remain the controlled entry point for real data.

The intended flow remains:

**Source → Import Batch → Validation → Candidate → Identity Resolution → Operator Decision → Promotion → Audit → Public Representation**

DEV-300 does not bypass this flow.

A CSV or external source must not become canonical merely because it passed syntactic validation.

---

## 7. Geography Boundary

Geography remains source-backed and governed.

DEV-300 does not:

- invent cities;
- infer districts;
- create missing parent geography;
- automatically repair campus geography;
- replace canonical geography.

Geography import and promotion continue through their existing governed workflows.

---

## 8. Public Safety

The readiness report is operator-only.

It is placed behind:

- authentication;
- institute operator authorization.

It is not a public endpoint and does not expose a public data-management operation.

The report is read-only.

---

## 9. API / UI Boundary

DEV-300 exposes the readiness summary through the operator operations area as a JSON response.

This is an operational inspection surface, not a public API contract.

The endpoint does not permit writes and does not mutate canonical data.

---

## 10. Testing Requirements

The batch must verify at least:

- institution population counting;
- detection of an active institution without an active campus;
- explicit mutation-free behavior;
- valid active institution/campus/geography/program/offering hierarchy;
- zero structural blocking findings for a valid hierarchy.

---

## 11. Relationship to Existing Governance

DEV-300 builds on:

- source and evidence governance;
- institute import readiness;
- controlled import validation;
- identity resolution;
- promotion governance;
- geography integrity;
- public lifecycle safety;
- API data-access safety;
- nearby/geospatial discovery.

It does not replace any of these layers.

---

## 12. Explicit Non-Approval

DEV-300 does **not** approve:

- a particular real-world institute dataset;
- automatic population from web scraping;
- unverified bulk publication;
- fuzzy auto-merge;
- automatic canonical repair;
- ranking;
- recommendation;
- database redesign;
- new external data provider;
- payment or financial processing;
- AI provider/model;
- deployment architecture.

Those remain governed separately.

---

## 13. Completion Criteria

DEV-300 is complete when:

1. the read-only readiness service is implemented;
2. structural findings are explicit;
3. operator-only access is enforced;
4. regression tests cover valid and invalid structural states;
5. no canonical mutation is performed by the readiness layer;
6. the governance documentation is present;
7. the implementation is submitted as a reviewable pull request;
8. CI results are verified before merge.

---

## 14. Next Operational Stage

After this gate is merged and validated, the project can proceed to controlled **real institute data population and verification** using source-backed import batches.

No real-world institute record should be fabricated merely to demonstrate population.

