# Institutes Directory — Project Completion Master Strategy

**Strategy date:** 2026-10-31 (PKT)  
**Repository:** `mattari20/estudent-listing`  
**Production branch:** `main`  
**Strategy baseline:** `b878416826bdf62c26ad27d040f254461d3cf126`  
**Production URL:** https://estudent.pk/institutes/  
**Status:** CONSOLIDATED PLAN — IMPLEMENTATION NOT STARTED

> This document consolidates the four Step 1–4 audit reports. PRs #954–#957 are open, unmerged documentation branches at the time of this strategy. Their findings are treated as audit evidence, not as merged main-branch documentation or proof of production state. This plan authorizes no production or implementation action.

## 1. Executive summary

The Institutes Directory is a substantial Laravel application with public directory/API surfaces, institution and academic data models, source retrieval, snapshots and evidence, deterministic extraction, candidate staging, duplicate/identity review, field approvals, canonical promotion/publication controls, operational tooling and extensive GitHub Actions automation.

It is **not yet a verified nationwide production directory**, and its institution-finding automation is **not a completed autonomous AI agent**. The strongest demonstrated capability is a governed, deterministic, configured-source pilot architecture. GCU is the only enabled pilot family identified by the Step 3 audit. Twenty additional HEC identity profiles remain disabled. Optional AI is a programme-extraction adapter, disabled by default; the institution identity discovery service is deterministic and rejects AI-enabled monitoring configuration.

The immediate priority is not to enable more discovery. It is to establish safe operational control and current production truth, then remediate security/data-integrity issues, recover the protected GCU evidence, complete the human-reviewed pilot, and only then expand source coverage and AI.

### Current position at a glance

| Area | Consolidated status | What the evidence supports |
|---|---|---|
| Laravel application foundation | IMPLEMENTED / PRODUCTION UNVERIFIED | Broad architecture and routes exist; live parity and end-to-end acceptance are not current |
| Public directory/API | PARTIALLY COMPLETE | Listing, detail and API contracts exist; current live behavior, populated-state behavior and consistent publication filters need acceptance |
| Data model and relationships | PARTIALLY COMPLETE | Institution/campus/programme structures exist; campus parent resolution and offering discovery remain incomplete |
| Official-source pipeline | PARTIALLY COMPLETE | Safe retrieval, snapshots, hashes, deterministic extraction and candidate staging exist for configured sources |
| Nationwide source coverage | BLOCKED / PARTIALLY COMPLETE | Source leads exist, but a lead is not a qualified source; seven-jurisdiction coverage is not demonstrated |
| Institution identity AI | NOT STARTED | Current identity discovery is deterministic; no autonomous AI identity discovery |
| AI programme extraction | IMPLEMENTED / DISABLED | Optional provider adapter exists; grounding, provider compatibility, cost controls and live qualification remain |
| Candidate governance | PARTIALLY COMPLETE / PRODUCTION UNVERIFIED | Review packages, identity/field approval and promotion controls exist; historical GCU records and live schema need evidence |
| Production operations | BLOCKED / UNVERIFIED | Revision, schema, cron, queue worker, active runs, grants, backups and live HTTP acceptance need current evidence |
| Automation governance | PARTIALLY COMPLETE | 35 workflows and Laravel scheduler inventoried; cross-runner lock and actual hosting scheduler ownership unresolved |
| Security and QA | BLOCKED FOR PRODUCTION RESUME | Step 2 high-priority findings require fixes and regression evidence |

### Top blockers

1. **Operational visibility:** current Hostinger revision/runtime, schema/ledger, cron, worker, queue and active automation state are not established.
2. **Overlapping execution:** GitHub scheduled discovery and Laravel scheduler invoke the same hourly Artisan commands without a demonstrated shared lock.
3. **Security defects:** first-admin guard, JSON-LD script encoding, public-state filtering, evidence authorization and CSV formula injection need remediation.
4. **Data integrity:** migration schema-equivalence checks, migration ledger and historical production schema issues require fresh evidence and stronger checks.
5. **GCU evidence recovery:** candidate rows #348–353 and batches #19–23 remain blocked/unverified pending protected export and snapshot reconciliation.
6. **Discovery gaps:** general unknown-institution enumeration, campus-parent resolution and programme-campus offering discovery are incomplete.
7. **National qualification:** source leads and configured profiles do not prove accepted authority, access policy, extractor reliability or jurisdiction coverage.
8. **AI readiness:** AI is disabled and must remain so until grounding, model, cost, failure and governance acceptance.

## 2. Verified repository baseline and audit evidence

### 2.1 Repository and pull requests

At the time of verification, the latest commit returned for `main` was:

`b878416826bdf62c26ad27d040f254461d3cf126`

The following PRs were open and unmerged. Their exact-head Test and Laravel CI runs were both completed successfully when checked for this strategy:

