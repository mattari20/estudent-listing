#!/usr/bin/env python3
"""Validate Step 3 offering evidence and proposed campus mappings; never writes to a database."""
import csv
import sys
from pathlib import Path
from urllib.parse import urlparse

EXPECTED_ROWS = 36
REQUIRED = {
    "program_id", "institution_id", "program_name", "official_source_url",
    "source_status", "campus_status", "remediation_decision",
    "candidate_campus_id", "candidate_campus_name",
    "candidate_delivery_mode", "campus_source_url",
}
ALLOWED_SOURCE = {"source-found", "not-verified"}
ALLOWED_DECISIONS = {"ready-for-validation", "review", "blocked"}
ALLOWED_MODES = {"on_campus", "online", "hybrid", "distance", "unknown"}
errors = []
warnings = []
path = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(
    "docs/step3-offering-gap-evidence-register.csv"
)
try:
    with path.open(newline="", encoding="utf-8-sig") as handle:
        reader = csv.DictReader(handle)
        if not reader.fieldnames or not REQUIRED.issubset(reader.fieldnames):
            missing = sorted(REQUIRED - set(reader.fieldnames or []))
            errors.append("Missing required columns: " + ", ".join(missing))
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
    source_url = row.get("official_source_url", "").strip()
    source_status = row.get("source_status", "").strip()
    campus_status = row.get("campus_status", "").strip()
    decision = row.get("remediation_decision", "").strip()
    campus_id = row.get("candidate_campus_id", "").strip()
    campus_name = row.get("candidate_campus_name", "").strip()
    mode = row.get("candidate_delivery_mode", "").strip()
    campus_url = row.get("campus_source_url", "").strip()
    label = f"line {line} program_id={pid or 'blank'}"

    if not pid.isdigit():
        errors.append(f"{label}: program_id must be numeric")
    elif pid in seen:
        errors.append(f"{label}: duplicate program_id")
    else:
        seen.add(pid)
    if not institution.isdigit():
        errors.append(f"{label}: institution_id must be numeric")
    if not name:
        errors.append(f"{label}: program_name is required")
    if source_status not in ALLOWED_SOURCE:
        errors.append(f"{label}: invalid source_status {source_status!r}")
    if decision not in ALLOWED_DECISIONS:
        errors.append(f"{label}: invalid remediation_decision {decision!r}")
    if mode not in ALLOWED_MODES:
        errors.append(f"{label}: invalid candidate_delivery_mode {mode!r}")
    if not campus_id.isdigit() or not campus_name:
        errors.append(f"{label}: candidate campus ID and name are required for explicit review")
    for field_name, url in (("official_source_url", source_url), ("campus_source_url", campus_url)):
        if url:
            parsed = urlparse(url)
            if parsed.scheme != "https" or not parsed.netloc:
                errors.append(f"{label}: {field_name} must be an HTTPS URL")
    if not source_url and not (decision == "blocked" and source_status == "not-verified"):
        errors.append(f"{label}: missing official programme source must remain blocked/not-verified")
    if not campus_url and "confirmed" not in campus_status:
        warnings.append(f"{label}: campus source URL absent; campus remains unverified")
    if decision == "ready-for-validation":
        if source_status != "source-found" or "confirmed" not in campus_status:
            errors.append(f"{label}: ready-for-validation requires programme source and campus confirmation")
    if "wrong-campus" in campus_status and decision != "blocked":
        errors.append(f"{label}: wrong-campus record must be blocked")
    if decision == "blocked" and source_status == "source-found" and "wrong-campus" not in campus_status:
        errors.append(f"{label}: blocked row must document a campus/source blocker")
    if mode in {"online", "distance"} and campus_id:
        warnings.append(f"{label}: virtual delivery is proposed; do not assume campus is a teaching location")
    if decision == "ready-for-validation":
        warnings.append(f"{label}: this label is not production-write authorization")

if errors:
    print("STEP 3 CAMPUS-AWARE PREFLIGHT: FAIL")
    for error in errors:
        print("- " + error)
    print("database_mutations=0")
    sys.exit(1)

print("STEP 3 CAMPUS-AWARE PREFLIGHT: PASS")
print(f"records={len(rows)} unique_program_ids={len(seen)}")
print(f"review_warnings={len(warnings)}")
for warning in warnings:
    print("WARNING: " + warning)
print("database_mutations=0")
print("production_write_authorization=0")
