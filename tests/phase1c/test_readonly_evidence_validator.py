import importlib.util
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SCHEMA_PATH = ROOT / "scripts/phase1c/readonly-diagnostic.schema.json"
VALIDATOR_PATH = ROOT / "scripts/phase1c/validate-readonly-evidence.py"
SPEC = importlib.util.spec_from_file_location("readonly_evidence_validator", VALIDATOR_PATH)
VALIDATOR = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(VALIDATOR)
SCHEMA = VALIDATOR.json.loads(SCHEMA_PATH.read_text(encoding="utf-8"))


def valid_evidence():
    return {
        "schema_version": "phase1c-readonly-diagnostic/v2",
        "database": {"engine": "mysql", "server_version": "8.4.0", "identity_verified": True},
        "metadata_visibility": {"complete": True, "triggers_complete": True},
        "tables": [{"table": f"table_{index}", "state": "PASS"} for index in range(39)],
        "columns": [],
        "indexes": [],
        "expected_index_reconciliation": [
            {"table": "example", "name": f"index_{index}", "columns": ["id"], "unique": True, "index_type": "BTREE", "state": "PASS"}
            for index in range(23)
        ],
        "expected_column_reconciliation": [
            {"table": "example", "column": f"column_{index}", "type": "varchar(64)", "nullable": True, "state": "PASS"}
            for index in range(50)
        ],
        "foreign_keys": [],
        "expected_foreign_key_reconciliation": [
            {"name": f"foreign_{index}", "table": "child", "column": "parent_id", "referenced_table": "parent", "referenced_column": "id", "match_option": "NONE", "update_rule": "RESTRICT", "delete_rule": "RESTRICT", "state": "PASS"}
            for index in range(23)
        ],
        "expected_trigger_reconciliation": [
            {"name": f"trigger_{index}", "table": "example", "event": "DELETE", "timing": "BEFORE", "orientation": "ROW", "body_contract": "append_only", "state": "PASS"}
            for index in range(24)
        ],
        "migration_ledger_reconciliation": [
            {"migration": f"migration_{index}", "state": "PASS", "record_count": 1, "duplicate_count": 0, "batches": [1], "batch_values_valid": True}
            for index in range(18)
        ],
        "migration_object_reconciliation": [
            {"migration": f"migration_{index}", "ledger_state": "PASS", "object_state": "PASS",
             "consistency_state": "PASS", "reason": "LEDGER_AND_EXPECTED_OBJECTS_PRESENT",
             "objects": [{"object": "table:fixture", "state": "PASS"}]}
            for index in range(18)
        ],
        "full_migration_ledger_inventory": {
            "state": "PASS",
            "source_file_count": 18,
            "ledger_row_count": 18,
            "distinct_ledger_migration_count": 18,
            "source_files_not_recorded": [],
            "ledger_entries_without_source_files": [],
            "duplicate_ledger_entries": [],
            "invalid_ledger_rows": [],
            "batch_numbers": [1],
            "batch_gaps": [],
            "batch_gap_state": "PASS",
            "ordering_state": "PASS",
            "ordering_anomalies": [],
        },
        "queue_mapping": {"state": "NOT_CHECKED", "records": []},
        "alert_mapping": {"state": "NOT_CHECKED", "records": []},
        "orphan_checks": [{"id": f"orphan_{index}", "state": "PASS"} for index in range(18)],
        "duplicate_checks": [{"id": f"duplicate_{index}", "state": "PASS"} for index in range(14)],
        "registry_counts": {
            "tables": 39,
            "expected_indexes": 23,
            "expected_columns": 50,
            "expected_foreign_keys": 23,
            "expected_triggers": 24,
            "expected_migrations": 18,
            "orphan_checks": 18,
            "duplicate_checks": 14,
        },
        "transaction": {"mode": "READ ONLY", "outcome": "ROLLED_BACK"},
    }


class ReadonlyEvidenceValidatorTests(unittest.TestCase):
    def test_complete_reconciliation_evidence_passes(self):
        self.assertEqual(VALIDATOR.validate_document(SCHEMA, valid_evidence()), [])

    def test_each_reconciliation_section_is_required(self):
        for section in (
            "expected_column_reconciliation",
            "expected_foreign_key_reconciliation",
            "expected_trigger_reconciliation",
            "migration_ledger_reconciliation",
            "full_migration_ledger_inventory",
            "migration_object_reconciliation",
        ):
            with self.subTest(section=section):
                evidence = valid_evidence()
                del evidence[section]
                self.assertTrue(VALIDATOR.validate_document(SCHEMA, evidence))

    def test_incomplete_reconciliation_arrays_cannot_validate(self):
        for section in (
            "expected_column_reconciliation",
            "expected_foreign_key_reconciliation",
            "expected_trigger_reconciliation",
            "migration_ledger_reconciliation",
            "migration_object_reconciliation",
        ):
            with self.subTest(section=section):
                evidence = valid_evidence()
                evidence[section].pop()
                self.assertTrue(VALIDATOR.validate_document(SCHEMA, evidence))

    def test_duplicate_ledger_batches_are_all_retained(self):
        evidence = valid_evidence()
        evidence["migration_ledger_reconciliation"][0].update(
            {"state": "FAIL", "record_count": 2, "duplicate_count": 1, "batches": [4, 5]}
        )
        self.assertEqual(VALIDATOR.validate_document(SCHEMA, evidence), [])

        evidence["migration_ledger_reconciliation"][0]["batches"] = [4]
        self.assertIn(
            "privacy_validation=FAIL reason=migration_ledger_rows_not_retained",
            VALIDATOR.validate_document(SCHEMA, evidence),
        )

    def test_reconciliation_evidence_cannot_export_raw_sql_or_sensitive_values(self):
        evidence = valid_evidence()
        evidence["expected_trigger_reconciliation"][0]["observed"] = {"action_statement": "SIGNAL SQLSTATE '45000'"}
        self.assertTrue(VALIDATOR.validate_document(SCHEMA, evidence))

        evidence = valid_evidence()
        evidence["columns"] = [{"table": "users", "COLUMN_NAME": "password", "COLUMN_DEFAULT": "password=secret-value"}]
        self.assertIn(
            "privacy_validation=FAIL reason=sensitive_value_pattern",
            VALIDATOR.validate_document(SCHEMA, evidence),
        )


if __name__ == "__main__":
    unittest.main()