| PR | Audit/report | Head SHA | Test run | Laravel CI run | State |
|---|---|---|---:|---:|---|
| #954 | Step 1 baseline inventory | `c23c52807cf881a17206721315789a7db98096e5` | #41180827573 — success | #41180827589 — success | Open, unmerged |
| #955 | Step 2 technical audit | `ffc2dd06659f1bac9227d5ddb3a2213e55776896` | #41181645272 — success | #41181645302 — success | Open, unmerged |
| #956 | Step 3 data/AI/production audit | `1f0d9f814e427378c1fab136103ff990915b7097` | #41182142012 — success | #41182141971 — success | Open, unmerged |
| #957 | Step 4 automation audit | `1b8f7cfeefb88c66d7d8c45b90de29f3b3705b31` | #41182959103 — success | #41182959162 — success | Open, unmerged |

These checks validate their respective PR heads under the available workflows. They do not verify Hostinger, production schema/data, cron, queue, public HTTP or source qualification. The repository commit search returned `main` at the baseline SHA above; re-check it before any later implementation phase.

Relevant older open PRs at the same repository check included #953 (runtime diagnostic repair), #913 (Hostinger diagnostic blocker), #911 (isolated parity fetch) and #899 (empty-directory diagnostic record). Do not assume these are resolved or change their state without a separate review and authorization.

### 2.2 Four audit reports used

| Step | Report | Branch / PR | Evidence qualification |
|---|---|---|---|
| 1 | `docs/audits/PROJECT_BASELINE_AND_INVENTORY.md` | `audit/step1-project-baseline-2026-10-31` / #954 | Open, unmerged; report blob SHA `32f1179613627aca8eece4ee348d4feac5c995a6` |
| 2 | `docs/audits/DEEP_TECHNICAL_AND_ARCHITECTURE_AUDIT.md` | `audit/step2-deep-technical-architecture-2026-10-31` / #955 | Open, unmerged; report blob SHA `360c01b73d7ff58c3141859af7d088ad245a8081` |
| 3 | `docs/audits/DATA_PIPELINE_AI_AGENT_AND_PRODUCTION_AUDIT.md` | `audit/step3-data-pipeline-ai-production-2026-10-31` / #956 | Open, unmerged; report blob SHA `2802a476a964c0027f6b60d9fa12a9f080cbb25d` |
| 4 | `docs/audits/AUTOMATION_FREEZE_AND_RESUME_READINESS_AUDIT.md` | `audit/step4-automation-freeze-resume-2026-10-31` / #957 | Open, unmerged; report blob SHA `7fc012c984984b370e5215a053fdb3d7e54035b6` |

The reports were fetched from their actual audit branches for this consolidation. They share the same audited main baseline, so later changes on PR branches must not be mistaken for changes already present on main.

### 2.3 Existing master tracker

The single source of truth remains `docs/PROJECT_MASTER_TRACKER.md`. It contains the five sequential legacy Phase 2 steps, long-term roadmap and extensive append-only execution history through October 30. Its opening/current-position statements are stale relative to the October 31 audit series. This strategy adds a dated roadmap section to that existing file; it does not replace its historical record or create a second tracker.

## 3. Consolidated capability and workstream matrix

Status definitions: **COMPLETE** means implementation and required acceptance evidence exist; **IMPLEMENTED / PRODUCTION UNVERIFIED** means source code exists but live acceptance is missing; **PARTIALLY COMPLETE** means meaningful implementation exists but requirements remain; **NOT STARTED** means insufficient implementation evidence; **BLOCKED** means a prerequisite is unresolved; **DEFERRED** means intentionally postponed.

