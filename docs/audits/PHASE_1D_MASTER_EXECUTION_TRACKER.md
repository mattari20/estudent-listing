# Phase 1D — Master Execution Tracker

**Repository:** `mattari20/estudent-listing`  
**Execution scope:** Phase 1D.2B through Phase 1D.6  
**Excluded:** Phase 1D.7 backup and restore  
**Production boundary:** READ-ONLY / NO PRODUCTION EXECUTION  
**Last verified main SHA:** `00cfce7d73ed8647b07d3f185aa16be081913d08` (2026-11-11)

## Authoritative completed baseline

| Phase | Status | PR | Merge commit / evidence |
|---|---|---:|---|
| 1D.1 inventory | MERGED | #1067 | Inventory merged; see closeout #1068 |
| 1D.1 independent closeout | MERGED | #1068 | `2fb7efc1a333637d3501662cedd48819d1245fb8` |
| 1D.2 recovery-readiness investigation | MERGED | #1069 | `193cf8267ce4e69f904b1cb9c05e8e3c0af7126c` |
| 1D.2A diagnostic source correction | MERGED | #1070 | `00cfce7d73ed8647b07d3f185aa16be081913d08` |

PR #1070 final focused MySQL 8.4 / MariaDB 11.8.9 matrix passed (run #42977214545); Laravel CI passed (run #42977413880); Test passed (run #42977413888). Test 1A / Test 1B were not restarted.

## Remaining stages

| Phase | Status | Branch | PR | Commit | MySQL | MariaDB | Blockers / next action |
|---|---|---|---|---|---|---|---|
| 1D.2B full schema and migration-ledger reconciliation | IN PROGRESS — DRAFT PR; FULL SCHEMA CONTRACT OPEN | `phase1d2b/full-schema-ledger-reconciliation` | [#1071](https://github.com/mattari20/estudent-listing/pull/1071) (draft) | `6418ee49e5babef60c1293cc1635451841c6ef3c` (at last check) | NOT RUN | NOT RUN | Full-ledger inventory and target migration/object consistency matrix implemented; full source-derived 39-table schema manifest/comparator remains open; exact-head CI/review pending. |
| 1D.3 existing production data compatibility | NOT STARTED | — | — | — | — | — | Must wait for 1D.2B formal acceptance; production row-level evidence remains unavailable and must stay NOT_CHECKED. |
| 1D.4 controlled recovery implementation | NOT STARTED | — | — | — | — | — | Must use accepted 1D.2B/1D.3 results; no production execution. |
| 1D.5 isolated recovery testing | NOT STARTED | — | — | — | NOT RUN | NOT RUN | Must test disposable MariaDB 11.8.9 and MySQL 8.4 without restarting Test 1A/1B. |
| 1D.6 final recovery acceptance | NOT STARTED | — | — | — | NOT RUN | NOT RUN | Final acceptance package and PR after all prior gates. |
| 1D.7 backup and restore | EXCLUDED BY USER | — | — | — | NOT APPLICABLE | NOT APPLICABLE | Do not investigate, request, create, schedule, test or execute. |

## Phase 1D.2B source review observations

- The current read-only collector captures table options and sanitized `SHOW CREATE TABLE` output for its 39-table inventory, all visible columns and indexes, foreign-key metadata, trigger contracts, and the existing 18-target ledger reconciliation.
- A successful metadata read is currently reported as table metadata collection success; it is not a comparison against a complete source-derived table-definition manifest.
- The 50-column, 23-index, 23-FK and 24-trigger registries are targeted contracts, not a complete definition manifest for all columns and indexes across all 39 tables.
- The existing standalone full-ledger script was previously separate from the JSON evidence collector. The current branch integrates a full source/ledger inventory into the v2 collector; object-level partial-application reconciliation remains open.
- A Phase 1D.2B branch change now adds a versioned v2 full-ledger evidence object: all migration source filenames, all ledger rows, missing source records, ledger entries without source files, duplicates, invalid batch values, batch gaps and order anomalies (UNKNOWN when no sequence id exists). A target-object/ledger consistency matrix is now included for all 18 migrations; complete all-object source mapping remains part of the schema-manifest gate.
- Metadata visibility must remain distinct from absence. A denied or incomplete metadata query is UNKNOWN / NOT_CHECKED, never MISSING.

## Safety and acceptance rules

- Production is untouched. No production SQL, DDL, DML, migrations, backfills, repairs, deployments or workflow dispatches.
- Preserve the five open reverification records and two open monitoring alerts.
- No identity backfills.
- Do not restart Test 1A or Test 1B.
- Do not infer production PASS from isolated tests or historical artifacts.
- A stage is complete only after its work, tests, exact-head CI, required review and merge are verified.
- Do not merge a PR without required checks and review approval.
- Stop after Phase 1D.6. Do not begin Phase 1D.7.

## Current next action

Complete the source-derived schema manifest and full observed-vs-source comparator for all 39 tables, expand object provenance, then obtain exact-head CI and review for draft PR #1071. Do not start Phase 1D.3 until 1D.2B is formally accepted. Phase 1D.3 must not begin until Phase 1D.2B is formally merged and accepted.
