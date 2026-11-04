# Phase 1C-21 — Operator Transition Atomicity and State-Guard Hardening

Date: 2026-11-04  
Repository: `mattari20/estudent-listing`  
Verified source baseline: `8aa2ee2416b2729d05afd249961085b888b61910`  
Previous audit: `docs/audits/PHASE_1C_DISCOVERY_TO_REVIEW_PERSISTENCE_BOUNDARY_AUDIT.md`

## 1. Scope and status

This delivery addresses the Phase 1C-20 P1 operator-transition findings. It is repository and isolated-CI work only. The implementation preserves the separate identity decision, canonical promotion and public publication gates. It does not close Phase 1C or certify production readiness.

**Final status:** PR #1006 merged successfully on 2026-11-04. The final documentation-inclusive PR head was `514c87b4539821dbd31b9d01a932a39080749f2a`; merge commit and verified main baseline are `fc54741ea246fe1c284ba397117b90ab3d87ca55`. The four exact-head validation workflows passed before merge. Post-merge repository workflow results and the two automatically triggered production-facing workflow failures are reconciled in Section 10.

## 2. Execution environment recovery

### 2.1 Previous checkout failure

The earlier local checkout attempt failed with:

```text
fatal: unable to access 'https://github.com/mattari20/estudent-listing.git/':
Could not resolve host: github.com
```

The failure was reproduced in the ChatGPT local container:

- `getent ahosts github.com` returned no address;
- `curl -I https://github.com` returned `curl: (6) Could not resolve host: github.com`;
- `git ls-remote https://github.com/mattari20/estudent-listing.git HEAD` returned the same DNS resolution error.

This establishes a DNS resolution failure in the local container. It is not evidence of failed GitHub authentication or a GitHub-hosted runner checkout failure.

### 2.2 Project Controller evidence

The latest Project Controller run on the expected main SHA was run `41771644665`, job `126382465036`. The job completed successfully. Its actual checkout log shows:

- `actions/checkout@v4` used the masked runner `GITHUB_TOKEN`;
- SSH strict checking remained enabled;
- Git fetched the exact commit `8aa2ee2416b2729d05afd249961085b888b61910`;
- `git log -1 --format=%H` returned that exact SHA;
- the checkout step passed.

There was no failed Phase 1C-21A Project Controller run in the inspected recent Actions history. The failed operation was the local container's Git checkout, not the Project Controller checkout.

### 2.3 Approved repository transfer

A temporary read-only export workflow was created on branch `recovery/phase-1c-21a-source-bundle` at commit `4590899d288de680d60a460b3f0b23be57db4242`. Workflow run `41774288577`, job `126390899600`, completed successfully and:

1. checked out the exact expected commit with full Git history;
2. confirmed the working tree was clean;
3. ran `git fsck --full`;
4. created a Git bundle and ran `git bundle verify`;
5. uploaded the bundle with a verification manifest.

Artifact ID: `13296003161`. Artifact ZIP SHA-256:

`dc8f6be1d18dd73cf43233f27c9327483ec76e97b673b7d388c20491f271e1f5`

The archive was downloaded and unpacked locally. The bundle was verified again and fetched into `/tmp/estudent-listing`. The recovered checkout has:

- `HEAD = 8aa2ee2416b2729d05afd249961085b888b61910`;
- 1,350 tracked files;
- a clean working tree;
- complete Git history and 1,053 refs in the bundle.

The local container has PHP 8.4.24, while `composer.json` requires PHP `^8.5`; Composer, MySQL client/server and Docker are not installed. Therefore full Laravel/PHPUnit/MySQL validation is delegated to the repository's GitHub-hosted PHP 8.5 and MySQL 8.4 workflows. Local PHP lint and YAML parsing are syntax checks only, not substitutes for CI.

## 3. Confirmed operator-transition inventory

