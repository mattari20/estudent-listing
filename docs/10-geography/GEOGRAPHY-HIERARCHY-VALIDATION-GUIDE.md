# Geography hierarchy validation guide

Use this guide when reviewing country, province/territory, district, and lower-level geography records. It is a review procedure, not an automated correction policy.

## Required checks

- Country records are roots and must not have a parent.
- Non-country records must have a parent.
- Every active node must reference an active parent.
- A node must not reference itself as parent.
- Slugs must use the expected lowercase URL-safe format.
- Sibling nodes must not share the same parent and slug.
- Active campuses must point to an active geography node.
- Geography IDs used by import rows must exist and satisfy the expected active-state gate.

## Review before correction

For each finding, verify the source and the full parent chain. Confirm the administrative type and jurisdiction from an authoritative source. A matching name is not sufficient evidence to reassign a node.

Review all active campuses depending on the node before considering deactivation, renaming, or parent reassignment. Preserve stable IDs when possible because campuses, search filters, and reports may reference them.

## Coverage reporting

Report total and active counts separately by geography type and region. Distinguish:
- missing geography from inactive geography;
- missing parent from inactive parent;
- duplicate identity candidates from confirmed duplicates;
- seed completeness from actual campus coverage.

A complete list of top-level provinces/territories does not prove complete district, tehsil, city, or campus coverage.

## Exit criteria

Each finding must have a source-backed classification, downstream-impact review, and proposed action. Unknown or conflicting findings remain on hold. Any live write requires a reviewed change plan and explicit authorization.
