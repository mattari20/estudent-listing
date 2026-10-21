# Institution import preflight: clean import CSV plus evidence sidecar

## Goal

Prepare one clean CSV for the institution importer and, separately, an evidence sidecar for official-source research. The sidecar is **not** an importer file and must never be uploaded as institution records.

## Workflow

1. Research each institution from an official university website or authoritative regulator page.
2. Populate optional candidate columns `official_source_url` and `source_excerpt`. The excerpt must be copied from the actual page; never generate or invent it.
3. Run preflight to normalize names/slugs, remove exact duplicates, and compare candidates against a read-only canonical institution export.
4. The tool writes the importer CSV with only the six supported fields and optionally writes a separate evidence sidecar.
5. Sidecar rows are marked `ready_for_review` only when the URL is HTTPS and the excerpt is at least 20 characters. This is a syntactic preflight, **not proof the source was visited or the excerpt is authentic**.
6. Keep rows with missing or incomplete evidence held for research. The Laravel application still requires evidence to be captured and linked to each import row before promotion; generating a sidecar alone does not promote or publish records.

## Run

```bash
python scripts/data-quality/prepare_institution_import.py \
  --input data/imports/candidates.csv \
  --canonical path/to/read-only-canonical-institutions.csv \
  --output /tmp/institutions-clean-import.csv \
  --report /tmp/institutions-preflight-report.json \
  --evidence-output /tmp/institutions-evidence-sidecar.csv
```

Candidate source columns are optional: `official_source_url` (or `source_url`) and `source_excerpt`. The importer output remains exactly `name, official_name, short_name, slug, institution_type, status`. The sidecar is for review and future integration with the application's evidence-capture workflow.

## Safety and limitations

- No database writes, approvals, promotion, or publication are performed.
- Do not add dummy links or AI-generated excerpts as evidence.
- Duplicate matching is deterministic and conservative; ambiguous matches are held rather than silently accepted.
- Without `--canonical`, canonical duplicate checking is explicitly marked incomplete.
- A URL/excerpt pair still requires verification against the source and explicit capture in the application's evidence system. Do not treat a syntactically valid sidecar as proof of verified evidence.
