# Phase 2 / Step 3: final audit readiness and closure runbook

**Status: BLOCKED — live audit and record-level review are not complete.**  
**Safety mode: read-only; no production writes, migrations, promotions, approvals, or deletions are authorized by this runbook.**

## Verified repository state

- The read-only closure gate is implemented by `institutes:step3-closure-gate`. It only evaluates whether a report is eligible for human closure review; it does not close the issue or modify application data.
- The Hostinger workflow is `.github/workflows/hostinger-readonly-offering-gap-audit.yml`.
- The workflow intentionally reports database grants verification as skipped because of the shared-hosting limitation. A successful workflow alone must not be represented as proof that database privileges are SELECT-only.
- Hostinger support confirmed that the current shared-hosting database supports one database user in hPanel. PR #757 was closed without merge because it relied on unverified alternative DB secrets and did not resolve this limitation.
- The current evidence register is `docs/step3-offering-gap-evidence-register.csv`; its expected register size is 36 rows. Those rows must be reconciled against a fresh live report and current official evidence.
- The historical GCU discovery baseline was 112 candidate rows. It is a historical batch count, not proof that the current batch has 112 valid or unresolved candidates.
- The historical 76 promotion-failure events (31 identity/duplicate collisions and 45 validation/missing-field events) are event counts, not necessarily 76 unique unresolved records.

## Final audit sequence

1. **Verify SSH trust and access configuration**
   - Confirm `HOSTINGER_SSH_FINGERPRINT` is configured from a trusted Hostinger-provided fingerprint, not inferred solely from an unauthenticated scan.
   - Confirm the SSH secrets are present in the intended GitHub Actions environment without printing or exposing secret values.
   - If a prerequisite is missing or the host key does not match, stop. Do not bypass strict host-key verification.

2. **Run the live read-only audit**
   - Dispatch `Hostinger Read-only Offering Gap Audit` with the approved project path.
   - Confirm the preflight and reconciliation commands completed and that both reports are valid JSON.
   - Confirm exactly 36 register rows are represented and the report timestamp is fresh.
   - Retain the exact workflow run URL, commit SHA, timestamps, report summaries, and checksums.
   - A successful run does not establish SELECT-only grants. Record that limitation explicitly unless Hostinger provides an independently verifiable supported grants inspection.

3. **Review every offering-gap record**
   - Match each row by stable program/institution/campus identity, not display name alone.
   - Verify current programme availability and campus/offerings against official sources.
   - Record reviewer status, concise rationale, evidence URL, duplicate/identity assessment, and proposed disposition.
   - Do not apply canonical repairs as part of the audit.

4. **Review the GCU candidate batch**
   - Identify the exact current batch ID and export its candidate rows with stable IDs and provenance.
   - Give every candidate a reviewed disposition and official source evidence.
   - Preserve historical batches and events. Do not bulk reject, promote, or delete candidates.

5. **Reconcile historical promotion failures**
   - Group events by unique import-row identity.
   - Separate historical event counts from current unresolved rows.
   - Document each current disposition and any missing evidence.

6. **Validate the closure package**
   - Include the live audit run URL, commit SHA, timestamps, exact report files, SHA-256 checksums, 36-row review register, GCU disposition export and manifest, promotion-failure reconciliation, and unresolved blocker list.
   - Run artifact validators and the read-only closure gate against the actual fresh report and completed review register.
   - The gate must return `ELIGIBLE_FOR_HUMAN_CLOSURE_REVIEW` with zero errors before a human considers closure. This is eligibility only, not automatic closure.

## Explicit closure blockers

- The live Hostinger audit has not yet been run and its current row-level output has not been reviewed in this repository session.
- Trusted SSH fingerprint configuration and the actual workflow run must be confirmed.
- Actual database grants are not independently verified; do not describe them as SELECT-only.
- The 36 offering-gap rows still require fresh evidence review and record-level dispositions.
- The exact current GCU candidate batch must be exported and every row reviewed.
- Historical promotion-failure events need reconciliation to unique rows and current dispositions.
- Final checksums and the closure gate must be generated from the actual artifacts, not hand-authored summaries.

## Completion rule

Do not close Step 3 or issue #585 while any blocker above remains unresolved. If the hosting platform cannot provide a supported independent grants check, document that as an explicit risk/limitation for human sign-off; never imply it passed. Any later production data repair requires a separate record-level plan, duplicate checks, and explicit authorization.