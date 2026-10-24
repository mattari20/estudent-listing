#!/usr/bin/env python3
"""Validate the Step 3 programme-offering evidence register without touching a database."""
import csv
import sys
from pathlib import Path
from urllib.parse import urlparse

EXPECTED_ROWS = 36
REQUIRED = {
    "program_id", "institution_id", "program_name", "official_source_url",
    "source_status", "campus_status", "remediation_decision",
}
SOURCE_STATUSES = {"source-found", "not-verified"}
DECISIONS = {"ready-for-validation", "review", "blocked"}
errors = []
path = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(
    "docs/step3-offering-gap-evidence-register.csv"
)
try:
    with path.open(newline="", encoding="utf-8-sig") as handle:
        reader = csv.DictReader(handle)
        if not reader.fieldnames or not REQUIRED.issubset(reader.fieldnames):
            missing = sorted(REQUIRED - set(reader.fieldnames or []))
            errors.append(f"Missing required columns: {', '.join(missing)}")
            rows = []
        else:
            rows = list(reader)
except OSError as exc:
    print(f"ERROR: cannot read {path}: {exc}", file=sys.stderr)
    sys.exit(2)

if len(rows) != EXPECTED_ROWS:
    errors.append(f"Expected {EXPECTED_ROWS} records, found {len(rows)}")

seen = set()
for line, row in enumerate(rows, start=2):
    pid = row.get("program_id", "").strip()
    institution = row.get("institution_id", "").strip()
    name = row.get("program_name", "").strip()
    url = row.get("official_source_url", "").strip()
    source = row.get("source_status", "").strip()
    campus = row.get("campus_status", "").strip()
    decision = row.get("remediation_decision", "").strip()
    prefix = f"line {line} (program_id={pid or 'blank'})"

    if not pid.isdigit():
        errors.append(f"{prefix}: program_id must be numeric")
    elif pid in seen:
        errors.append(f"{prefix}: duplicate program_id")
    else:
        seen.add(pid)
    if not institution.isdigit():
        errors.append(f"{prefix}: institution_id must be numeric")
    if not name:
        errors.append(f"{prefix}: program_name is required")
    if source not in SOURCE_STATUSES:
        errors.append(f"{prefix}: invalid source_status {source!r}")
    if decision not in DECISIONS:
        errors.append(f"{prefix}: invalid remediation_decision {decision!r}")

    if url:
        parsed = urlparse(url)
        if parsed.scheme != "https" or not parsed.netloc:
            errors.append(f"{prefix}: official source must be an HTTPS URL")
    elif decision != "blocked" or source != "not-verified":
        errors.append(f"{prefix}: missing source URL must remain blocked and not-verified")

    if decision == "ready-for-validation":
        if source != "source-found":
            errors.append(f"{prefix}: ready-for-validation requires a found source")
        if "confirmed" not in campus:
            errors.append(f"{prefix}: ready-for-validation requires campus confirmation")
    if "wrong-campus" in campus and decision != "blocked":
        errors.append(f"{prefix}: wrong-campus record must be blocked")
    if decision == "blocked" and source == "source-found" and "wrong-campus" not in campus:
        errors.append(f"{prefix}: blocked record needs explicit campus/source blocker")
    if decision == "ready-for-validation" and any(
        token in decision for token in ("approved", "apply", "active")
    ):
        errors.append(f"{prefix}: evidence register cannot authorize production writes")

if errors:
    print("STEP 3 EVIDENCE REGISTER: FAIL")
    for error in errors:
        print(f"- {error}")
    sys.exit(1)

print("STEP 3 EVIDENCE REGISTER: PASS")
print(f"records={len(rows)} unique_program_ids={len(seen)}")
print("database_mutations=0")
print("production_write_authorization=0")
