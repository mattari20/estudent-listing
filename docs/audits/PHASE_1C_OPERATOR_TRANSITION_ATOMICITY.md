# Phase 1C-21 — Operator Transition Atomicity and State-Guard Hardening

Date: 2026-11-04  
Repository: `mattari20/estudent-listing`  
Verified source baseline: `8aa2ee2416b2729d05afd249961085b888b61910`  
Previous audit: `docs/audits/PHASE_1C_DISCOVERY_TO_REVIEW_PERSISTENCE_BOUNDARY_AUDIT.md`

## 1. Scope and status

This delivery addresses the Phase 1C-20 P1 operator-transition findings. It is repository and isolated-CI work only. The implementation preserves the separate identity decision, canonical promotion and public publication gates. It does not close Phase 1C or certify production readiness.

**Current implementation status:** code and regression coverage are prepared on the Phase 1C-21 implementation branch; exact final PR-head Test, Laravel CI/Pint, Project Controller and isolated MySQL evidence must be recorded after the final PR head is fixed. Do not treat source recovery or local PHP syntax checks as full validation.

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
| Bulk approve/reject | Bulk controller had its own transaction, but institution rejection bypassed the shared identity service and retry handling was not uniform | Both paths use the transition service; row state and conflict eligibility are rechecked under lock |
| Institution promotion | Already transactional and conflict/evidence/approval guarded, but used a potentially stale route-bound row and unlocked decision/canonical record | Reloads and locks the import row and identity decision in the transaction; approved-match canonical institution is locked |
| Enrichment promotion | Canonical writes and audit were separate controller-level operations | Enrichment write and audit are enclosed by a row-locked outer transaction; open conflicts block promotion |
| Geography promotion | Geography service transaction did not include controller audit | Outer row-locked transaction now includes canonical geography update and audit; open conflicts block promotion |
| Institution publication | Canonical status update and audit were separate; concurrent calls could duplicate events | Row and institution are locked; evidence, approved identity decision and open-conflict guards are checked; status and audit commit together; already-active replay creates no duplicate event |
| Promotion reversal | Already transaction-bound and row-locked | Existing boundary retained; failure injection added for canonical status update rollback |

The changes do not merge identity approval, canonical promotion and publication into one action. Institution quick approval remains disabled. Identity conflict resolution remains separate from identity approval. Promotion and publication continue to require explicit prior decisions and existing evidence/conflict gates.

## 4. Implementation changes

- Added `OperatorTransitionService` to own non-institution rejection, promotion-decision persistence, institution publication, enrichment promotion and geography promotion transaction boundaries.
- Extended `IdentityResolutionDecisionService` with a row-locked institution rejection transition. Institution rejection now uses the same deterministic decision identity and audit ledger as other identity decisions.
- Added the candidate-state guard inside the identity decision transaction. Identical decision replay can return its existing immutable record after a state transition; a new decision cannot be appended to a non-candidate row.
- Updated `InstituteImportPromotionController` to use the transactional transition service for quick approval, rejection, enrichment decisions, enrichment promotion, geography promotion and publication.
- Updated `InstituteBulkPromotionController` to route bulk approval/rejection through the same transaction owner.
- Updated `InstituteImportPromoter` to reload/lock the row and decision, and lock an approved-match canonical institution before updating the import row.
- Added conflict guards to enrichment/geography promotion and retained the existing institution promotion and publication conflict guards.
- No migration is required: existing nullable unique `identity_resolution_decisions.decision_identity_key` is reused. Historical decision rows are not backfilled, deleted or rewritten except that an exact legacy replay can attach its deterministic key using the existing compatibility path.

## 5. Failure-injection and regression coverage

`tests/Feature/OperatorTransitionAtomicityTest.php` adds coverage for:

1. identity-decision insertion failure and retry;
2. candidate import-row state update failure during rejection, rolling back decision and audit;
3. canonical institution status update failure during reversal, rolling back reversal state and event;
4. identity-decision audit-event insertion failure, rolling back the decision;
5. publication state update failure, rolling back and permitting retry;
6. publication audit-event insertion failure, rolling back canonical publication and permitting retry.

Additional feature coverage checks identical institution rejection replay, competing rejection rejection, non-institution rejection replay, quick-approval decision/audit replay, publication replay without duplicate publication event, non-candidate identity decision guard, and publication blocking while an identity conflict remains open.

`tests/Support/verify-operator-transition-multiprocess.php` is an isolated-MySQL independent-process proof. It covers:

- identical rejection requests converging to one identity decision and one audit event;
- competing rejection and identity-decision requests serializing on the row lock, with the rejected row remaining non-promotable;
- concurrent candidate-state evaluation converging on one idempotent decision;
- concurrent publication requests converging on active canonical status and one publication event;
- an audit insertion failure rolling back the first rejection while a waiting independent process retries and commits exactly one decision/event.

The isolated MySQL workflow is extended to execute the feature suite and the independent-process script against MySQL 8.4. SQLite feature tests are not represented as the MySQL concurrency proof.

## 6. Validation evidence

Local checks completed on the recovered source workspace:

- PHP syntax lint passed for the changed PHP service, controller, feature test and multiprocess script files under PHP 8.4.24;
- YAML parsing passed for the edited Laravel CI and isolated MySQL workflow files.

These are not the final acceptance checks. The following exact final PR-head evidence must be appended after all documentation changes are committed:

| Workflow | Run / job | Required evidence |
|---|---|---|
| Test | pending | Full suite test count and assertions |
| Laravel CI | pending | Full suite count plus Pint format and `--test` result |
| Institutes Directory Project Controller | pending | Job result and internal inventory result reported separately |
| Isolated MySQL migration/recovery | pending | New feature suite count, all six failure boundaries, independent-process logs |

Do not merge unless all required checks pass on the exact final PR head, repository protections permit merging, and no required workflow is queued or skipped.

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

After Phase 1C-21 is merged and its exact evidence is reconciled, the next smallest P1 package is chronological source-snapshot predecessor selection and ordering under concurrency. Do not start Phase 1C-22 automatically.