| Path | Prior boundary | Phase 1C-21 handling |
|---|---|---|
| Institution `reject` | Direct identity decision insert, row status/notes update and audit insert were separate; bypassed decision idempotency service | Routed through `IdentityResolutionDecisionService::rejectRow`; row lock, deterministic decision identity, row rejection and audit event share one transaction |
| Non-institution `reject` | Decision, row state and audit could partially persist | Routed through `OperatorTransitionService::rejectRow`; row lock, retry lookup, state update and audit share one transaction |
| Identity `decide` | No candidate-state guard in the controller/service transaction | `IdentityResolutionDecisionService::record` locks the row and permits new decisions only while it is a candidate; exact existing decision replay remains safe after a later state transition |
| `quickApprove` | Promotion decision and audit were separate writes; candidate check used the route-bound model | Routed through `recordPromotionDecision`, which locks/reloads the row and writes the decision/audit transactionally; identical actor/decision/reason replay reuses the decision |
| `enrichmentDecision` | Decision and audit were separate writes; candidate check was outside a lock | Uses the same locked transactional decision path |
| Bulk approve/reject | Bulk controller had its own transaction, but institution rejection bypassed the shared identity service and retry handling was not uniform | Both paths use the transition service; row state and conflict eligibility are rechecked under lock. Bulk approve-and-promote-new also records its identity decision through the shared idempotent service |
| Institution promotion | Already transactional and conflict/evidence/approval guarded, but used a potentially stale route-bound row and unlocked decision/canonical record; callers could select an older approval while a later rejection existed | Reloads and locks the import row and latest identity decision in the transaction; the supplied decision must still be latest; approved-match canonical institution is locked |
| Enrichment promotion | Canonical writes and audit were separate controller-level operations | Enrichment write and audit are enclosed by a row-locked outer transaction; open conflicts block promotion |
| Geography promotion | Geography service transaction did not include controller audit | Outer row-locked transaction now includes canonical geography update and audit; open conflicts block promotion |
| Institution publication | Canonical status update and audit were separate; concurrent calls could duplicate events; query selected latest approved decision while ignoring a later rejection | Row and institution are locked; latest identity decision must be approved; evidence and open-conflict guards are checked; status and audit commit together; already-active replay creates no duplicate event |
| Promotion reversal | Already transaction-bound and row-locked | Existing boundary retained; failure injection added for canonical status update rollback |

The changes do not merge identity approval, canonical promotion and publication into one action. Institution quick approval remains disabled. Identity conflict resolution remains separate from identity approval. Promotion and publication continue to require explicit prior decisions and existing evidence/conflict gates.

## 4. Implementation changes

- Added `OperatorTransitionService` to own non-institution rejection, promotion-decision persistence, institution publication, enrichment promotion and geography promotion transaction boundaries.
- Extended `IdentityResolutionDecisionService` with a row-locked institution rejection transition. Institution rejection now uses the same deterministic decision identity and audit ledger as other identity decisions.
- Added the candidate-state guard inside the identity decision transaction. Identical decision replay can return its existing immutable record after a state transition; a new decision cannot be appended to a non-candidate row.
- Updated `InstituteImportPromotionController` to use the transactional transition service for quick approval, rejection, enrichment decisions, enrichment promotion, geography promotion and publication.
- Updated `InstituteBulkPromotionController` to route bulk approval/rejection through the same transaction owner; `BulkImportPromotionService::approveAndPromoteNew` now uses the shared idempotent identity-decision service instead of direct decision insertion.
- Updated `InstituteImportPromoter` to reload/lock the row and decision, and lock an approved-match canonical institution before updating the import row.
- Promotion and publication now require the latest decision to be an approval rather than selecting the latest record from an approval-only subset. Added conflict guards to enrichment/geography promotion and retained the institution promotion/publication conflict guards.
- No migration is required: existing nullable unique `identity_resolution_decisions.decision_identity_key` is reused. Historical decision rows are not backfilled, deleted or rewritten except that an exact legacy replay can attach its deterministic key using the existing compatibility path.

## 5. Failure-injection and regression coverage

`tests/Feature/OperatorTransitionAtomicityTest.php` adds coverage for:

