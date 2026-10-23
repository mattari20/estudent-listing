# Pakistan geography coverage matrix

Use this matrix to report geographic coverage without overstating completeness. Counts should be sourced from a timestamped read-only report and separated by active/inactive status.

| Level | Coverage measure | Required interpretation |
|---|---|---|
| Country | Expected canonical country record exists | Does not prove lower-level completeness |
| Province/territory | Active records compared with the approved national list | Confirm names, types, and parent country |
| District | Active count by province/territory | A province count alone is insufficient |
| Tehsil/taluka/subdistrict | Active count by district where supported | Administrative terminology varies by jurisdiction |
| City/locality | Active records and parent coverage | Not every locality has an institute campus |
| Campus assignment | Active campuses with active geography | Missing and inactive geography must be reported separately |

## Coverage report fields

- check timestamp and observed deployment revision;
- total and active records by geography type;
- missing/inactive parents and duplicate parent+slug groups;
- active campuses with missing geography;
- active campuses assigned to inactive geography;
- campuses by province/territory and district;
- known limitations and unverified regions.

## Interpretation safeguards

- A seed workflow passing proves that workflow ran, not that national coverage is complete.
- Zero integrity errors does not prove all expected districts or campuses are present.
- A campus assigned to a province directly may be structurally valid but less precise than a district/city assignment; report precision separately if the schema supports it.
- Do not fabricate missing administrative units to improve counts.
- Record expected coverage requirements separately from observed counts.

Use this matrix for planning and reporting only. Any seed or canonical data correction must use an authorized, reviewed workflow.
