#!/usr/bin/env python3
"""Pin the Hostinger SSH endpoint and verify its presented ED25519 key."""
from __future__ import annotations

import argparse
import re
import subprocess
from pathlib import Path

EXPECTED_HOST = "185.232.14.237"
EXPECTED_PORT = "65002"
EXPECTED_FINGERPRINT = "SHA256:W9YXwb9g6kNtTQxZvw+/cxOeyMUeyw+Y4v4iIc/eHAk"
FINGERPRINT_RE = re.compile(r"^SHA256:[A-Za-z0-9+/]+$")


def validate_reference(host: str, port: str, fingerprint: str) -> None:
    if not host or not port or not fingerprint:
        raise ValueError("required_host_identity_setting_missing")
    if host != EXPECTED_HOST:
        raise ValueError("unexpected_ssh_host")
    if port != EXPECTED_PORT:
        raise ValueError("unexpected_ssh_port")
    if not FINGERPRINT_RE.fullmatch(fingerprint) or fingerprint != EXPECTED_FINGERPRINT:
        raise ValueError("trusted_fingerprint_missing_or_mismatched")


def validate_presented_fingerprint(fingerprint: str, trusted: str) -> None:
    if fingerprint != trusted or fingerprint != EXPECTED_FINGERPRINT:
        raise ValueError("presented_host_key_fingerprint_mismatch")


def verify(host: str, port: str, fingerprint: str, known_hosts: Path) -> None:
    validate_reference(host, port, fingerprint)
    scan = subprocess.run(
        ["ssh-keyscan", "-T", "10", "-t", "ed25519", "-p", port, host],
        check=False, capture_output=True, text=True, timeout=15,
    )
    if scan.returncode != 0 or not scan.stdout.strip():
        raise ValueError("host_key_scan_failed")
    known_hosts.write_text(scan.stdout, encoding="utf-8")
    known_hosts.chmod(0o600)
    report = subprocess.run(
        ["ssh-keygen", "-lf", str(known_hosts), "-E", "sha256"],
        check=False, capture_output=True, text=True, timeout=10,
    )
    if report.returncode != 0:
        raise ValueError("host_key_fingerprint_unavailable")
    fingerprints = [line.split()[1] for line in report.stdout.splitlines() if len(line.split()) >= 2]
    if len(fingerprints) != 1:
        raise ValueError("unexpected_ed25519_host_key_count")
    validate_presented_fingerprint(fingerprints[0], fingerprint)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--host", required=True)
    parser.add_argument("--port", required=True)
    parser.add_argument("--fingerprint", required=True)
    parser.add_argument("--known-hosts", required=True, type=Path)
    args = parser.parse_args()
    try:
        verify(args.host, args.port, args.fingerprint, args.known_hosts)
    except (ValueError, OSError, subprocess.SubprocessError):
        print("host_identity_verification=FAIL")
        return 1
    print("host_identity_verification=PASS")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