| ID | Workstream | Status | Implemented / evidence | Remaining work and completion evidence | Priority |
|---|---|---|---|---|---|
| APP-01 | Laravel foundation, routes, models, services | IMPLEMENTED / PRODUCTION UNVERIFIED | Laravel 13 architecture and broad application surface inventoried in Step 1/2 | Exact deployed revision/runtime, command registration and route smoke evidence | P0 |
| APP-02 | Public directory, detail, API, search | PARTIALLY COMPLETE | Listing/detail/API/search routes and empty-state handling exist | Fresh HTML/API checks, populated and empty states, consistent status allowlists, pagination/filter/detail tests | P1 |
| DATA-01 | Institution identity and hierarchy | PARTIALLY COMPLETE | Institution, campus and parent relationships exist | Evidence-backed parent/child resolution, duplicate conflicts, historical candidate reconciliation | P1 |
| DATA-02 | Programme and qualification data | PARTIALLY COMPLETE | Deterministic programme extraction and academic models exist | Broader source-specific parsers, quality checks, qualification relationships | P2 |
| DATA-03 | Programme-campus offerings | NOT STARTED / PARTIALLY COMPLETE | Separate offering-gap evidence/reconciliation work exists | First-class offering discovery, campus linkage, delivery modes and provenance | P2 |
| SRC-01 | Official source registry and qualification | PARTIALLY COMPLETE | Source leads, qualification workflow, configured GCU pilot | Authority/access/coverage validation per source; no source lead counted as qualified | P1 |
| SRC-02 | Seven-jurisdiction coverage | NOT STARTED / PARTIALLY COMPLETE | National source leads cover multiple authorities/jurisdictions | Accepted coverage matrix for Punjab, Sindh, KP, Balochistan, ICT, GB and AJK | P2 |
| AG-01 | Safe retrieval, snapshots, hashes | IMPLEMENTED / PRODUCTION UNVERIFIED | Guarded HTTP retrieval, snapshots and evidence lineage | Live bounded qualification, retention and failure recovery evidence | P1 |
| AG-02 | Deterministic identity discovery | PARTIALLY COMPLETE | Configured-source deterministic top-level identity staging | Unknown-institution enumeration and broad source-specific extraction | P1 |
| AG-03 | Campus and affiliation discovery | PARTIALLY COMPLETE | Campus extraction heuristics and parent-aware staging improvements | Parent resolution, affiliated/constituent college extraction and relationship QA | P2 |
| AG-04 | AI programme extraction | IMPLEMENTED / DISABLED | Optional OpenAI-compatible programme adapter and tests | Provider/model compatibility, exact snapshot excerpt grounding, limits, audit and separately approved live test | P3 |
| AG-05 | Autonomous AI institution agent | NOT STARTED | No evidence of a completed autonomous institution identity AI agent | Design only after deterministic discovery and governance meet acceptance; AI cannot approve/promote/publish | Deferred |
| GOV-01 | Candidate staging and review packages | PARTIALLY COMPLETE | Candidate batches, evidence and private review package architecture | Fresh protected evidence, row-level lineage and idempotency acceptance | P1 |
| GOV-02 | Identity/duplicate resolution | PARTIALLY COMPLETE | Exact/known duplicate classification and identity decision services | Parent/campus ambiguity handling, conflicts and human decision audit | P1 |
| GOV-03 | Field approvals and reviewer separation | IMPLEMENTED / PRODUCTION UNVERIFIED | Field-level approval workflow and reviewer/proposer controls exist | Production schema, permissions and end-to-end acceptance | P1 |
| GOV-04 | Canonical promotion and publication | IMPLEMENTED / PRODUCTION UNVERIFIED | Separate routes/services and reversal/audit mechanisms exist | Explicit authorization and record-level transaction/reversal/public visibility tests | P2 |
| OPS-01 | Production revision/runtime/schema | BLOCKED | Prior diagnostic architecture and controlled workflows exist | Fresh exact revision/runtime/schema/ledger/index report and parity evidence | P0 |
| OPS-02 | Scheduler/queue ownership | BLOCKED | Laravel hourly scheduler and GitHub scheduled runner identified | Hostinger cron/worker evidence; one scheduler or shared lock; retries/recovery | P0 |
| OPS-03 | Private storage/backups/monitoring | IMPLEMENTED / PRODUCTION UNVERIFIED | Private evidence/report workflows and monitoring definitions exist | Confirm storage/retention, backup restore test, current run/failure monitoring | P1 |
| AUTO-01 | GitHub workflow governance | PARTIALLY COMPLETE | 35 workflows inventoried; trigger and side-effect classes recorded | Enabled/active state evidence, owner/run ledger and trigger controls | P0 |
| AUTO-02 | Cross-runner locking and Git parity | BLOCKED | GitHub concurrency and Laravel `withoutOverlapping` exist separately | Shared lock/idempotency or one execution plane; parity fetch outside deployed checkout | P0 |
| SEC-01 | Security findings | BLOCKED FOR PRODUCTION RESUME | Findings and locations documented in Step 2 | Fix SEC-01…SEC-05 and regression tests; review remaining medium findings | P0 |
| QA-01 | Test-suite completeness | PARTIALLY COMPLETE | Feature tests and CI pipelines exist; PR #954–#957 checks pass | Include Unit suite, test migration equivalence, public-state and security regressions | P0 |
| GOV-05 | Operational governance/maintenance | NOT STARTED / PARTIALLY COMPLETE | Existing tracker and controller provide some reporting | Named owner, runbook, per-source freshness, escalation and correction procedure | P2 |

## 4. Consolidated findings and implementation truth

### 4.1 Application, public interface and data model

The existing application should be repaired and extended, not rebuilt. Public listing, detail, API, search, admin/operator, import, source, evidence, candidate, approval, promotion and publication components exist. Their presence does not prove deployed parity or complete end-to-end operation. Historical production evidence of HTTP 200 and valid empty JSON dates to October 29 and is not a fresh October 31 acceptance check. The current live public state remains unverified.

Institution/campus/programme relationships exist, but campus extraction does not fully resolve parent identity, and programme-campus offering discovery is not yet a complete first-class pipeline. Public academic status allowlists differ between HTML/API/search paths and must be unified.

### 4.2 Source and AI capability truth

The system has a meaningful deterministic source pipeline: configured source retrieval, safe URL/HTTP controls, immutable snapshots/hashes, deterministic extraction, candidate quality checks, duplicate classification, staging, evidence/provenance and review packages. This is a governed pilot foundation, not nationwide autonomous enumeration.

The Step 3 audit found 24 configured monitoring profiles: two enabled GCU profiles and 22 disabled; no profile was AI-enabled. Twenty additional HEC identity profiles remain disabled. The national source register contains 12 leads across seven jurisdictions, all requiring live validation. These are leads, not qualified coverage.

