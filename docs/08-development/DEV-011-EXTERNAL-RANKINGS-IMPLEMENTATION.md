# DEV-011 — External Rankings & External Signals Implementation

## Status
Implemented

## Scope
DEV-011 implements storage and domain relationships for third-party ranking and external-signal records.

## Governance
- An external ranking/signal is an external claim, not an eStudent institutional fact.
- Publisher, ranking name, edition, year, scope, methodology context, and source provenance are preserved where available.
- Records may target an institution or a program.
- Rank, score, percentile, band/label, and signal type remain separate representations.
- Historical and current records may coexist.
- Missing external ranking data does not imply poor performance or absence from the external publisher.
- Multiple publishers and conflicting signals are preserved rather than collapsed into one value.
- This implementation does not calculate an eStudent ranking, score, weight, or winner.
- AI-assisted extraction/classification may create candidates for review, but must not invent or silently promote external results.

## Data Model
external_rankings stores institution/program scope, publisher and ranking identity, edition/year, methodology/scope context, rank/label/score/percentile, signal type/status, effective dates, source URL and notes.

## Relationships
- Institution has many external ranking/signal records.
- Program has many external ranking/signal records.
- A record may be institution-scoped or program-scoped.

## Testing
Migration/domain tests cover institution-scoped and program-scoped records and preserve nullable scope.

## Deferred
- Internal ranking methodology
- Ranking aggregation
- Search weighting
- Recommendation weighting
- Publisher integrations
- Automated crawling
- AI provider/model selection
