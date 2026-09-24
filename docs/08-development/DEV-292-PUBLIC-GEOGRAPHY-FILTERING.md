# DEV-292 — Public Geography Filtering Safety

## Purpose

Ensure public institute search geography filters only traverse active canonical campuses and active canonical geography records.

## Scope

Province, district, and city filters require:

- active campus
- active campus geography
- active parent geography at each required hierarchy level
- the expected geography type and name

## Safety

Inactive campus or geography records cannot make an institution appear in a public geography-filtered result.

This batch is read-only and does not repair or mutate canonical records.

## Testing

Regression coverage verifies that active canonical geography remains searchable while inactive campus and inactive geography paths are excluded.