The institution identity discovery service is deterministic and explicitly rejects AI-enabled monitoring. Optional AI is limited to programme extraction and disabled by default. Its excerpt grounding, model compatibility, cost controls and live qualification remain incomplete. Do not market or operate it as a completed autonomous AI agent.

### 4.3 Governance and historical data

Candidate staging, evidence, review packages, identity decisions, field-level approval, canonical promotion and publication are separate capabilities. That separation is a strength. It must remain explicit in implementation and authorization.

GCU historical candidate rows #348–353 and batches #19–23 are **BLOCKED / NOT VERIFIED** because protected export/snapshot evidence has not been reconciled. No row-level decision may be inferred from the historic 112-row reference. Preserve all those records unchanged until an authorized evidence-recovery and review phase.

### 4.4 Production and automation

Step 4 inventories 35 GitHub workflow definitions, eight with schedules, plus two hourly Laravel scheduler commands: `institutes:bootstrap-agent` and `institutes:monitor-changes`. The scheduled GitHub discovery workflow also invokes those commands over SSH. GitHub workflow concurrency and Laravel `withoutOverlapping(55)` do not demonstrate a shared cross-runner lock. The monitoring chain can write source/policy, retrieval, snapshot, observation, evidence, candidate and review-package records even though the inspected path does not directly promote or publish canonical records.

The actual Hostinger cron entry, queue worker/Supervisor, workflow enabled states, active runs, current queue/failed jobs, production revision/runtime, DB grants, source policy rows and backup retention remain unverified. Several diagnostics still fetch Git history inside the deployed checkout. Do not call these processes fully read-only.

## 5. Priority security, reliability and quality backlog

All findings below remain open until a later implementation PR and exact-head regression evidence demonstrate a fix. This strategy does not claim any are fixed.

| Task | Finding | Required remediation and regression |
|---|---|---|
| SEC-01 | First-admin setup ignores `master_admin` | Count every privileged admin role in setup guard; test master_admin-only database cannot expose setup |
| SEC-02 | Raw JSON-LD script-context output | Use HTML-sensitive JSON encoding (e.g. JSON_HEX flags) and test hostile script-closing payload |
| SEC-03 | Evidence capture trusts caller-selected verification state/subject refs | Server-authorize allowed state transitions and bind subject references to permitted owned records; test forged values |
| SEC-04 | Public academic-state filtering inconsistent | Centralize public allowlists across HTML/API/search/related records; test draft/candidate/hidden states never leak |
| SEC-05 | Review CSV formula injection | Neutralize spreadsheet formula-leading cells; test =, +, -, @ and leading whitespace/control variants |
| QA-01 | PHPUnit excludes Unit suite | Include tests/Unit in configured suite and prove composer test runs both Feature and Unit |
| REL-01 | GitHub/Laravel duplicate execution planes | One authoritative scheduler or shared DB/Redis lock plus idempotency key and overlap test |
| REL-02 | Git fetch in deployed checkout | Use disposable temporary repository for all parity checks; assert deployed `.git` is never fetched or changed |
| DB-01 | Existing-schema migrations check column existence only | Compare types, nullability, defaults, indexes and constraints; test matching, missing and mismatched schemas |
| DB-02 | Official refresh schema-equivalence overclaim | Enforce full structural equivalence and accurate migration ledger/readiness output |
| REL-03 | Search/nearby scaling | Bound inputs/results, query and distance filtering at DB level, add representative performance tests |
| REL-04 | AI excerpts not verified against captured source | Require excerpt/locator to match normalized snapshot content; reject fabricated or stale excerpts |
| QA-02 | Pint CI autoformats before check | Make formatting check non-mutating and fail on unformatted code |
| QA-03 | Password recovery not end-to-end | Separate informational follow-up; verify only if password recovery is in scope |

P0 remediation should begin only after the current production baseline and operational control are known. Avoid mixing security changes with unrelated nationwide extractor work.

## 6. Dependency-aware implementation roadmap

The sequence below preserves the required gates. Later phases depend on accepted evidence from earlier phases; phase names are project roadmap labels, not permission to execute.

### Phase 0 — Audit and documentation baseline

**Objective:** establish a reviewed, internally consistent strategy and tracker.  
**Tasks:** review all four reports; verify PR/head/check state; reconcile current tracker; record uncertainty without guessing.  
**Dependencies:** none beyond repository access.  
**Deliverables:** this strategy, updated existing master tracker, reviewed audit PRs.  
**Suggested PR boundary:** one documentation-only PR (this Step 5 PR).  
**Tests/evidence:** Markdown/structure validation; exact-head Test and Laravel CI.  
**Acceptance:** all four audit reports accounted for, current SHA/PR/check state recorded, stable tasks and gates documented.  
**Risks:** reports are unmerged; future main changes may supersede evidence.  
**Must not happen yet:** no implementation, production inspection or freeze action.

### Phase 1 — Production observability and safe operational control

