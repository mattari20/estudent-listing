# Phase 3 — Institution Source Refresh Approval Gate

## Current finding

The existing `OfficialSourceCanonicalRefreshService` checks evidence attributes supplied in an array and uses expected-current-value checks, transactions, and `OfficialSourceRefreshEvent` records. Those checks do not establish that a human or authorized review process approved the exact field-level proposal.

The source-change lifecycle uses `SourceChangeObservation` states such as `detected` and `actionable`; these indicate detection/processing, not approval. `SourceChangeAssertionLink` records candidate impacts. `ImportPromotionDecision` is tied to an `ImportRow` promotion decision, not a field-level update against an existing canonical institution. Reusing these as approval without binding the reviewed field, proposed value, expected current value, source revision, and canonical identity would not securely prove approval.

## Fail-closed behavior

This branch adds a conservative Institution allowlist containing only `description`, because descriptive prose does not itself redefine canonical identity. It excludes `name`, `official_name`, `short_name`, `slug`, `institution_type`, `status`, `verified_at`, primary keys, and relationships.

Even for `description`, the service explicitly blocks Institution writes until a persisted field-level approval integration can be verified. Caller-supplied booleans are not accepted as a substitute. Existing Campus, Program, and ProgramOffering refresh behavior is unchanged.

## Required integration before Institution writes can be enabled

A persisted, authorized review decision must be verifiably bound to:
- canonical Institution ID and exact field;
- proposed value and expected-current value captured at review;
- approved source ID, observation, retrieved snapshot/content fingerprint, evidence URL, excerpt, and observation time;
- explicit approved decision, actor, and decision timestamp.

The service must validate that decision transactionally, re-check the latest source/evidence and canonical value, then atomically write the field and audit event. Until that integration exists, Institution refresh remains blocked.

## Safety

No production database writes, canonical imports/promotions/publication, deployment, cron changes, migration-ledger repair, or pilot activity are performed. No schema migration is added because the gap is the missing approval binding, not the audit-event table.
