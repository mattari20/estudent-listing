# Institution import preflight: one clean upload file

## Goal

Prepare **one and only one CSV for the institution importer**. Source manifests and machine-generated duplicate reports are audit/review artifacts and must never be uploaded as institution records.

## Workflow

1. Research agent assembles candidates and source evidence from authoritative sources.
2. Preflight normalizes names and slugs, removes exact duplicates inside the candidate file, and compares candidates against a read-only canonical institution export.
3. High-confidence existing matches are excluded from the upload CSV and written to the JSON review report.
4. Similar-but-not-certain matches are held out of the upload CSV and listed as `hold_ambiguous_match` for focused human review.
5. Remaining rows are written in the exact importer schema order: `name, official_name, short_name, slug, institution_type, status`.
6. The operator uploads only the generated clean CSV, then reviews/approves through the existing workflow. This tool never imports, approves, promotes, or writes to the database.

## Run

```bash
python scripts/data-quality/prepare_institution_import.py \
  --input data/imports/2026-10-review-pack/institution-candidates-v1.0.csv \
  --canonical path/to/read-only-canonical-institutions.csv \
  --output /tmp/institutions-clean-import.csv \
  --report /tmp/institutions-preflight-report.json
```

The canonical export must be read-only and include `id,name,official_name,short_name,slug`. If `--canonical` is omitted, the tool explicitly reports that database-level duplicate checking was **not** performed. Do not treat that output as ready for production import.

## Safety and limitations

- Matching is deterministic and conservative; it is not a claim that AI can infer identity perfectly.
- Ambiguous matches are excluded from the upload file rather than silently accepted.
- Only the clean CSV is an importer input. Do not upload the source manifest or JSON report.
- A CSV-only comparison cannot detect aliases absent from both files, merger/name-history cases, or semantic relationships. Keep authoritative source links and use the application's identity-conflict checks too.
- No production DB access or write operation is part of this script.