**Objective:** establish the real production and automation state before resuming discovery.  
**Tasks:** resolve the outstanding runtime diagnostic delivery issue; obtain authorized read-only deployed revision/PHP/Laravel/Artisan evidence; verify schema/indexes/migration ledger; inspect exact Hostinger cron command/timezone/enabled state; identify queue worker/Supervisor and failed-job handling; inspect current active GitHub runs/workflow enabled state; determine DB grants; establish one scheduler or shared lock; isolate all deployed Git parity fetches.  
**Dependencies:** Step 5 strategy review; separate authorization for each production read path; PR #953 status review.  
**Deliverables:** current production readiness evidence package, scheduler ownership decision, safe freeze/resume decision record, parity isolation PR(s).  
**Suggested PR boundaries:** (1) diagnostic delivery repair; (2) isolated parity fetch repairs; (3) scheduler lock/ownership design; (4) runtime/schema acceptance tests.  
**Tests/evidence:** shell/workflow contract tests; isolated runtime diagnostic; schema read-only report; hPanel cron screenshot/export; worker/process evidence; active-run inventory.  
**Acceptance:** exact deployed revision and runtime known; schema/ledger match; cron/worker known; no uncontrolled duplicate execution; diagnostic report delivered and reviewed.  
**Risks:** shared-hosting limits, inaccessible process state, overlapping jobs, partial writes.  
**Must not happen yet:** no imports, source qualification, profile activation, candidate decisions, migrations, deployments, or global resume. A freeze itself needs separate explicit authorization.

### Phase 2 — Security and data-integrity remediation

**Objective:** close the P0 trust/security issues before broader production activity.  
**Tasks:** SEC-01 through SEC-05; QA-01; DB-01/DB-02; review REL-03/REL-04 and Pint behavior; add regression tests and migration contract fixtures.  
**Dependencies:** accepted Phase 0 baseline; exact code locations revalidated against current main; production operational control from Phase 1 for any later live acceptance.  
**Deliverables:** focused security/data-integrity PRs and regression evidence.  
**Suggested PR boundaries:** admin/bootstrap and JSON-LD; evidence authorization and public-state allowlists; CSV export safety; PHPUnit and migration equivalence; performance/AI grounding follow-ups.  
**Tests/evidence:** exploit regression tests, Feature+Unit suite, isolated MySQL migration-equivalence matrix, formatter check, static analysis and CI.  
**Acceptance:** every P0 finding has a merged fix and exact-head tests; no test bypass; migration checks reject mismatched schema.  
**Risks:** compatibility changes to existing records and reviewers; broad refactor scope.  
**Must not happen yet:** do not alter production schema or run migrations without a separately approved controlled migration plan.

### Phase 3 — GCU pilot evidence recovery and qualification

**Objective:** recover the evidence needed to make safe, row-level decisions on the existing GCU pilot.  
**Tasks:** use the protected authorized evidence path to retrieve historical export and relevant snapshots; record checksums, counts and timestamps; reconcile rows #348–353 and batches #19–23 without mutation; inspect current GCU profile and qualification prerequisites; distinguish source response evidence from workflow success.  
**Dependencies:** Phase 1 protected access/runtime controls; Phase 2 evidence authorization fixes where relevant; explicit authorization for protected evidence retrieval and separately for source qualification.  
**Deliverables:** private evidence reconciliation report, row-to-snapshot lineage matrix, qualification readiness decision.  
**Suggested PR boundary:** documentation/tests only for reconciliation tooling; no candidate data changes in this phase.  
**Tests/evidence:** checksum verification, exact row/batch matching, snapshot hash/excerpt integrity, read-only qualification report if separately authorized.  
**Acceptance:** every historical row and batch is accounted for or explicitly unresolved; no mutation; source authority and retrieval evidence are sufficient to request qualification.  
**Risks:** protected export unavailable or incomplete; historical IDs may not map cleanly.  
**Must not happen yet:** no approvals/rejections/identity resolutions, candidate regeneration, promotion or publication. GCU qualification itself requires separate authorization.

### Phase 4 — Governed GCU candidate review

**Objective:** complete a human-reviewed pilot with evidence and identity lineage.  
**Tasks:** validate each candidate's identity, source, snapshot, hash, extractor version and excerpt; review duplicates and parent/campus relationships; inspect programmes/campuses; record proposer/reviewer separation; create explicit row-level decisions; keep approval, promotion and publication distinct.  
**Dependencies:** Phase 3 accepted evidence; Phase 2 governance/security fixes; production schema/roles verified; separate authorization for staging and each decision category.  
**Deliverables:** GCU review package, decision log and unresolved-conflict register.  
**Suggested PR boundary:** review UI/validation fixes separately from any approved record decision.  
**Tests/evidence:** role separation, stale-evidence rejection, duplicate conflict, transaction rollback and audit history tests.  
**Acceptance:** each candidate has complete lineage and explicit human disposition; ambiguous identity remains unresolved; review history is immutable and attributable.  
**Risks:** source evidence may not support an unambiguous identity; reviewer availability.  
**Must not happen yet:** no bulk approval; no automatic promotion; no public publication.

### Phase 5 — General institution and campus discovery

