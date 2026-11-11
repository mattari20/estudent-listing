#!/usr/bin/env python3
"""Disposable fixtures for Phase 1C evidence transport validation."""
from __future__ import annotations

import gzip
import hashlib
import importlib.util
import json
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
TRANSFER_MODULE_PATH = ROOT / "scripts/phase1c/validate-evidence-transfer.py"
TRANSFER_SPEC = importlib.util.spec_from_file_location("validate_evidence_transfer", TRANSFER_MODULE_PATH)
if TRANSFER_SPEC is None or TRANSFER_SPEC.loader is None:
    raise RuntimeError("cannot load evidence transfer validator")
TRANSFER_MODULE = importlib.util.module_from_spec(TRANSFER_SPEC)
TRANSFER_SPEC.loader.exec_module(TRANSFER_MODULE)
MAX_COMPRESSED_BYTES = TRANSFER_MODULE.MAX_COMPRESSED_BYTES
inspect_legacy_base64 = TRANSFER_MODULE.inspect_legacy_base64
validate_transfer = TRANSFER_MODULE.validate_transfer


class EvidenceTransferTests(unittest.TestCase):
    def setUp(self) -> None:
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.directory = Path(self.temp.name)
        self.schema = ROOT / "scripts/phase1c/readonly-diagnostic.schema.json"
        self.validator = ROOT / "scripts/phase1c/validate-readonly-evidence.py"
        self.run_id = "987654321"
        self.target_sha = "3add1b95ff4b83b03892077a6c2ee81ade9f407c"
        self.evidence = self.valid_evidence()
        self.raw = json.dumps(self.evidence, separators=(",", ":")).encode()
        self.compressed = gzip.compress(self.raw, mtime=0)
        self.compressed_path = self.directory / "evidence.gz"
        self.manifest_path = self.directory / "remote-manifest.json"
        self.output_path = self.directory / "phase1c-readonly-evidence.json"
        self.write_transfer(self.compressed)

    @staticmethod
    def valid_evidence() -> dict:
        return {
            "schema_version": "phase1c-readonly-diagnostic/v3",
            "database": {"engine": "mysql", "server_version": "8.4-fixture", "identity_verified": True},
            "metadata_visibility": {"complete": True, "triggers_complete": True},
            "tables": [{"table": f"fixture_{i}", "state": "PASS"} for i in range(39)],
            "columns": [],
            "indexes": [],
            "expected_index_reconciliation": [
                {"table": "fixture", "name": f"idx_{i}", "columns": ["id"],
                 "unique": False, "index_type": "BTREE", "state": "PASS"}
                for i in range(23)
            ],
            "foreign_keys": [],
            "expected_column_reconciliation": [
                {"table": "fixture", "column": f"column_{i}", "type": "varchar(64)", "nullable": True, "state": "PASS"}
                for i in range(50)
            ],
            "expected_foreign_key_reconciliation": [
                {"name": f"fk_{i}", "table": "child", "column": "parent_id", "referenced_table": "parent",
                 "referenced_column": "id", "match_option": "NONE", "update_rule": "RESTRICT",
                 "delete_rule": "RESTRICT", "state": "PASS"}
                for i in range(23)
            ],
            "expected_trigger_reconciliation": [
                {"name": f"trigger_{i}", "table": "fixture", "event": "DELETE", "timing": "BEFORE",
                 "orientation": "ROW", "body_contract": "append_only", "state": "PASS"}
                for i in range(24)
            ],
            "migration_ledger_reconciliation": [
                {"migration": f"migration_{i}", "state": "PASS", "record_count": 1,
                 "duplicate_count": 0, "batches": [1], "batch_values_valid": True}
                for i in range(18)
            ],
            "migration_object_reconciliation": [
                {"migration": f"migration_{i}", "ledger_state": "PASS", "object_state": "PASS",
                 "consistency_state": "PASS", "reason": "LEDGER_AND_EXPECTED_OBJECTS_PRESENT",
                 "objects": [{"object": "table:fixture", "state": "PASS"}]}
                for i in range(18)
            ],
            "full_migration_ledger_inventory": {
                "state": "PASS", "source_file_count": 18, "ledger_row_count": 18,
                "distinct_ledger_migration_count": 18, "source_files_not_recorded": [],
                "ledger_entries_without_source_files": [], "duplicate_ledger_entries": [],
                "invalid_ledger_rows": [], "batch_numbers": [1], "batch_gaps": [],
                "batch_gap_state": "PASS", "ordering_state": "PASS", "ordering_anomalies": [],
            },
            "source_schema_reconciliation": {
                "contract": "phase1d2b-schema-comparison/v1", "state": "NOT_CHECKED",
                "table_count": 63, "complete_table_count": 0, "results": {},
                "definition_coverage": {"state": "NOT_CHECKED"},
                "observed_metadata_collected": True, "schema_equivalence_proven": False,
            },
            "full_source_migration_reconciliation": {
                "state": "PASS", "source_count": 97, "ledger_row_count": 97,
                "source_files_not_recorded": [], "ledger_entries_without_source_files": [],
                "duplicate_ledger_entries": [], "invalid_ledger_rows": [],
                "batch_numbers": [1], "batch_gaps": [], "batch_gap_state": "NOT_CHECKED",
                "ordering_state": "PASS", "migration_object_reconciliation": [],
                "object_mapping_state": "NOT_CHECKED",
            },
            "queue_mapping": {"state": "PASS", "records": []},
            "alert_mapping": {"state": "PASS", "records": []},
            "orphan_checks": [{"id": f"orphan_{i}", "state": "PASS"} for i in range(18)],
            "duplicate_checks": [{"id": f"duplicate_{i}", "state": "PASS"} for i in range(14)],
            "registry_counts": {
                "tables": 39, "expected_indexes": 23, "expected_columns": 50,
                "expected_foreign_keys": 23, "expected_triggers": 24,
                "expected_migrations": 18, "orphan_checks": 18, "duplicate_checks": 14,
            },
            "transaction": {"mode": "READ ONLY", "outcome": "ROLLED_BACK"},
        }

    def write_transfer(self, compressed: bytes, checksum: str | None = None) -> None:
        self.compressed_path.write_bytes(compressed)
        manifest = {
            "workflow_run_id": self.run_id,
            "target_sha": self.target_sha,
            "compressed_bytes": len(compressed),
            "compressed_sha256": checksum or hashlib.sha256(compressed).hexdigest(),
        }
        self.manifest_path.write_text(json.dumps(manifest), encoding="utf-8")

    def validate(self) -> dict:
        return validate_transfer(
            self.manifest_path, self.compressed_path, self.schema, self.validator,
            self.output_path, self.run_id, self.target_sha,
        )

    def assert_rejected_without_artifact(self) -> None:
        with self.assertRaises((ValueError, OSError)):
            self.validate()
        self.assertFalse(self.output_path.exists())
        self.assertFalse((self.directory / "evidence-manifest.json").exists())
        self.assertFalse((self.directory / "evidence.sha256").exists())

    def test_valid_binary_transfer_passes_and_writes_manifest(self) -> None:
        metrics = self.validate()
        self.assertEqual(metrics["gzip_integrity"], "PASS")
        self.assertEqual(metrics["remote_compressed_sha256"], metrics["runner_compressed_sha256"])
        self.assertTrue(self.output_path.is_file())
        self.assertTrue((self.directory / "evidence-manifest.json").is_file())
        self.assertTrue((self.directory / "evidence.sha256").is_file())

    def test_legacy_stdout_capture_includes_action_footer_and_is_rejected(self) -> None:
        encoded = __import__("base64").b64encode(self.compressed).decode("ascii")
        clean = inspect_legacy_base64(encoded)
        mixed = inspect_legacy_base64(encoded + "\nSuccessfully executed commands to all hosts.\n")
        self.assertTrue(clean["base64_alphabet_only"])
        self.assertTrue(clean["strict_base64_decode"])
        self.assertEqual(clean["gzip_integrity"], "PASS")
        self.assertFalse(mixed["base64_alphabet_only"])
        self.assertFalse(mixed["strict_base64_decode"])
        self.assertEqual(mixed["gzip_integrity"], "NOT_CHECKED")
        print(
            "legacy_capture_diagnostics=PASS "
            + " ".join(f"{key}={value}" for key, value in mixed.items())
        )

    def test_corrupted_base64_character_is_rejected(self) -> None:
        encoded = __import__("base64").b64encode(self.compressed).decode("ascii")
        corrupted = encoded[:12] + "!" + encoded[13:]
        metrics = inspect_legacy_base64(corrupted)
        self.assertFalse(metrics["base64_alphabet_only"])
        self.assertFalse(metrics["strict_base64_decode"])

    def test_truncated_base64_is_rejected(self) -> None:
        encoded = __import__("base64").b64encode(self.compressed).decode("ascii")
        metrics = inspect_legacy_base64(encoded[:-1])
        self.assertFalse(metrics["base64_length_mod_4"] == 0)
        self.assertFalse(metrics["strict_base64_decode"])

    def test_invalid_base64_padding_is_rejected(self) -> None:
        metrics = inspect_legacy_base64("YWJj=")
        self.assertFalse(metrics["base64_length_mod_4"] == 0)
        self.assertFalse(metrics["base64_padding_valid"])
        self.assertFalse(metrics["strict_base64_decode"])

    def test_corrupted_gzip_is_rejected(self) -> None:
        damaged = bytearray(self.compressed)
        damaged[-5] ^= 0x01
        self.write_transfer(bytes(damaged))
        self.assert_rejected_without_artifact()

    def test_payload_over_configured_limit_is_rejected(self) -> None:
        oversized = b"x" * (MAX_COMPRESSED_BYTES + 1)
        self.write_transfer(oversized)
        self.assert_rejected_without_artifact()

    def test_invalid_json_is_rejected(self) -> None:
        self.write_transfer(gzip.compress(b"{not-json", mtime=0))
        self.assert_rejected_without_artifact()

    def test_schema_invalid_json_is_rejected(self) -> None:
        self.write_transfer(gzip.compress(b"{}", mtime=0))
        self.assert_rejected_without_artifact()

    def test_checksum_mismatch_is_rejected(self) -> None:
        self.write_transfer(self.compressed, checksum="0" * 64)
        self.assert_rejected_without_artifact()

    def test_truncated_compressed_stream_is_rejected(self) -> None:
        self.write_transfer(self.compressed[:-4])
        self.assert_rejected_without_artifact()


if __name__ == "__main__":
    unittest.main(verbosity=2)
