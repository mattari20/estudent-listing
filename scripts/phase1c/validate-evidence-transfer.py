#!/usr/bin/env python3
"""Validate a binary SSH evidence transfer before creating an artifact candidate."""
from __future__ import annotations

import argparse
import base64
import binascii
import gzip
import hashlib
import json
import os
import re
import subprocess
import sys
import tempfile
import zlib
from pathlib import Path
from typing import Any

MAX_COMPRESSED_BYTES = 70_000
MAX_EVIDENCE_BYTES = 2_000_000
SHA256_RE = re.compile(r"^[0-9a-f]{64}$")


def inspect_legacy_base64(captured: str) -> dict[str, Any]:
    """Return metadata only; never return or print the captured payload."""
    alphabet_only = re.fullmatch(r"[A-Za-z0-9+/]*={0,2}", captured) is not None
    length_valid = len(captured) % 4 == 0
    padding = len(captured) - len(captured.rstrip("="))
    padding_valid = alphabet_only and padding <= 2 and (padding == 0 or length_valid)
    decoded: bytes | None = None
    try:
        if not alphabet_only or not length_valid or not padding_valid:
            raise binascii.Error("invalid base64 shape")
        decoded = base64.b64decode(captured, validate=True)
    except (binascii.Error, ValueError):
        decoded = None

    gzip_integrity = "NOT_CHECKED"
    compressed_sha256 = "unavailable"
    if decoded is not None:
        compressed_sha256 = hashlib.sha256(decoded).hexdigest()
        try:
            _bounded_gzip_decompress(decoded)
            gzip_integrity = "PASS"
        except (OSError, EOFError, ValueError, zlib.error):
            gzip_integrity = "FAIL"

    return {
        "captured_output_length": len(captured),
        "base64_alphabet_only": alphabet_only,
        "base64_length_mod_4": len(captured) % 4,
        "base64_length_valid": length_valid,
        "base64_padding_valid": padding_valid,
        "strict_base64_decode": decoded is not None,
        "decoded_compressed_bytes": len(decoded) if decoded is not None else None,
        "gzip_integrity": gzip_integrity,
        "decoded_compressed_sha256": compressed_sha256,
    }


def _fail(reason: str) -> None:
    raise ValueError(reason)


def _bounded_gzip_decompress(payload: bytes) -> bytes:
    decoder = zlib.decompressobj(16 + zlib.MAX_WBITS)
    raw = decoder.decompress(payload, MAX_EVIDENCE_BYTES + 1)
    if len(raw) > MAX_EVIDENCE_BYTES or decoder.unconsumed_tail:
        _fail("decompressed_evidence_exceeds_limit")
    raw += decoder.flush()
    if len(raw) > MAX_EVIDENCE_BYTES:
        _fail("decompressed_evidence_exceeds_limit")
    if not decoder.eof or decoder.unused_data:
        _fail("gzip_integrity_or_framing_failure")
    return raw


