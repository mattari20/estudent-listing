# DEV-297 — Geography Integrity Consolidation

## Status

Implemented on branch `dev-297-geography-integrity-consolidation`.

## Purpose

Bring the geography integrity boundary onto the current `main` architecture as one maintained, read-only capability.

## Included

- Institution-level active-campus coverage detection.
- Campus-level canonical geography validation.
- Active ancestor validation through the complete geography chain.
- Missing-parent and cycle detection.
- Safe root-to-leaf geography context for public campus cards.
- Geography-aware search matching using active campus/geography data.
- Operator-facing read-only integrity review.
- Regression coverage for valid, missing, inactive, cyclic and inaccessible states.

## Safety

- No automatic reassignment, activation, deletion or merge is performed.
- Integrity findings never change canonical data.
- Public search/profile output remains limited to active, complete geography.
- Operator review is read-only and protected by the existing operator middleware.
