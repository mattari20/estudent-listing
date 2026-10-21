#!/usr/bin/env python3
"""Prepare a safe institution-import CSV and a separate duplicate-review report.

No database writes are performed. Pass a read-only canonical institution export
when available; without one, the report explicitly says canonical duplicate
checking was not performed.
"""
from __future__ import annotations

import argparse
import csv
import json
import re
import sys
import unicodedata
from urllib.parse import urlparse
from difflib import SequenceMatcher
from pathlib import Path

IMPORT_COLUMNS = ["name", "official_name", "short_name", "slug", "institution_type", "status"]
DEFAULT_CANONICAL_COLUMNS = ["id", "name", "official_name", "short_name", "slug"]
DUPLICATE_THRESHOLD = 0.93
REVIEW_THRESHOLD = 0.82


def normalize(value: object) -> str:
    text = unicodedata.normalize("NFKD", str(value or "")).encode("ascii", "ignore").decode()
    return re.sub(r"[^a-z0-9]+", " ", text.lower()).strip()


def slugify(value: object) -> str:
    return normalize(value).replace(" ", "-")


def read_csv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8-sig", newline="") as stream:
        return [{str(k or "").strip(): str(v or "").strip() for k, v in row.items()}
                for row in csv.DictReader(stream)]


def write_csv(path: Path, rows: list[dict[str, str]], columns: list[str]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="") as stream:
        writer = csv.DictWriter(stream, fieldnames=columns, extrasaction="ignore")
        writer.writeheader()
        writer.writerows(rows)


def names(row: dict[str, str]) -> set[str]:
    return {n for n in (normalize(row.get(k)) for k in
                        ("name", "official_name", "short_name", "slug")) if n}


def similarity(candidate: dict[str, str], existing: dict[str, str]) -> tuple[float, str]:
    # Exact identity across any populated name/alias/slug field wins.
    a_names, b_names = names(candidate), names(existing)
    exact = a_names & b_names
    if exact:
        return 1.0, "exact normalized name/alias/slug match: " + sorted(exact)[0]
    best = 0.0
    reason = "closest normalized name/alias comparison"
    for a in a_names:
        for b in b_names:
            score = SequenceMatcher(None, a, b).ratio()
            if score > best:
                best, reason = score, f"similar names: '{a}' vs '{b}'"
    # Same slug is a strong match even if other labels differ.
    if candidate.get("slug") and existing.get("slug") and slugify(candidate["slug"]) == slugify(existing["slug"]):
        return 1.0, "same normalized slug"
    return best, reason


