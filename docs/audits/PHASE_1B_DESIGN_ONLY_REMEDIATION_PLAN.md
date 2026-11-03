# Phase 1B — Design-Only Remediation and Readiness Plan

Date: 2026-11-03

Repository: `mattari20/estudent-listing`

Verified base: `df4d9477ac8db61c93af0e2d830c9c7b380e9b3e` (PR #979 merge commit)

## 1. Purpose and boundary

Phase 1A's bounded evidence-acquisition/documentation exercise is closed. This is not a production-readiness certification. The final evidence disposition records AUTH-04 as owner-bypassed/not verified, P1A-07 as unresolved, Hostinger/provider cron and worker scope as unknown, and backup retention/recoverability as unverified.

Phase 1B may proceed only as design and repository-side preparation until each required operational authorization is separately granted.

This plan authorizes no production action. Specifically prohibited under this plan: production SSH or hPanel actions; Artisan command execution; database connections, SQL, migrations or writes; cron/queue/backup changes or restore; workflow enable/disable/dispatch/rerun/cancel; deployment; source retrieval/discovery; extraction; candidate staging or approval; import; canonical promotion; publication; activation of additional HEC profiles; and AI extraction.

Preserve GCU candidate rows #348–353 and batches #19–23. Keep the 20 additional HEC profiles and AI extraction disabled. Do not create dummy production data.

## 2. Workstream A — repository-only security remediation

**COMPLETED in PR #981**, merged at `63c3cd51c4bc73bfebd81406d1b2d4bb8ef2858f`.

Delivered:
- First-admin guard includes `master_admin` on both setup GET and POST paths.
- Institution and breadcrumb JSON-LD use HTML-sensitive JSON encoding flags.
- Discovery review CSV formula-leading string values are neutralized.
- PHPUnit discovers both Feature and Unit suites.
- Focused regression tests cover setup blocking, JSON-LD script termination and CSV formula injection.
- Two pre-existing Unit-test issues exposed by enabling Unit discovery were corrected: the unsupported entity fixture now uses an actually unsupported entity type; official-source identity tests use an injected deterministic SourceUrlGuard resolver rather than relying on live DNS. Production retains the default SourceUrlGuard behavior.

Exact-head validation:
- Laravel CI: passed, 798 tests / 3,531 assertions / 0 failures.
- Test workflow: passed, 798 tests / 3,531 assertions / 0 failures.
- Public Browser Runtime Diagnostics: passed.
- All three checks passed before merge.

No production deployment or production execution was part of this PR.

## 3. Workstream B — scheduler ownership design

Known repository facts:
- Laravel schedules `institutes:bootstrap-agent` and `institutes:monitor-changes` hourly.
- GitHub's `scheduled-institute-discovery.yml` also invokes these commands.
- GitHub concurrency and Laravel `withoutOverlapping(55)` are distinct mechanisms; a shared cross-runner lock has not been established.
- Actual Hostinger cron invocation and provider-managed worker scope remain unknown.

Design deliverables:
1. Draw the execution paths for GitHub Actions, Laravel scheduler and any possible Hostinger/provider scheduler, labeling unknown edges as unknown.
2. Define one intended scheduler owner and a single cross-runner coordination strategy. Compare (a) one authoritative scheduler, (b) a shared database/cache lock with bounded TTL and owner identity, and (c) idempotent command-level run claims.
3. Specify lock acquisition, expiry, crash recovery, duplicate-run behavior, observability and safe manual invocation.
4. Define idempotency requirements for both commands and tests proving concurrent invocations cannot duplicate source observations, candidate batches or other side effects.
5. Record a decision matrix and a recommended architecture without changing any workflow, schedule, lock backend or production configuration.

Do not claim scheduler ownership is resolved until provider/Hostinger evidence is supplied and the design is implemented and validated separately.

## 4. Workstream C — GCU preservation and reconciliation design

Prepare a non-executing reconciliation specification for historical GCU rows #348–353 and batches #19–23:
- Define identity, provenance and duplicate-resolution rules.
- Map each historical row to its evidence and review status only when evidence is already available.
- Distinguish missing evidence from rejected evidence and from duplicate identity.
- Define immutable audit history and approval requirements.
- Specify a dry-run report and acceptance tests.

Do not query production, retrieve sources, stage candidates, alter existing rows, approve or import anything as part of this design.

## 5. Workstream D — production-readiness prerequisites

The following remain gates before any production-write proposal:
1. Explicit owner decision on whether AUTH-04 remains waived or a separately safe metadata-only method is authorized.
2. Provider/Hostinger confirmation of cron and persistent worker scope, or an explicit statement of what the provider cannot expose.
3. Scheduler ownership and cross-runner lock/idempotency design, then implementation and tests.
4. Backup retention and recoverability evidence. Any restore test needs its own recovery plan and explicit authorization.
5. A fresh production revision/runtime observation if a later operation depends on current deployment state.
6. A separate, narrowly scoped authorization for every production operation, including deployment, database access, workflow state changes, source retrieval, staging, approval, import or publication.

The current public sanitized report remains public under the previously approved exception; do not place secrets, raw logs or private diagnostic payloads in repository documentation.

## 6. Sequencing and completion gates — final record

| Delivery | PR | Final result |
|---|---|---|
| Initial Phase 1B design-only plan | #980 | MERGED at `d8a876ea0de43453b9a5c29e50b2fced78396e04` |
| Security remediation and regression tests | #981 | MERGED at `63c3cd51c4bc73bfebd81406d1b2d4bb8ef2858f`; exact-head Laravel CI, Test and browser diagnostics passed |
| Scheduler ownership, GCU reconciliation, production-readiness package and tracker | #982 | MERGED at `a844f6f545df3a312a8d391e2e002822fb6a8b4a`; exact-head Laravel CI, Test and Project Controller passed |
| Final closeout report and tracker status | #983 | MERGED at `2e0f6e21e8c66e746dcd57ad10deab2b9af551a8` |
| Final documentation formatting/evidence correction | #984 | MERGED at `5fb31b3d5873ab114c3354bfc012a8a90b687b6c`; exact-head Laravel CI, Test and Project Controller passed |

PR #975 was closed unmerged as superseded by #981. PRs #983 and #984 were closeout/accuracy corrections, not separate workstream implementation PRs. GitHub assigns PR numbers; the next phase's expected PR numbers are planning labels only.

**Phase 1B is CLOSED.** Its defined deliverables are complete. Do not reopen or expand this phase. Any future correction to a Phase 1B artifact must be narrowly scoped, documentation-only unless a material factual defect is proven, and must not silently reopen production implementation. New operational work belongs to Phase 1C.

## 7. Reporting rules

For every task, record:
- Exact base and head commit.
- Files changed and whether the change is documentation-only or code-only.
- Focused tests and exact-head workflow results.
- Known facts versus assumptions and unresolved evidence.
- Explicit production-impact statement.

A merged PR is not proof of production deployment, live scheduler health, schema correctness, backup recoverability, source qualification or directory data completeness.

## 8. Initial status

- PR #979: merged; Phase 1A evidence exercise closed with explicit blockers.
- PR #980: merged; Phase 1B design-only plan established.
- Phase 1B's two substantive delivery PRs #981 and #982, plus closeout PRs #983 and #984, are all merged. Phase 1B is CLOSED.
- Production operational readiness: NOT CERTIFIED.
- PR #975: closed unmerged as superseded by PR #981.
- PR #981: merged at `63c3cd51c4bc73bfebd81406d1b2d4bb8ef2858f`; exact-head Test and Laravel CI each passed 798 tests / 3,531 assertions; public browser diagnostics passed.
- Scheduler ownership: unresolved.
- Hostinger cron/worker provider scope: unknown.
- AUTH-04 schema metadata: not verified.
- Backup recoverability: unverified.
- GCU historical records: preserve unchanged.
- Additional HEC profiles and AI extraction: remain disabled.

This document is a design-only plan. It does not grant or imply any production execution authorization.


## Final status update (2026-11-03)

This original design-only plan is superseded for status purposes by `docs/audits/PHASE_1B_FINAL_CLOSEOUT_REPORT.md`.

- PR #981 security remediation: merged and exact-head checks passed.
- PR #982 consolidated scheduler, GCU and production-readiness design package: merged and exact-head checks passed.
- Phase 1B defined deliverables: COMPLETE.
- Production operational readiness: NOT CERTIFIED.
- AUTH-04, provider cron/worker visibility, scheduler ownership implementation, backup recoverability and separately authorized future operational actions remain carried gates.

No production execution authorization is created by this status update.


Final closeout PR #983 is merged. See `docs/audits/PHASE_1B_FINAL_CLOSEOUT_REPORT.md` and the final closeout section of the master tracker. Phase 1B defined deliverables are complete; production operational readiness remains NOT CERTIFIED.


## Phase 1B freeze notice (2026-11-03)

The Phase 1B scope is complete and frozen. Its security changes are merged but were not deployed by this phase. Its scheduler and GCU deliverables are designs/specifications, not live operational implementations. The carried production-readiness blockers are transferred to the Phase 1C plan. Do not reopen Phase 1B to implement those blockers; use new Phase 1C PRs with independent checks and authorization.