**Objective:** discover institutions beyond the known configured identity seeds and model their hierarchy.  
**Tasks:** build unknown-institution enumeration from qualified official directories; implement source-specific institution and campus extraction; resolve parent/constituent/affiliated relationships; add deterministic identity normalization, deduplication and conflict staging; define programme-campus offering discovery.  
**Dependencies:** accepted GCU pilot; qualified directory sources; reliable snapshots/evidence and identity governance.  
**Deliverables:** source-specific extractors, unknown-identity staging, campus/affiliation/college relationship model and test fixtures.  
**Suggested PR boundaries:** one extractor family per source type; separate parent-resolution service; separate offering model/pipeline.  
**Tests/evidence:** golden source fixtures, duplicate/parent edge cases, idempotency, parser drift and evidence provenance.  
**Acceptance:** new institutions can be enumerated from an approved directory, staged with evidence, deduplicated and linked to parent/campus without canonical writes.  
**Risks:** inconsistent source formats, source ambiguity and nonstandard campus naming.  
**Must not happen yet:** do not enable national schedules or infer identities from weak evidence.

### Phase 6 — National source qualification and extractor expansion

**Objective:** build defensible coverage across all seven jurisdictions and relevant institution categories.  
**Tasks:** qualify sources one at a time; map HEC university/campus, provincial higher education, TEVTA, federal, IBCC/school-college, professional regulator, AJK and GB authorities; document authority/access terms/rate limits; implement province-specific extractors; add drift monitoring; keep new profiles disabled until accepted.  
**Dependencies:** Phase 1 operational control; Phase 5 extraction and parent resolution; source access policy review.  
**Deliverables:** seven-jurisdiction coverage matrix, accepted source register, source qualification dossiers and extractor suite.  
**Suggested PR boundaries:** one source/authority family per PR, with profile creation separate from activation.  
**Tests/evidence:** authority evidence, source response tuples, snapshots/hashes, parser precision/recall fixtures, rate/size/time limits and failure tests.  
**Acceptance:** every enabled source has accepted authority, access policy, retrieval and extraction evidence; uncovered categories/regions are explicitly disclosed.  
**Risks:** inaccessible or unstable government websites, overlapping authority and stale lists.  
**Must not happen yet:** do not activate all 20 disabled HEC profiles or bulk-enable new sources.

### Phase 7 — AI extraction qualification

**Objective:** qualify optional AI only as a constrained assistant to deterministic extraction.  
**Tasks:** keep AI disabled until separately approved; verify provider/model contract; verify every quote/excerpt against the captured snapshot; add deterministic locator validation, cost ceilings, timeouts, retries, output schema validation and audit metadata; prove AI cannot approve, resolve identity, promote or publish.  
**Dependencies:** stable Phase 5/6 deterministic pipeline, source snapshots, security/governance controls, cost/provider decision.  
**Deliverables:** AI qualification dossier, model/provider contract tests, cost and failure controls, separately gated opt-in configuration.  
**Suggested PR boundaries:** grounding validator; provider compatibility/cost guard; isolated AI qualification workflow; no automatic profile activation.  
**Tests/evidence:** fabricated excerpt rejection, stale snapshot rejection, timeout/retry/cost cap, deterministic fallback and permission boundary tests.  
**Acceptance:** AI outputs are reproducibly grounded and auditable; all writes remain candidate staging only; AI is disabled unless explicitly accepted.  
**Risks:** provider drift, cost spikes, hallucinated facts and weak source text.  
**Must not happen yet:** no AI identity discovery, approval, promotion or publication.

### Phase 8 — Controlled automation rollout

**Objective:** resume only approved source processes under one coherent scheduler and bounded execution.  
**Tasks:** choose one scheduler or prove shared lock; test idempotency and crash recovery; resume one approved source; separate retrieval/observation from candidate staging where possible; monitor per-source results and run ledger; add alerting and bounded retry/backoff; expand one source at a time.  
**Dependencies:** Phases 1–7 as applicable; explicit authorization for retrieval, monitoring and candidate staging as separate categories.  
**Deliverables:** controlled runbook, source-by-source activation record, per-source run ledger and failure/recovery procedures.  
**Suggested PR boundaries:** shared lock/idempotency; scheduler owner; one source activation change per PR; run ledger/alerts.  
**Tests/evidence:** overlapping runner test, repeat-run idempotency, partial failure recovery, stale source and rate-limit tests; live bounded run only when separately authorized.  
**Acceptance:** no duplicate uncontrolled run; each source's run is attributable, bounded and recoverable; candidate staging remains governed.  
**Risks:** hidden Hostinger cron/worker, source drift, partial writes.  
**Must not happen yet:** no all-source enablement, no automatic human decisions, no canonical publication.

### Phase 9 — Public directory and nationwide production acceptance

