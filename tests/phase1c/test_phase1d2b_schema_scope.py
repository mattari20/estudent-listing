from __future__ import annotations

import json
import re
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
SCOPE_PATH = ROOT / "database/schema-contract/phase1d2b-schema-scope-inventory.json"
DIAGNOSTIC_PATH = ROOT / "scripts/phase1c/readonly-schema-diagnostic.php"


class Phase1D2BSchemaScopeTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.scope = json.loads(SCOPE_PATH.read_text(encoding="utf-8"))
        cls.diagnostic = DIAGNOSTIC_PATH.read_text(encoding="utf-8")
        cls.migration_dir = ROOT / "database/migrations"

    def test_scope_counts_and_framework_ledger_are_explicit(self):
        self.assertEqual(self.scope["application_table_count"], 62)
        self.assertEqual(self.scope["framework_managed_table_count"], 1)
        self.assertEqual(self.scope["total_table_count"], 63)
        self.assertEqual(self.scope["tables"]["migrations"]["ownership"], "framework_managed")
        self.assertEqual(self.scope["tables"]["migrations"]["managed_by"], "Laravel migrator")

    def test_existing_diagnostic_allowlist_is_a_subset_of_full_scope(self):
        match = re.search(r"private const TABLES = \[(.*?)\];", self.diagnostic, re.S)
        self.assertIsNotNone(match, "diagnostic TABLES allowlist must remain discoverable")
        allowlist = set(re.findall(r"'([a-zA-Z0-9_]+)'", match.group(1)))
        self.assertTrue(allowlist)
        self.assertTrue(allowlist.issubset(set(self.scope["tables"])))

    def test_every_application_table_has_existing_creation_source(self):
        for table, entry in self.scope["tables"].items():
            with self.subTest(table=table):
                if entry["ownership"] == "framework_managed":
                    self.assertIsNone(entry["creation_migration"])
                    continue
                source_path = ROOT / entry["creation_migration"]
                self.assertTrue(source_path.is_file(), f"missing source file: {entry['creation_migration']}")
                source = source_path.read_text(encoding="utf-8")
                self.assertIn(table, source, f"creation source does not identify {table}")
                self.assertTrue(
                    re.search(r"Schema::create|createTableIfMissing|CREATE TABLE", source, re.I),
                    f"no supported table creation operation found for {table}",
                )

    def test_alteration_migration_paths_exist_and_reference_their_table(self):
        for table, entry in self.scope["tables"].items():
            for migration in entry.get("alteration_migrations", []):
                with self.subTest(table=table, migration=migration):
                    source_path = ROOT / migration
                    self.assertTrue(source_path.is_file(), f"missing alteration source: {migration}")
                    source = source_path.read_text(encoding="utf-8")
                    self.assertIn(table, source, f"alteration source does not reference {table}")

    def test_scope_is_not_mislabeled_as_final_schema_acceptance(self):
        self.assertEqual(
            self.scope["scope_status"],
            "SOURCE_CREATE_INVENTORY_IDENTIFIED; FINAL_SCHEMA_DEFINITION_NOT_CHECKED",
        )
        self.assertTrue(
            all(entry["definition_state"] == "NOT_CHECKED" for entry in self.scope["tables"].values())
        )


if __name__ == "__main__":
    unittest.main()
