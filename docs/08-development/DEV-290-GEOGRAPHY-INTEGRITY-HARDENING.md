# DEV-290 — Geography Integrity Hardening

## Purpose

Strengthen the canonical geography integrity boundary on the current main branch.

## Findings

Active geography records are checked for:

- missing name
- missing slug
- invalid slug format
- country with a parent
- non-country without a parent
- inactive or missing parent
- self-parent relationship
- duplicate slug within the same parent

Inactive geography records are excluded from the finding set.

## Compatibility

The existing validate() aggregate contract remains available. It now delegates to the richer read-only inspection boundary instead of maintaining separate integrity logic.

## Collection Safety

inspectMany() can detect duplicate parent/slug identities in an in-memory collection without requiring database writes.

## Safety Boundary

The service only reports integrity findings. It never renames, reassigns, activates, deactivates, merges, or deletes canonical geography.

## Testing

Regression coverage includes valid geography, inactive exclusion, parent failures, self-parent detection, identity validation, duplicate detection, aggregate validation, and non-mutation.
