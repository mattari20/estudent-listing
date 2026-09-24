# DEV-291 — Geography Gap Detection

## Purpose

Provide a read-only detection boundary for active campuses whose canonical geography linkage is missing or points to inactive geography.

## Findings

- missing geography on active campus
- inactive geography on active campus
- active campus with active geography is not a finding
- inactive campuses are excluded

## Safety

Detection never changes canonical data. Operator review and explicit correction remain separate workflows.

## Testing

Coverage includes missing geography, inactive geography, inactive campus exclusion, active geography safety, and finding identifiers.