**Objective:** demonstrate that the public directory accurately presents only approved, evidence-backed records at sustainable scale.  
**Tasks:** coverage review across institution/campus/programme/offering; HTML/API/search/detail/nearby acceptance; empty/populated state checks; security/performance/accessibility review; SEO and sitemap; backup restoration; operational ownership and maintenance handover.  
**Dependencies:** approved canonical data from earlier phases; stable production runtime; source coverage and governance sign-off.  
**Deliverables:** production acceptance report, coverage statement, operational runbook and maintenance calendar.  
**Suggested PR boundaries:** public API/filter consistency; performance; accessibility/SEO; final acceptance docs.  
**Tests/evidence:** route smoke tests, API schema/JSON, publication allowlist, load tests, accessibility checks, backup restore evidence and source freshness metrics.  
**Acceptance:** only approved/publishable data is public; no unexplained API errors/empty states; coverage gaps are disclosed; operational maintenance is assigned.  
**Risks:** incomplete national coverage and data freshness.  
**Must not happen yet:** do not claim nationwide completeness where source categories remain uncovered.

## 7. Automation freeze/resume policy

**No freeze has been executed by these audits.** Step 4 recommendations are proposals only.

Preserve CI, security/branch protections, backups, audit logs, evidence retention and public HTTP monitoring. The highest-priority future pause candidate is the complete `scheduled-institute-discovery.yml` workflow because it has hourly, manual and `workflow_run` triggers and performs production writes. Removing only its cron expression would not block the other triggers.

Before any pause decision, obtain current active-run evidence and inspect Hostinger Cron Jobs read-only to identify the exact Laravel `schedule:run` command, frequency, timezone and enabled state. Do not cancel an active run merely because a workflow is disabled. Do not touch queue workers until their actual consumer is identified. The offering-gap preflight should not run on a scheduled credential until SELECT-only DB grants are evidenced. Diagnostics that fetch inside deployed `.git` should be isolated before routine resumption.

Resume in stages: CI → one authorized read-only runtime/schema diagnostic → scheduler ownership/shared lock → one-source read-only qualification → monitoring without staging if separable → explicitly authorized GCU staging → human review → separately authorized canonical promotion/publication → one-source-at-a-time expansion → AI last and separately.

## 8. Realistic effort and timeline estimates

Estimates are **person-days of engineering effort**, not promised calendar dates. A person-day is one focused engineer-day. Ranges exclude unknown external waiting time; human review is shown separately.

| Phase | Engineering effort (min–likely) | Calendar duration (minimum–likely) | Human/external dependency |
|---|---:|---|---|
| 0 Audit/strategy baseline | 1–2 days | 1–3 working days | PR review and exact-head checks |
| 1 Production observability/control | 4–8 days | 1–3 weeks | Hostinger access, runtime report, owner decision |
| 2 Security/data-integrity | 8–15 days | 2–4 weeks | Focused review and regression cycles |
| 3 GCU evidence recovery | 2–5 days | 1–3 weeks | Protected export and snapshot access |
| 4 GCU candidate review | 4–8 engineering days | 2–5 weeks | Approx. 3–10 reviewer-days depending on candidate volume/conflicts |
| 5 General discovery/campus | 10–20 days | 3–6 weeks | Official directory formats and fixture collection |
| 6 National qualification | 12–25 days | 4–10+ weeks | Authority/access review; approx. 5–20 source-review days |
| 7 AI qualification | 5–10 days | 2–4 weeks | Provider access, model/cost decision and evaluation |
| 8 Automation rollout | 5–10 days | 2–4 weeks | Hosting change windows and bounded live runs |
| 9 Nationwide acceptance | 5–10 days | 2–4 weeks | Data coverage review, load/accessibility and backup restore |

Some phases can overlap only after their dependencies are met (e.g. source authority research can run in parallel with isolated security fixes). Phases 3–4 depend on evidence recovery and governance; Phase 5 depends on the pilot; Phase 6 depends on source-specific extractors; Phase 7 depends on grounding and deterministic controls; Phase 8 depends on operational ownership.

### Overall scenarios

| Scenario | Engineering effort | Indicative calendar | Scope |
|---|---:|---|---|
| Minimum viable controlled completion | 30–45 person-days | 8–12 weeks | Production control, P0 security fixes, one GCU pilot reviewed, one or two accepted sources, safe staging and public empty/populated acceptance |
| Recommended production-ready completion | 55–85 person-days | 4–6 months | Security and operational readiness, governed GCU pilot, multi-jurisdiction source coverage in agreed priority categories, deterministic campus discovery, controlled automation and public acceptance |
| Full nationwide coverage + mature AI-assisted automation | 80–125+ person-days | 6–10+ months | Broader source qualification across seven jurisdictions, institution/campus/offering coverage, AI grounding/cost qualification, mature operations and recurring review |

Calendar ranges assume at least one engineer with regular availability and timely hosting/source access. Human review, source authority responses, website changes, rate limits, data ambiguity and production change windows can extend them substantially. No fixed completion date is promised.

## 9. Definition of DONE

### Public directory
- Fresh production HTML/API checks succeed and return valid, documented responses.
- Listing, filters, search, detail and geography relationships work for empty and populated states.
- Public HTML/API/search use the same publication allowlists.
- Only approved and publishable canonical records are visible.
- Performance, accessibility and SEO acceptance are recorded.

