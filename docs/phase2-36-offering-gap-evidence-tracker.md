## Purpose
Provide a row-level review structure for the 36 active programmes reported without active offerings. This tracker does not claim the gaps have been rechecked against a fresh live artifact.

## Required columns per register row
`register_row_id`, `programme_id`, `programme_title`, `canonical_institution_id`, `canonical_institution_name`, `current_campus`, `official_award_title`, `official_programme_url`, `official_campus_source_url`, `source_quote_or_locator`, `identity_match`, `award_match`, `campus_match`, `duplicate_check_result`, `proposed_action`, `blocker`, `reviewer`, `reviewed_at`, `artifact_run_id`.

## Review statuses
- `verified-no-change`
- `proposed-offering-repair`
- `blocked-institution-identity`
- `blocked-award-title-mismatch`
- `blocked-campus-evidence`
- `blocked-duplicate-or-collision`
- `needs-fresh-live-artifact`

## Evidence standard
Use official institution/programme sources for award title and campus linkage. A general contact/address page can support address context but cannot independently prove that a specific programme is delivered at that campus. A prospectus mentioning a broad subject does not prove an exact canonical award-title match.

## Exit checks
All 36 register rows have a disposition tied to the exact artifact run; duplicates and identity collisions are explicitly checked; blocked rows remain blocked; proposed changes are reviewed before any production mutation.