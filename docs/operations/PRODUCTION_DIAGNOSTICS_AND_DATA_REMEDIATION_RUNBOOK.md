# Institutes Production Diagnostics and Data Remediation Runbook

## Document status

- Status: Proposed operational runbook; review required before adoption
- Repository: `mattari20/estudent-listing`
- Scope: protected, read-only diagnostics and planning for controlled data remediation
- Safety boundary: this runbook authorizes no production mutation, deployment, migration, import, promotion, activation, or publication change

## 1. Current verified checkpoint

As of 2026-10-16:

- PR #173, the diagnostics-controller PHP namespace/fail-closed correction, is merged.
- The post-merge Test, Laravel CI, frontend asset build, public-site monitor, and protected-diagnostics verification workflows passed for merge commit `f25218047a63882cb814d6055b4c1669b5f26ee4`.
- The private diagnostics retrieval workflow initially failed on its first run and then succeeded after the response-parsing fix in commit `f561861c08b03a41b943562e7a7e0693d1dd3a01`.
- The successful retrieval workflow confirms the endpoint's negative-authentication checks, validates the report contracts, and stores the report in the configured private reports repository. It deliberately does not print report fields or create a public artifact.
- The exact contents of the private production report have not yet been reviewed in this runbook; a successful retrieval job is not the same as reviewing the report findings.
- These workflow results do not, by themselves, prove that the Hostinger production application is running the same commit as GitHub `main`.

References:
- PR #173: https://github.com/mattari20/estudent-listing/pull/173
- Successful private retrieval run: https://github.com/mattari20/estudent-listing/actions/runs/38857645706
- Post-merge CI run group: https://github.com/mattari20/estudent-listing/actions

## 2. Non-negotiable safety rules

1. Treat diagnostic output as evidence about the database connected to the endpoint at the time of execution, not as proof of external-source truth.
2. Keep record-level diagnostics private. Do not paste private report payloads into public issues, PR comments, workflow logs, public artifacts, or this repository.
3. Never run a seed, migration, import, promotion, bulk approval, status change, or publication action merely because a report says it is needed.
4. Before any approved production write, require a verified backup, an exact change list, an explicit approval, a rollback plan, and post-change read-only verification.
5. Preserve original source assertions, provenance, identity decisions, conflicts, and history. Do not infer that similarly named institutions are duplicates without review.
6. A green CI or diagnostics retrieval workflow proves workflow success, not data correctness, deployment identity, or permission to modify production.
7. Do not expose tokens, secrets, full raw import payloads, private evidence, or unnecessary personal data in reports or discussions.

## 3. Read-only review sequence

Review the protected report in the private reports repository and record only the minimum necessary findings in authorized private notes:

1. **Aggregate report** — check database connectivity, required tables, canonical counts, relationship gaps, geography integrity, duplicate-name candidate groups, import pipeline status counts, and source/evidence availability.
2. **Record-level audit** — identify the exact institution, campus, program, batch, and row IDs requiring review. Payloads are intentionally excluded from the audit; retrieve original row details only through the authorized review interface when necessary.
3. **Geography preflight** — review every proposed create/update/no-change action, duplicate identity, current-vs-expected field difference, and parent relationship.
4. **Deployment identity** — separately verify the production deployment revision through an available, trusted deployment record or hosting control panel. Do not infer it from the GitHub workflow SHA.
5. **Evidence quality** — identify the authoritative source and applicable date for each material correction; mark unresolved or unsupported claims as pending rather than filling gaps with guesses.

## 4. Geography seed risk that must be addressed before execution

The current `PakistanGeographySeedService::seedTopLevel()` uses `updateOrCreate` for the Pakistan country and seven first-level administrative entries. It can update existing records and set their status to active; it is not a create-only operation.

The current preflight is read-only and correctly flags differences and duplicate identities, but its report is a point-in-time preview. The database may change between preflight and any later operation.

Therefore:

- Do not execute `institutes:seed-pakistan-geography` against production based only on a zero-conflict preflight.
- Review the exact existing-vs-expected field diff and duplicate checks immediately before any proposed write.
- Require a verified backup and explicit approval for any operation that changes existing values or reactivates a record.
- Prefer a narrowly scoped, reviewed change list over a broad seed operation when existing records require correction.
- After an approved change, rerun the read-only aggregate report, record-level audit, and geography integrity checks; compare before/after counts and identities.

This section documents a risk; it does not authorize or implement a code change.

## 5. Campus and institution remediation

For each active institution without an active campus, and each active campus with missing or inactive geography:

1. Confirm canonical institution identity and aliases from reliable sources.
2. Confirm each campus is a distinct location and not simply a duplicate institution record.
3. Record source URL/document, retrieval/check date, relevant scope, and uncertainty.
4. Match the campus to an existing geography record only when the match is supported; otherwise leave the relationship unresolved for review.
5. Do not create a campus from a missing-data finding alone.
6. Keep proposed changes in a reviewable list with current value, proposed value, evidence, rationale, approver, and rollback notes.

## 6. Import candidate and rejection review

- Candidate rows: review validation output and source evidence; do not bulk-approve.
- Rejected rows: inspect the recorded reason and original source only when needed; do not assume all rejections are errors.
- Institution identity: preserve the existing requirement for an explicit approved-new or approved-match decision.
- Geography rows: confirm parent identity, type, slug, and scope before promotion.
- Conflicts: resolve with an explicit reason and supporting evidence; do not silently overwrite canonical data.

## 7. Programs, offerings, and admissions

Review in dependency order:

1. Confirm reusable Program identity and classification.
2. Confirm an institution or campus actually offers the program.
3. Record a Program Offering separately from the Program.
4. Add intake/admission details only with applicable cycle/session dates and supporting sources.
5. Preserve unknown values as unknown. Do not infer offerings or current admissions from institution-level marketing text without checking its scope and date.

## 8. Acceptance criteria for a remediation batch

A batch is ready for review when all of the following are available:

- A private, timestamped before-report and record-level audit.
- An exact list of affected IDs and fields, without unnecessary sensitive payloads.
- A source/evidence reference and rationale for each proposed factual change.
- A clear separation of verified findings, hypotheses, and unresolved questions.
- An explicit human approval record for material production changes.
- A verified backup and documented rollback steps before any production write.
- A post-change read-only audit showing what changed and whether new integrity gaps appeared.

## 9. Current blockers and next batch

1. Review the JSON report in `mattari20/institutes-private-reports` using authorized private access; the successful workflow log intentionally suppresses its contents.
2. Verify the exact production deployment revision independently.
3. Produce a prioritized private repair list from the report, grouped by geography, campus/location, institution identity, programs/offerings, and import exceptions.
4. Address the seed update/reactivation risk through a separately reviewed implementation proposal and tests before considering execution.
5. Obtain approval and backup evidence before any production write.

Until those gates are satisfied, keep all data work read-only.
