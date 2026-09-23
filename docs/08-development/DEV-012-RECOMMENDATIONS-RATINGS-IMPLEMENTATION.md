# DEV-012 — Student/Teacher Recommendations & Ratings Implementation

## Status
Implemented

## Scope
Implements community recommendation/rating records as a separate, non-canonical signal.

## Governance
- A recommendation or rating is a community signal, not an institutional fact.
- Contributor type can distinguish student, teacher, alumni and other permitted categories.
- Recommendation states are Recommend, Do not recommend, Neutral, or Prefer not to say.
- Ratings are optional and remain separate from recommendation state.
- Experience period and target context are preserved.
- Verification describes provenance/verification state; it does not convert an opinion into fact.
- Moderation/publication status is separate from verification.
- Institution/campus/program targets are supported.
- Aggregates must preserve sample size, contributor context, period and verification coverage.
- No ranking/recommendation formula, weight, minimum sample or time-decay rule is introduced here.
- AI may assist moderation, topic extraction and anomaly detection, but must not fabricate, suppress or rewrite independent opinions.

## Deferred
- Aggregate calculations
- Anti-manipulation scoring
- Ranking integration
- Personalized recommendations
- User identity/authentication design
- Public submission workflow