def prepare(candidates: list[dict[str, str]], canonical: list[dict[str, str]] | None):
    accepted: list[dict[str, str]] = []
    excluded: list[dict[str, object]] = []
    seen: list[dict[str, str]] = []

    for index, original in enumerate(candidates, start=2):
        row = {column: (original.get(column) or "").strip() for column in IMPORT_COLUMNS}
        name = row["name"] or row["official_name"]
        if not name:
            excluded.append({"source_row": index, "name": "", "decision": "hold_invalid",
                             "reason": "required name and official_name are both empty"})
            continue
        row["name"] = name
        row["official_name"] = row["official_name"] or name
        row["slug"] = row["slug"] or slugify(name)

        # Exact duplicates inside the candidate file: keep the first occurrence only.
        internal_match = next((prior for prior in seen
                               if similarity(row, prior)[0] >= DUPLICATE_THRESHOLD), None)
        if internal_match:
            _, why = similarity(row, internal_match)
            excluded.append({"source_row": index, "name": name, "decision": "exclude_duplicate_in_file",
                             "matched_candidate": internal_match["name"], "score": 1.0, "reason": why})
            continue

        if canonical is not None:
            matches = []
            for existing in canonical:
                score, why = similarity(row, existing)
                if score >= REVIEW_THRESHOLD:
                    matches.append((score, existing, why))
            matches.sort(key=lambda item: item[0], reverse=True)
            if matches and matches[0][0] >= DUPLICATE_THRESHOLD:
                score, existing, why = matches[0]
                excluded.append({"source_row": index, "name": name, "decision": "exclude_existing_match",
                                 "canonical_id": existing.get("id", ""), "canonical_name": existing.get("name", ""),
                                 "score": round(score, 3), "reason": why})
                continue
            if matches:
                score, existing, why = matches[0]
                excluded.append({"source_row": index, "name": name, "decision": "hold_ambiguous_match",
                                 "canonical_id": existing.get("id", ""), "canonical_name": existing.get("name", ""),
                                 "score": round(score, 3), "reason": why})
                continue

        accepted.append(row)
        seen.append(row)

    return accepted, excluded


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--input", required=True, type=Path, help="Candidate CSV using the institution import schema")
    parser.add_argument("--canonical", type=Path, help="Read-only canonical institution export CSV (id,name,official_name,short_name,slug)")
    parser.add_argument("--output", required=True, type=Path, help="Single clean CSV to upload into the institution importer")
    parser.add_argument("--report", required=True, type=Path, help="JSON report; not an import file")
    parser.add_argument("--evidence-output", type=Path, help="Optional evidence sidecar CSV; never an importer file")
    args = parser.parse_args()

    candidates = read_csv(args.input)
    missing = [column for column in IMPORT_COLUMNS if column not in (candidates[0].keys() if candidates else [])]
    if missing:
        print("Input is missing importer columns: " + ", ".join(missing), file=sys.stderr)
        return 2
    canonical = read_csv(args.canonical) if args.canonical else None
    accepted, excluded = prepare(candidates, canonical)
    write_csv(args.output, accepted, IMPORT_COLUMNS)

    # Keep evidence separate because the Laravel importer schema intentionally
    # accepts only institution fields. Never fabricate a source or excerpt.
    evidence_rows = []
    for index, original in enumerate(candidates, start=2):
        row_name = (original.get("name") or original.get("official_name") or "").strip()
        url = (original.get("official_source_url") or original.get("source_url") or "").strip()
        excerpt = (original.get("source_excerpt") or "").strip()
        parsed = urlparse(url) if url else None
        valid_url = bool(parsed and parsed.scheme == "https" and parsed.netloc)
        state = "ready_for_review" if valid_url and len(excerpt) >= 20 else "missing_or_incomplete"
        evidence_rows.append({
            "source_row": index,
            "name": row_name,
            "official_source_url": url if valid_url else "",
            "source_excerpt": excerpt if valid_url and len(excerpt) >= 20 else "",
            "evidence_preflight": state,
            "note": "URL and excerpt require verification against the official page before capture" if state == "ready_for_review" else "Do not invent evidence; research official source and copy a relevant excerpt",
        })
    if args.evidence_output:
        write_csv(args.evidence_output, evidence_rows, [
            "source_row", "name", "official_source_url", "source_excerpt", "evidence_preflight", "note"
        ])
    args.report.parent.mkdir(parents=True, exist_ok=True)
    report = {
        "input_file": str(args.input),
        "canonical_check_performed": canonical is not None,
        "canonical_source_file": str(args.canonical) if args.canonical else None,
        "input_rows": len(candidates),
        "clean_import_rows": len(accepted),
        "excluded_or_held_rows": len(excluded),
        "counts_by_decision": {decision: sum(1 for row in excluded if row["decision"] == decision)
                               for decision in sorted({str(row["decision"]) for row in excluded})},
        "excluded_or_held": excluded,
        "evidence_sidecar_written": bool(args.evidence_output),
        "evidence_rows_ready_for_review": sum(1 for row in evidence_rows if row["evidence_preflight"] == "ready_for_review"),
        "evidence_rows_missing_or_incomplete": sum(1 for row in evidence_rows if row["evidence_preflight"] != "ready_for_review"),
        "safety_note": ("No database writes performed. Rows held as ambiguous require review. "
                        "Canonical duplicate checking is incomplete unless --canonical is supplied.")
    }
    args.report.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({k: report[k] for k in ("input_rows", "clean_import_rows", "excluded_or_held_rows",
                                              "canonical_check_performed", "counts_by_decision")}, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
