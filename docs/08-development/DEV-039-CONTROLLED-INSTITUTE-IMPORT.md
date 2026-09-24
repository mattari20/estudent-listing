# DEV-039 — Controlled Institute Import

Status: Implemented

The initial controlled import accepts institution CSV data and creates a source plus validation batch. Supported columns are name, official_name, short_name, slug, institution_type and status.

Rows become candidates only after structural validation. Duplicate slugs within a batch are rejected. Existing canonical institutions are not silently overwritten.

Future work must add identity matching, evidence creation, review/approval and canonical promotion before importing external datasets at scale.
