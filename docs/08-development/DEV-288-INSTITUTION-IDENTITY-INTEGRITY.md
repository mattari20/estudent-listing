# DEV-288 — Institution Identity Integrity

## Purpose

Establish a read-only integrity boundary for the canonical identity of active institutions.

## Findings

The service reports:

- missing canonical institution name
- missing slug
- invalid slug format
- duplicate slug among active institutions

Inactive institutions are outside the public integrity finding set.

## Safety

This service is diagnostic only. It does not rename institutions, regenerate slugs, merge records, activate records, deactivate records, or delete records.

Duplicate identity findings require explicit operator review before any future correction workflow.

## Testing

Focused feature tests cover:

- a valid active institution
- inactive institution exclusion
- missing identity fields
- invalid slug detection
- duplicate active slug detection
- non-mutation of canonical records
