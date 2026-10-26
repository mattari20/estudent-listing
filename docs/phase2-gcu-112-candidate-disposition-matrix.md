## Scope
The baseline in issue #585 records 112 rows in the latest GCU discovery batch. The prior audit's zero canonical slug matches and zero validation errors do not prove candidate correctness.

## Required row-level fields
For each of the 112 candidate IDs, capture: batch ID, stable candidate ID, normalized URL, final URL after redirects, official-domain check, page-content classification, programme/award evidence excerpt, campus evidence, canonical institution match, duplicate check, extractor rule/version, disposition, reviewer, review timestamp, and evidence URL.

## Allowed dispositions
- `verified-programme-candidate`
- `unrelated-navigation-or-admission-page`
- `duplicate-of-candidate-id:<id>` only when a stable candidate ID and evidence establish duplication
- `wrong-institution-or-campus`
- `insufficient-official-evidence`
- `fetch-or-extraction-failure`
- `needs-manual-review`

## Review rules
- Review all rows; do not infer a global outcome from aggregate counts.
- A programme name alone is insufficient to establish canonical institution or campus linkage.
- Preserve the original batch and provenance; a refresh must not silently overwrite historical results.
- Do not bulk reject, promote, or delete rows.
- Report counts by disposition only after each row has an explicit disposition.

## Acceptance checks
Exactly 112 baseline candidate IDs are accounted for once; every disposition has a rationale; source URLs and extractor version are retained; ambiguous rows remain blocked; no canonical writes occur.