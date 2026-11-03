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

The older Draft PR #975 was closed as superseded. Its replacement, PR #981, is the active security-remediation candidate, rebuilt on current main.

It currently proposes:
- Include `master_admin` in the initial administrator-exists guard.
- Apply HTML-sensitive JSON encoding flags to both institution and breadcrumb JSON-LD.
- Neutralize formula-leading string values in discovery review CSV exports.
- Include both Feature and Unit tests in PHPUnit discovery.

Before merge, the PR owner must:
1. Rebase or recreate the work on the current `main` without force-updating unrelated branches.
2. Add or identify focused regression tests for each security behavior.
3. Verify spreadsheet-safe handling for leading whitespace/control characters and formula prefixes, while preserving non-string numeric values.
4. Verify JSON-LD remains valid JSON and hostile string values cannot terminate the script element.
5. Verify the first-admin guard blocks setup when the only existing privileged user has role `master_admin`, in both create and store paths.
6. Run PR-head Test, Laravel CI and Project Controller checks; inspect exact-head results.
7. Keep the PR Draft until review confirms all tests and behavior. Do not merge solely because it is mergeable or checks unrelated to the exact head pass.

No production deployment is included.

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

## 6. Sequencing and completion gates

| Sequence | Deliverable | Completion gate |
|---|---|---|
| 1 | PR #975 security remediation | Focused regression tests, exact-head CI, review; remain Draft until validated |
| 2 | Scheduler ownership design | Execution-path map, alternatives, chosen proposal, failure/recovery model and concurrency tests specified |
| 3 | GCU reconciliation design | Row/batch preservation rules, evidence mapping, dry-run contract and acceptance tests specified |
| 4 | Readiness decision package | Every prerequisite classified with evidence or explicit blocker; no inferred production pass |

Work may proceed on repository-only artifacts in parallel only when files and assumptions do not conflict. Do not begin implementation of scheduler changes or GCU data operations until their design has been reviewed and separately authorized.

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
- Phase 1B is being consolidated into one security/code PR and one documentation/design PR where practical.
- Production operational readiness: NOT CERTIFIED.
- PR #975: closed unmerged as superseded by PR #981.
- PR #981: open Draft; first exact-head checks exposed two existing Unit-test failures after PHPUnit Unit discovery was enabled. The unsupported-entity fixture was corrected to use a truly unsupported type; official-source identity tests were made deterministic through an injectable URL guard. New exact-head CI remains pending.
- Scheduler ownership: unresolved.
- Hostinger cron/worker provider scope: unknown.
- AUTH-04 schema metadata: not verified.
- Backup recoverability: unverified.
- GCU historical records: preserve unchanged.
- Additional HEC profiles and AI extraction: remain disabled.

This document is a design-only plan. It does not grant or imply any production execution authorization.
