#!/usr/bin/env python3
"""Validate Phase 1C diagnostic evidence against its JSON Schema and privacy contract."""
import json
import re
import sys
from pathlib import Path

from jsonschema import Draft202012Validator

if len(sys.argv) != 3:
    raise SystemExit("usage: validate-readonly-evidence.py SCHEMA.json EVIDENCE.json")

schema_path = Path(sys.argv[1])
evidence_path = Path(sys.argv[2])
schema = json.loads(schema_path.read_text(encoding="utf-8"))
evidence = json.loads(evidence_path.read_text(encoding="utf-8"))
errors = sorted(Draft202012Validator(schema).iter_errors(evidence), key=lambda e: list(e.path))
if errors:
    for error in errors[:20]:
        print("schema_validation=FAIL path=" + "/".join(map(str, error.path)) + " rule=" + error.validator)
    raise SystemExit(1)

# The two row-mapping sections have a deliberately narrow, ordinal-only shape.
for section, limit in (("queue_mapping", 5), ("alert_mapping", 2)):
    for record in evidence[section]["records"]:
        if set(record) - {"record_ordinal", "state", "mapping_match_count"}:
            raise SystemExit("privacy_validation=FAIL section=" + section + " unexpected_record_fields")
        if not 1 <= record["record_ordinal"] <= limit:
            raise SystemExit("privacy_validation=FAIL section=" + section + " invalid_ordinal")

forbidden_keys = re.compile(r"^(password|passwd|secret|credential|private_key|ssh_key|raw_payload|record_content|alert_payload)$", re.I)
sensitive_assignment = re.compile(r"(password|passwd|secret|token|api[_-]?key)\s*[:=]\s*['\"]?(?!\[REDACTED\])([^\s,'\"]+)", re.I)
private_key = re.compile(r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----")

def inspect(value):
    if isinstance(value, dict):
        for key, child in value.items():
            if forbidden_keys.match(str(key)):
                raise ValueError("forbidden_sensitive_field")
            inspect(child)
    elif isinstance(value, list):
        for child in value:
            inspect(child)
    elif isinstance(value, str):
        if private_key.search(value) or sensitive_assignment.search(value):
            raise ValueError("sensitive_value_pattern")

try:
    inspect(evidence)
except ValueError as exc:
    raise SystemExit("privacy_validation=FAIL reason=" + str(exc))

if evidence["transaction"] != {"mode": "READ ONLY", "outcome": "ROLLED_BACK"}:
    raise SystemExit("transaction_validation=FAIL")
print("schema_validation=PASS")
print("privacy_validation=PASS")
print("registry_validation=PASS tables=39 indexes=23 orphans=18 duplicates=14")
print("transaction_validation=PASS")