1. identity-decision insertion failure and retry;
2. candidate import-row state update failure during rejection, rolling back decision and audit;
3. canonical institution status update failure during reversal, rolling back reversal state and event;
4. identity-decision audit-event insertion failure, rolling back the decision;
5. publication state update failure, rolling back and permitting retry;
6. publication audit-event insertion failure, rolling back canonical publication and permitting retry.

Additional feature coverage checks identical institution rejection replay, separate row-transition audit when a prior decision already exists, competing rejection rejection, non-institution rejection replay, quick-approval decision/audit replay after row-state transition, publication replay without duplicate publication event, non-candidate identity decision guard, latest-decision publication guard, and publication blocking while an identity conflict remains open.

`tests/Support/verify-operator-transition-multiprocess.php` is an isolated-MySQL independent-process proof. It covers:

- identical rejection requests converging to one identity decision and one audit event;
- competing rejection and identity-decision requests serializing on the row lock, with the rejected row remaining non-promotable;
- concurrent candidate-state evaluation converging on one idempotent decision;
- concurrent publication requests converging on active canonical status and one publication event;
- an audit insertion failure rolling back the first rejection while a waiting independent process retries and commits exactly one decision/event.

The isolated MySQL workflow is extended to execute the feature suite and the independent-process script against MySQL 8.4. SQLite feature tests are not represented as the MySQL concurrency proof.

## 6. Validation evidence

### Exact implementation code-head validation — PR #1006

Final documentation-inclusive PR head: `514c87b4539821dbd31b9d01a932a39080749f2a` (base `8aa2ee2416b2729d05afd249961085b888b61910`). PR #1006 merged at `fc54741ea246fe1c284ba397117b90ab3d87ca55`.

| Workflow | Run / job | Result |
|---|---|---|
| Test | #41776287316 / 126397309893 | PASS — 880 tests, 3,877 assertions |
| Laravel CI | #41776287333 / 126397310179 | PASS — 880 tests, 3,877 assertions; Pint formatting and `--test` passed for 66 files |
| Institutes Directory Project Controller | #41776287301 / 126397404490 | Job PASS; internal inventory REVIEW_REQUIRED, 2/5 tasks passed |
| Isolated MySQL migration/recovery | #41776287321 / 126397309903 | PASS — MySQL 8.4 migration/schema/ledger checks and idempotent second migration passed; all recovery steps passed |

Actual isolated-MySQL logs show:

- `OperatorTransitionAtomicityTest.php`: **15 tests, 55 assertions — PASS**, including all six requested failure-injection boundaries and retry/rollback checks.
- `verify-operator-transition-multiprocess.php`: **PASS**. Independent PHP processes converged on one identical rejection decision/audit event; competing rejection/decision and candidate-state checks serialized; concurrent publication produced one event; injected audit failure rolled back while a waiting retry committed exactly one decision and event.
- Existing MySQL migration, lease, snapshot/retrieval, discovery outcome, candidate batch, review-package, identity conflict, source event, monitoring alert and policy checkpoint recovery steps also passed.

The Project Controller job succeeded, but its internal read-only inventory is not a full project readiness pass. It remains REVIEW_REQUIRED at 2/5: Hostinger read-only diagnostics are blocked by historical run #41771644645; open-PR review and tracker-evidence reconciliation are also marked blocked while this PR is open. No Hostinger access or production operation was attempted.

The first code-head run set found one compatibility regression in the established decision-row ownership error message. The implementation was corrected to preserve that message while retaining the locked latest-decision check; the successful run set above is on the corrected implementation head, not the failed earlier attempt.

### Documentation-inclusive final PR-head validation

The final documentation-inclusive PR head `514c87b4539821dbd31b9d01a932a39080749f2a` passed all four required workflows:

| Workflow | Run / job | Result |
|---|---|---|
| Test | #41776912871 / 126399289675 | PASS — 880 tests, 3,877 assertions |
| Laravel CI | #41776912841 / 126399289035 | PASS — 880 tests, 3,877 assertions; Pint formatting and `--test` passed |
| Institutes Directory Project Controller | #41776912867 / 126399305857 | Job PASS; internal inventory REVIEW_REQUIRED, 2/5 |
| Isolated MySQL migration/recovery | #41776912864 / 126399289310 | PASS — operator transition suite 15 tests / 55 assertions and independent-process proof PASS |

