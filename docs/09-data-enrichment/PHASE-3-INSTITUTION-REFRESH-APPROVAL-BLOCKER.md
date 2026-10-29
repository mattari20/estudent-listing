# Phase 3 — Institution Source Refresh Approval Gate

## Original blocker and Phase 3B implementation

PR #916 established the fail-closed gate because the existing source-change lifecycle records detection/actionability, not human approval of a specific canonical field change. Import-promotion decisions remain tied to import rows and are not reused for existing Institution updates.

Phase 3B introduces a dedicated `institution_field_approvals` record and authenticated review lifecycle. It is restricted to the Institution `description` field. The record binds the canonical Institution, expected and proposed values, source, source observation, captured snapshot, evidence record, evidence URL/excerpt/fingerprint and observation timestamps, creator, reviewer, decision timestamp, and application outcome.

## Authorization and evidence rules

- An authenticated existing institute operator may create a pending proposal.
- Only an authenticated existing admin/master-admin (`User::isAdmin()`) may approve or reject it.
- The creator cannot review their own proposal. Decisions are one-time and require a pending proposal.
- Approval requires an active source matching an existing configured official-source profile, a captured snapshot, an active evidence record, matching source/snapshot/observation relationships, and an evidence fingerprint matching the snapshot.
- Detection/actionability states never imply approval. Caller-supplied booleans and user IDs are not used as approval proof.
- The generic refresh method continues to reject Institution writes. The existing refresh service exposes a separate approved-proposal application method that locks the proposal and Institution, revalidates the proposal hash and evidence, checks the expected current description, records the refresh event atomically, and prevents replay.
- A stale or changed proposal is marked terminal and cannot be reused. An unchanged proposal records a `no_change` outcome without writing the canonical Institution or creating a duplicate refresh event.
- Other Institution fields remain blocked; Campus, Program, and ProgramOffering behavior is unchanged.

## Safety and validation

This change adds an application migration only; it does not run migrations against production. It does not perform production writes, imports, promotions, publication, deployment, cron changes, migration-ledger repairs, paid AI calls, or the 25-institution pilot.

CI must pass the focused approval/refresh tests and the existing Test and Laravel CI workflows before Phase 3B can be considered complete. Any failure or missing source profile/evidence must keep the proposal from being applied.
