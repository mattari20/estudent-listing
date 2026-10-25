# Phase 2 Runtime Verification Checklist (2026-10-25)

Use this checklist for a controlled read-only verification. Do not treat a checked box as complete without attaching the command output, workflow run, or protected admin report that proves it.

## A. GitHub code and CI
- [ ] Record main commit SHA being verified.
- [ ] Record CI/Test workflow run URLs and final conclusion for that exact SHA.
- [ ] Confirm no pending/failed required checks.
- [ ] Confirm no production migrations or canonical-data writes were bundled into code deployment.

## B. Hostinger deployed revision
- [ ] Capture the deployed application revision/commit from the deployment panel or a protected diagnostic endpoint.
- [ ] Compare it with the intended main SHA.
- [ ] Record verification time and endpoint/panel evidence.
- [ ] If revision cannot be observed, mark NOT VERIFIED.

## C. Scheduler and deterministic operation
- [ ] Confirm Hostinger cron invokes Laravel's scheduler at the documented cadence.
- [ ] Capture evidence that institutes:bootstrap-agent and institutes:monitor-changes ran successfully.
- [ ] Confirm the scheduled profile uses use_ai: false.
- [ ] Confirm execution succeeds without INSTITUTE_AI_API_KEY and without paid external AI calls.
- [ ] Record empty/no-change runs explicitly; no output is not proof of a successful run.

## D. Private review package
- [ ] Retrieve CSV and JSON manifest through the protected admin/review path or documented protected command.
- [ ] Confirm the package is not publicly accessible.
- [ ] Reconcile manifest snapshot/evidence/batch IDs and candidate count with the source batch.
- [ ] Confirm package reuse for unchanged snapshots and a recorded no-change outcome.
- [ ] Confirm package contents do not include secrets or source URLs with embedded credentials.

## E. Fresh read-only live audits
- [ ] Run candidate audit and preserve its output.
- [ ] Run live integrity/offering audit and preserve its output.
- [ ] Reconcile failure events to unique import-row IDs.
- [ ] Record report timestamp, deployed SHA, command/run IDs, counts, and blockers.
- [ ] Keep data mutation disabled until the proposed row-level plan has duplicate checks and explicit authorization.

## Final status vocabulary
- PASS: evidence captured and acceptance criterion satisfied.
- BLOCKED: a concrete failure or missing prerequisite prevents acceptance.
- NOT VERIFIED: evidence is unavailable or has not been collected.
- NOT APPLICABLE: explain why.

Do not use “passed” for a test that was not run, a runtime check inferred from source code, or a report whose timestamp/revision cannot be established.