### Data coverage
- Seven jurisdictions have an explicit category-by-category coverage matrix.
- Each included source has accepted authority, access-policy and qualification evidence.
- Institution, campus, parent/affiliate, programme and offering relationships are evidence-backed.
- Uncovered categories, regions and stale sources are disclosed; no unsupported nationwide-complete claim.

### Discovery and AI agent
- Unknown institutions can be enumerated from qualified official directories using deterministic, testable extractors.
- Snapshots, hashes, extractor versions, locators and excerpts are retained and verified.
- Duplicate/identity conflicts are staged for human decisions.
- Campus-parent and offering relationships are resolved or explicitly left ambiguous.
- AI, if enabled, is optional, source-grounded, bounded by cost/time/retry controls and cannot approve, promote or publish.

### Governance
- Every candidate has source and evidence lineage.
- Every ambiguous identity has an attributable human decision.
- Field approval, reviewer/proposer separation, promotion and publication are distinct.
- Reversal and audit history are tested and retained.
- Historical GCU candidate records are reconciled without loss or silent rewrite.

### Production and operations
- Deployed revision/runtime and schema/migration ledger match accepted evidence.
- Required tables/indexes and Artisan commands are verified.
- Scheduler/queue ownership and cross-runner concurrency are controlled.
- Private storage, evidence retention, backups and restoration are tested.
- Monitoring, alerts, failed-run recovery and source freshness metrics are operational.
- A named owner and runbook exist for source onboarding, corrections, incidents and maintenance.

## 10. Risk register and controls

| Risk | Severity | Control / exit condition |
|---|---|---|
| Unverified production revision/schema | Critical | Fresh authorized read-only evidence before any write path |
| Duplicate scheduler and partial writes | Critical | One scheduler or proven shared lock; inspect active runs first |
| Security findings remain open | High | Focused remediation PRs and regression tests |
| GCU history unavailable or mismatched | High | Protected export/snapshot reconciliation; preserve rows/batches |
| Source leads mistaken for qualified sources | High | Per-source qualification dossier and explicit disabled-by-default profile |
| AI excerpt hallucination/provider drift | High | Snapshot-grounded excerpt validation and AI disabled by default |
| Canonical promotion/publication too early | Critical | Separate explicit authorization and row-level human approval |
| Private evidence loss/overwriting | High | Retention, immutable identifiers/checksums and access controls |
| Nationwide coverage overclaim | High | Jurisdiction/category matrix and transparent uncovered scope |
| External website drift/rate limiting | Medium/High | Bounded retrieval, backoff, drift tests and per-source monitoring |

## 11. Master tracker and stable roadmap tasks

The existing tracker `docs/PROJECT_MASTER_TRACKER.md` remains authoritative. This strategy adds a dated roadmap section there. Stable task IDs use the prefixes `BASE`, `OPS`, `SEC`, `GCU`, `DISC`, `SRC`, `AI`, `AUTO`, `PUB` and `GOV`. Status is a planning baseline and must be updated only with new evidence.

The tracker extension contains the task register with priority, status, dependency, planned phase/PR, acceptance evidence, responsible role, effort and next action.

## 12. Recommended next execution prompt

After the user reviews and explicitly approves this Step 5 strategy PR, the recommended next task is **Phase 1A — Production Observability Evidence Acquisition Plan and Read-only Diagnostic Delivery Verification**. Do not start a freeze or any production command automatically.

The next prompt should require:
1. Re-verify current `main`, PR #953 and its exact-head checks.
2. Inspect only repository/workflow evidence first; do not dispatch or rerun.
3. Prepare a read-only evidence request for the exact Hostinger cron command/timezone/enabled state, queue worker state and active run inventory.
4. Define a one-time authorized runtime/schema diagnostic scope and its output contract; do not execute it without separate explicit approval.
5. Do not read/write production DB, run Artisan, contact sources, change cron/workflows, migrate/deploy, stage candidates or alter historical GCU rows.
6. Return an evidence matrix separating confirmed, historical and unverified facts; stop for review.

## 13. Strategy limitations and review rule

- Four audit reports are on open, unmerged PR branches. Their exact-head CI success is not a substitute for merge or production acceptance.
- GitHub connector access does not prove current Hostinger cron/worker state, full active-run inventory, database grants or current deployed revision.
- Estimates are planning ranges and depend on resource availability, source access and human review.
- If evidence conflicts, preserve the conflict as a blocker. Do not silently reconcile by guessing.
- Recheck all SHAs, PR states, checks and production evidence before each implementation phase.

## 14. Safety and stop condition

This strategy and tracker update are documentation-only. No application code, workflow, source profile, production configuration, database, cron, queue, private report or canonical data is changed by this plan. No workflow is paused, disabled, dispatched, rerun or cancelled. No imports, source qualification, discovery, monitoring, migrations, deployment, approvals, identity decisions, promotion, reversal or publication are authorized.

**Step 5 deliverable status:** prepared for review in a documentation-only PR.  
**Implementation status:** NOT STARTED.  
**Production operations:** NOT PERFORMED.  
**Next action:** user reviews the Step 5 PR and explicitly approves any next phase.
