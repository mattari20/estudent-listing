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

    def test_migration_directory_create_targets_are_fully_accounted_for(self):
        migration_files = sorted(self.migration_dir.glob("*.php"))
        self.assertEqual(len(migration_files), self.scope["migration_directory_file_count"])
        discovered = set()

        for source_path in migration_files:
            source = source_path.read_text(encoding="utf-8")
            discovered.update(re.findall(r"Schema::create\s*\(\s*['\\\"]([^'\\\"]+)", source))
            discovered.update(re.findall(r"createTableIfMissing\s*\(\s*['\\\"]([^'\\\"]+)", source))
            discovered.update(re.findall(r"CREATE TABLE(?: IF NOT EXISTS)?\s+\`?([a-zA-Z0-9_]+)", source, re.I))

            if re.search(r"Schema::create\s*\(\s*self::TABLE\s*,", source):
                constant = re.search(r"private const TABLE\s*=\s*['\\\"]([^'\\\"]+)", source)
                self.assertIsNotNone(constant, f"dynamic TABLE create needs a literal source constant: {source_path.name}")
                discovered.add(constant.group(1))

        expected = {
            table for table, entry in self.scope["tables"].items()
            if entry["ownership"] == "application"
        }
        self.assertEqual(discovered, expected, "migration-created tables must match the reviewed source scope inventory")

    def test_alteration_migration_paths_exist_and_reference_their_table(self):
        for table, entry in self.scope["tables"].items():
            for migration in entry.get("alteration_migrations", []):
                with self.subTest(table=table, migration=migration):
                    source_path = ROOT / migration
                    self.assertTrue(source_path.is_file(), f"missing alteration source: {migration}")
                    source = source_path.read_text(encoding="utf-8")
                    self.assertIn(table, source, f"alteration source does not reference {table}")

    def test_scope_inventory_records_final_schema_acceptance(self):
        self.assertEqual(
            self.scope["scope_status"],
            "FINAL_SCHEMA_DEFINITION_COMPLETE; MYSQL_8_4_AND_MARIADB_11_8_RECONCILIATION_PASS",
        )
        self.assertTrue(
            all(entry["definition_state"] == "COMPLETE" for entry in self.scope["tables"].values())
        )
        self.assertEqual(self.scope["verified_main_baseline_sha"], "d363fad00c15d6892677e452d5b1f383cbe892de")
        self.assertIn("97/97 MIGRATIONS OBJECT PASS", self.scope["alteration_mapping_status"])


if __name__ == "__main__":
    unittest.main()