def validate_transfer(
    manifest_path: Path,
    compressed_path: Path,
    schema_path: Path,
    validator_path: Path,
    evidence_output: Path,
    expected_run_id: str,
    expected_target_sha: str,
) -> dict[str, Any]:
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    required = {"workflow_run_id", "target_sha", "compressed_bytes", "compressed_sha256"}
    if not isinstance(manifest, dict) or set(manifest) != required:
        _fail("manifest_shape_invalid")
    if str(manifest["workflow_run_id"]) != expected_run_id:
        _fail("manifest_run_id_mismatch")
    if manifest["target_sha"] != expected_target_sha:
        _fail("manifest_target_sha_mismatch")
    if not isinstance(manifest["compressed_bytes"], int) or isinstance(manifest["compressed_bytes"], bool):
        _fail("manifest_byte_count_invalid")
    if not isinstance(manifest["compressed_sha256"], str) or not SHA256_RE.fullmatch(manifest["compressed_sha256"]):
        _fail("manifest_checksum_invalid")

    compressed = compressed_path.read_bytes()
    compressed_bytes = len(compressed)
    if compressed_bytes == 0 or compressed_bytes > MAX_COMPRESSED_BYTES:
        _fail("compressed_evidence_size_out_of_bounds")
    if compressed_bytes != manifest["compressed_bytes"]:
        _fail("compressed_evidence_byte_count_mismatch")

    runner_sha256 = hashlib.sha256(compressed).hexdigest()
    remote_sha256 = manifest["compressed_sha256"]
    if runner_sha256 != remote_sha256:
        _fail("compressed_evidence_checksum_mismatch")

    raw = _bounded_gzip_decompress(compressed)
    try:
        json.loads(raw.decode("utf-8"))
    except (UnicodeDecodeError, json.JSONDecodeError):
        _fail("evidence_json_invalid")

    evidence_output.parent.mkdir(parents=True, exist_ok=True)
    temporary_path: Path | None = None
    try:
        with tempfile.NamedTemporaryFile(
            mode="wb", prefix=".phase1c-evidence-", suffix=".json.tmp",
            dir=evidence_output.parent, delete=False
        ) as temporary:
            temporary.write(raw)
            temporary_path = Path(temporary.name)

        result = subprocess.run(
            [sys.executable, str(validator_path), str(schema_path), str(temporary_path)],
            check=False, capture_output=True, text=True,
        )
        if result.stdout:
            print(result.stdout, end="")
        if result.returncode != 0:
            _fail("evidence_schema_or_privacy_validation_failed")

        os.replace(temporary_path, evidence_output)
        temporary_path = None
    finally:
        if temporary_path is not None:
            temporary_path.unlink(missing_ok=True)

    evidence_sha256 = hashlib.sha256(raw).hexdigest()
    evidence_sha_path = evidence_output.parent / "evidence.sha256"
    evidence_sha_path.write_text(evidence_sha256 + "\n", encoding="ascii")
    artifact_manifest = {
        "workflow_run_id": expected_run_id,
        "target_sha": expected_target_sha,
        "evidence_sha256": evidence_sha256,
        "compressed_sha256": remote_sha256,
        "compressed_bytes": compressed_bytes,
    }
    (evidence_output.parent / "evidence-manifest.json").write_text(
        json.dumps(artifact_manifest, sort_keys=True, separators=(",", ":")) + "\n",
        encoding="utf-8",
    )

    metrics = {
        "compressed_bytes": compressed_bytes,
        "gzip_integrity": "PASS",
        "remote_compressed_sha256": remote_sha256,
        "runner_compressed_sha256": runner_sha256,
        "evidence_sha256": evidence_sha256,
        "json_schema_and_privacy": "PASS",
    }
    print(
        "transfer_validation=PASS "
        + " ".join(f"{key}={value}" for key, value in metrics.items())
    )
    return metrics


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--manifest", type=Path, required=True)
    parser.add_argument("--compressed", type=Path, required=True)
    parser.add_argument("--schema", type=Path, required=True)
    parser.add_argument("--validator", type=Path, required=True)
    parser.add_argument("--evidence-output", type=Path, required=True)
    parser.add_argument("--run-id", required=True)
    parser.add_argument("--target-sha", required=True)
    args = parser.parse_args()
    if not re.fullmatch(r"[0-9]+", args.run_id):
        print("transfer_validation=FAIL reason=invalid_run_id")
        return 1
    if not re.fullmatch(r"[0-9a-f]{40}", args.target_sha):
        print("transfer_validation=FAIL reason=invalid_target_sha")
        return 1
    try:
        validate_transfer(
            args.manifest, args.compressed, args.schema, args.validator,
            args.evidence_output, args.run_id, args.target_sha,
        )
    except (OSError, ValueError, json.JSONDecodeError, zlib.error) as exc:
        reason = str(exc) if str(exc) else "validation_failed"
        print(f"transfer_validation=FAIL reason={reason}")
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
