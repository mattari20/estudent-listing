# DEV-289 — Campus & Institution Relationship Integrity

## Purpose

Establish a read-only integrity boundary for active campus records and their canonical institution/geography relationships.

## Findings

The service reports:

- missing institution
- institution not active
- missing campus name
- missing campus slug
- invalid campus slug format
- missing geography
- geography not active
- duplicate campus slug within an institution

Inactive campuses are outside the integrity finding set.

## Collection Safety

Collection inspection detects duplicate institution/slug identities without requiring database writes. This allows diagnostic testing and future import-review workflows to identify collisions before canonical promotion.

## Safety

This service is diagnostic only. It never reassigns campuses, changes institution or geography relationships, renames records, regenerates slugs, activates records, deactivates records, or deletes records.

Any correction or reassignment remains an explicit operator workflow.

## Testing

Regression coverage includes:

- valid active campus
- inactive campus exclusion
- missing institution
- inactive institution
- missing geography
- inactive geography
- invalid campus identity fields
- collection-level duplicate identity detection
- non-mutation of the inspected collection