PR #1006 was merged through GitHub's normal merge process. The resulting merge commit is `fc54741ea246fe1c284ba397117b90ab3d87ca55`, which was independently verified as the Phase 1C-22 starting `main` SHA.

## 7. Remaining risks and Phase 1C-20 findings

### P1

- **Chronological source snapshot comparison remains open.** Phase 1C-20 found that an older snapshot can be compared after a newer snapshot has been stored and select a future snapshot as its predecessor. This is outside Phase 1C-21's operator-transition scope and still requires a separate deterministic chronological-order fix plus delayed independent-process MySQL test.
- The Phase 1C-20 P1 operator rejection, candidate-state and publication atomicity findings are addressed by this implementation only after exact-head tests and MySQL concurrency validation pass.

### P2

- Review-package reuse still needs a current database row-set digest and explicit evidence ownership verification.
- Audit/event append-only semantics are not database-enforced; parent cascade deletion can remove history. Retention and archival policy remain undefined.
- Retrieval snapshot commit and retrieval-run completion remain separate; final-run-update failure injection and retry proof remain outstanding.
- Monitoring checkpoint/active identity registry foreign-key and direct-concurrency assumptions remain outstanding.
- Legacy nullable identity keys are intentionally not bulk backfilled; a complete non-destructive historical NULL-key inventory remains open.
- Filesystem and MySQL remain separate transaction domains; no distributed exactly-once guarantee is claimed.

## 8. Safety and non-claims

Repository and isolated CI only. No production DB query/write, production migration, deployment, Hostinger SSH/hPanel, cron/worker/schedule change, production Artisan command, source retrieval/discovery, staging, approval, rejection, import, promotion or publication was performed.

GCU candidate rows #348–353 and batches #19–23 remain protected and unchanged. AI extraction and the additional 20 HEC profiles remain disabled. No dummy production data was created. Production readiness remains NOT CERTIFIED. Phase 1C remains IN PROGRESS until all separately governed closeout gates are satisfied.

## 9. Next smallest safe remediation

Phase 1C-21 is merged and its evidence is reconciled. The next P1 package is Phase 1C-22 chronological source-snapshot predecessor selection and ordering under concurrency.


## 10. Merge and post-merge reconciliation — 2026-11-04

- PR #1006 is closed and merged; merge commit: `fc54741ea246fe1c284ba397117b90ab3d87ca55`.
- The merge commit equals the independently verified `main` SHA at the start of Phase 1C-22.
- Exact final-head checks remain the four runs in Section 6: Test #41776912871, Laravel CI #41776912841, Project Controller #41776912867, and isolated MySQL #41776912864.
- Eight push workflows ran on the merge commit:
  - Passed: Test #41778840338; Laravel CI #41778840292; Project Controller #41778840412; Command Orchestrator #41778840397; Public Site Monitor #41778840291; Build frontend assets #41778840304.
  - Failed: Hostinger Read-only Diagnostics #41778840327; Automated Live Data Integrity Audit #41778840448.
- Both failed jobs reached their production-facing diagnostic step and reported failure. The Hostinger diagnostics workflow skipped its subsequent public directory/API and guest-denial checks. No retry or production investigation was initiated as part of Phase 1C-22. The workflow failures are not treated as repository CI passes or production readiness evidence.
- The earlier statement that the PR was open/draft is superseded by this merge record. The Phase 1C-20 chronological snapshot P1 remains open until Phase 1C-22 implementation and validation.
- No production database, Hostinger/hPanel, deployment, cron/worker, production Artisan, retrieval, discovery, staging, approval, rejection, import, promotion or publication action was performed by the Phase 1C-22 work. The two automatic post-merge production-facing workflow failures are recorded transparently; their production-side effects are not inferred from the failure status alone.
