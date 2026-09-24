# DEV-279 — Campus–Geography Integrity Rules

## Status

Implemented on branch `dev-279-campus-geography-integrity`.

## Purpose

Define the canonical geography integrity rules that apply directly to active campuses.

## Rules

- Inactive campuses are not inspected by this public-integrity boundary.
- An active campus must reference an active canonical geography.
- Every parent geography in the referenced hierarchy must also be active.
- A root geography is valid without a parent.
- A missing parent or cyclic geography hierarchy is reported as an integrity finding.
- The service is read-only and never repairs, reassigns, activates, or deletes canonical records.

## Safety

A finding does not change public visibility or status. Correction requires an explicit operator workflow and must remain auditable.
