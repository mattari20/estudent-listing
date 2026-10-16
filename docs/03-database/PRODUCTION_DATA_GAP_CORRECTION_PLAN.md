# Production Data-Gap Correction Plan

| Field | Value |
| --- | --- |
| Document ID | PLAN-INST-DATA-CORRECTION-001 |
| Updated | 2026-10-15 |
| Scope | Institutes & Education Directory production database |
| Status | Remediation preparation; no production writes authorized or performed |
| Source evidence | Read-only production report retrieved 2026-10-15 |

## 1. Confirmed production baseline

| Area | Count / status |
| --- | --- |
| Database connection | Healthy (MySQL) |
| Required canonical tables | All six present |
| Institutions | 10 total; 10 active |
| Campuses | 0 |
| Programs | 1 total; 1 active |
| Program offerings | 0 |
| Admission intakes | 0 |
| Geography records | 0 |
| Active institutions without an active campus | 10 |
| Import batches | 5, all validated |
| Import rows | 24: 11 promoted, 6 candidate, 7 rejected |
| Import conflicts | 0 |
| Sources | 5 active |
| Evidence records | 0 |
| Source snapshots | 0 |
| Source monitoring policies | 0 |
| Source retrieval runs | 0 |
| Report mutation flag | false |

These counts describe the database at the report timestamp; they do not prove that every record is erroneous or that every institution must have every data type. Zero geography records and ten active institutions lacking active campuses are high-priority gaps. The single active program must be identified and its institution relationship verified before any repair.

## 2. Safety rules

1. Keep investigation read-only until a verified backup, restore procedure, reviewed change set, and owner approval for the specific production writes exist.
2. Do not seed geography blindly: inspect import history, source payloads, parent relationships, and slug/type uniqueness first.
3. Do not deactivate the ten institutions simply because campuses are missing. Preserve current public records unless a review establishes they are invalid.
4. Do not infer a campus, program, fee, intake date, or accreditation from an institution name.
5. Record authoritative source URL, observed/published date, retrieval timestamp, content hash/snapshot, and reviewer for each factual assertion.
6. Do not publish candidate institutions or activate campuses/programs just to make counts nonzero.
7. No production migrations, seeds, imports, promotions, activations, deletions, or SQL writes are authorized by this plan.
8. Never put credentials, .env contents, personal information, or raw connection errors in repository reports.

## 3. Remediation work packages

### WP-1 — Identify the exact ten institutions and the one program

- Generate a private, read-only inventory of canonical institution IDs, names, slugs, types, and statuses.
- Identify the single program and verify its institution relationship, status, and provenance.
- Link the 11 promoted import rows to canonical subject type/ID; distinguish matched rows from rows that created canonical records.
- Summarize the six candidate and seven rejected rows with batch/row IDs, validation errors, and rejection reasons. Avoid dumping full untrusted payloads into logs.
- Reconcile aggregate counts against an independent read-only query.

**Acceptance:** every existing institution, program, and promoted row is accounted for, with no changes made.

### WP-2 — Establish authoritative geography

- Determine the supported geography hierarchy from current application validation rules and tests.
- Build a proposed geography dataset from authoritative public sources.
- Validate parent references, type, normalized slug, and existing slug/type collisions.
- Run the geography seed preflight and review its diff before any import.
- Propose only the minimum geography hierarchy needed for verified campus locations; do not invent campuses to fit geography.

**Acceptance:** a reviewable, deduplicated geography dataset with source references and a zero-write preflight report.

### WP-3 — Verify campuses for the ten institutions

- Research each institution using official institutional pages and applicable regulator sources.
- Resolve campus names, locations, official status, and institution identity.
- Distinguish main campuses from constituent colleges, affiliated colleges, and unrelated partners.
- Prepare campus records only when identity and location are supported by evidence.
- Attach geography only when the location is adequately verified.

**Acceptance:** a per-institution coverage matrix with verified campuses, evidence, uncertain facts, and explicit no-campus cases. No unsupported campus is published.

### WP-4 — Program offerings and admissions

- Identify and verify the parent institution for the one current program.
- Do not assume a program is offered at every campus.
- Create an offering only when an authoritative source supports the campus/program association, delivery mode, and current status.
- Create admission-intake records only from current official admissions information. If no reliable current intake is published, leave it absent and record the coverage gap rather than invent dates.

**Acceptance:** each proposed offering and intake has source-backed facts, or is deferred for insufficient evidence.

### WP-5 — Source and evidence lifecycle

- Review the five active source records and confirm authority scope and URLs.
- Check whether existing source retrieval, snapshot, evidence, monitoring-policy, and lineage workflows are suitable for production.
- Repair operational records only through the established application workflow, after testing outside production.
- Verify hashes, retrieval timestamps, freshness, and evidence-to-import-row linkage before promotion eligibility.
- Do not create placeholder evidence or mark monitoring successful without a real retrieval.

**Acceptance:** a tested, auditable source lifecycle with a real successful retrieval and evidence chain, or a documented blocker.

### WP-6 — Backup, dry run, and production correction

Blocked until all of these are recorded:

- A fresh successful database backup with timestamp and retention details.
- A verified restore path, preferably tested in a non-production environment.
- A reviewed before/after change set with exact target record IDs and source references.
- Idempotency and duplicate/collision checks.
- A production preflight report and rollback plan.
- Explicit owner approval for the specific production writes.

Execute corrections in small, independently verifiable batches through supported application workflows. Capture before/after counts and rerun the read-only report. Stop on unexpected changes or failed invariants.

## 4. Priorities and gates

| Priority | Work | Gate |
| --- | --- | --- |
| P0 | Identify existing records and import-row linkage | Read-only audit |
| P1 | Verify source URLs and geography dataset | Source references and slug/parent preflight |
| P2 | Verify campus coverage | Evidence for every proposed campus |
| P3 | Verify offerings and current intakes | Official source for each relationship/date |
| P4 | Validate source-evidence lifecycle | Tests pass; real retrieval and snapshot chain verified |
| P5 | Apply production changes | Verified backup, restore path, reviewed diff, and explicit approval |

## 5. Rollback and post-correction verification

- Capture baseline report and exact target IDs before changes.
- Keep each correction batch small and separately auditable.
- Rerun the data-gap report after each batch.
- Verify no unexpected institution deactivation, duplicate slug, broken parent relationship, unsupported offering, or unverified intake was introduced.
- Check the live public directory and selected detail pages after release.
- Document remaining gaps honestly; nonzero counts do not equal completeness.

## 6. Current decision

The first report prioritizes the investigation but does not safely identify every target row or authorize bulk corrections. The immediate technical step is a protected, read-only record-level preflight that identifies canonical records and import-row links without exposing secrets or changing data. Then geography and campus candidates can be researched and validated and packaged into a reviewed dry-run correction set.

**No production data was changed during this assessment.**


## 5. Report freshness gate before remediation

The counts in the baseline above are historical and must not be treated as current indefinitely. Before preparing a record-specific change set, retrieve the newest successful private diagnostics report and compare its timestamp with the last scheduled/manual workflow run. Confirm the aggregate report, record-level audit, and geography preflight are all present and each reports `mutation_performed: false` (and geography preflight reports `seed_command_executed: false`). Reconcile totals before interpreting individual records.

If the private report is unavailable, stale, malformed, or inconsistent with aggregate counts, stop at read-only investigation and resolve the reporting gap. Do not use old counts to seed geography, create campuses, promote rows, or deactivate institutions.