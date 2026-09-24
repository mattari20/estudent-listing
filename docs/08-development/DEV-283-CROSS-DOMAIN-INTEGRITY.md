# DEV-283 — Cross-Domain Integrity Service

## Status

Implemented on branch `dev-283-cross-domain-integrity`.

## Purpose

Provide one read-only integrity boundary that combines institution-level and campus-level geography checks.

## Composition

- Institution-level coverage rules come from `InstitutionGeographyIntegrityService`.
- Campus-level canonical geography rules come from `CampusGeographyIntegrityService`.
- The combined service does not duplicate or replace those bounded rules.

## Safety

The service only reports findings. It does not repair geography, reassign campuses, change statuses, or modify public records.
