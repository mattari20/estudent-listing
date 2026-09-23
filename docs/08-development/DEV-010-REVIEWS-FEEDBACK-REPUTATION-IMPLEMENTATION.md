# DEV-010 — Reviews, Feedback & Reputation Implementation

## Status
Implemented on the development branch for review.

## Scope
Adds the community-review foundation while keeping user opinion separate from canonical institutional facts.

## Governance Rules
- A review is a community signal, not a canonical institutional fact.
- Reviews can target an institution, campus, or program.
- Reviewer type may distinguish student, teacher, alumni, or other governed categories.
- Review status supports moderation lifecycle beginning with pending and published states.
- Verification state is separate from moderation status.
- Ratings may contain an overall rating and multiple named dimensions.
- No review or a low review count must not be interpreted as positive or negative evidence.
- Institution responses are separate from the original review and do not silently modify it.
- Fraud, manipulation, spam, duplicates, privacy violations, and abusive content require moderation controls in later workflow layers.
- Aggregated reputation signals must preserve sample size, time period, reviewer context, and verification coverage.
- Review aggregates must not silently become institutional facts.
- AI may assist moderation, topic extraction, duplicate detection, and anomaly detection, but must not fabricate, suppress, or rewrite independent user opinions.
- Search, SEO, ranking, and recommendation layers must clearly label community-derived signals.

## Deferred
Public review submission workflow, authentication/identity verification, moderation queues, anti-manipulation scoring, abuse reporting, appeals, reputation aggregation formulas, review-based ranking weights, and dedicated review APIs are deferred.
