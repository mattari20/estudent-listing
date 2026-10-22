# Project Constitution

## Product and architecture commitments
1. The directory lives under `/institutes/` on eStudent.pk.
2. Do not rebuild this directory as a WordPress plugin or make WordPress a dependency for its core operation.
3. Preserve a modular application and data model that can be expanded over many years.
4. Prefer automated CI, diagnostics, deployment checks and repeatable workflows. Avoid asking the owner to run manual shell commands when a safe connected automation can do the work.
5. Support the broad education directory goal: eligible institutions from lower-level schooling through higher education, with institution-specific capabilities rather than assuming every institution has university-style data.

## Data integrity and publication
1. An imported row is not automatically a canonical record and a canonical record is not automatically public.
2. Candidate, invalid, rejected, duplicate and conflicted rows must be distinguished.
3. Do not bulk-delete unapproved rows merely because they are unapproved or incomplete. They may contain useful review history or map to legitimate canonical records.
4. Do not auto-publish an AI-discovered or user-submitted record. Keep it in a review queue until the configured evidence, identity, conflict and approval requirements pass.
5. Never fabricate official source URLs, excerpts, ratings, reviews, accreditation, ranking or other evidence.
6. Resolve duplicates through explicit identity review and dependency checks; preserve an audit trail.
7. Production writes and destructive cleanup require a documented dry run, explicit scope, dependency review and verified recovery procedure. Dummy/test data may be re-imported during testing, but that does not justify uncontrolled destructive operations.
8. Diagnostics must clearly distinguish read-only reports from commands that mutate data.

## Trust and user-generated content
- Store provenance, source date/freshness, confidence where appropriate, approval decisions and correction history.
- Treat visitor comments and ratings as user-generated claims, not verified institutional facts.
- Design anti-spam, abuse reporting, moderation, privacy and fraud controls before public comments or rating submissions are enabled.
- Keep institution, campus/sub-institution and program feedback scopes distinct.
- Do not finalize a universal ranking formula or weights without a documented methodology and review.

## Delivery discipline
- Work from requirements and existing code; do not create a parallel system for an existing capability.
- Use pull requests and automated checks.
- Record what is implemented, tested and live-verified separately.
- Update the master index, gap register and roadmap when requirements or status change.
- Never claim live verification when only a CI test or historical report exists.
