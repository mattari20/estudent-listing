from __future__ import annotations

import json
import re
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
PROVENANCE_PATH = ROOT / "database/schema-contract/phase1d2b-table-source-provenance.json"
DIAGNOSTIC_PATH = ROOT / "scripts/phase1c/readonly-schema-diagnostic.php"


class Phase1D2BSourceProvenanceTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.manifest = json.loads(PROVENANCE_PATH.read_text(encoding="utf-8"))
        cls.diagnostic = DIAGNOSTIC_PATH.read_text(encoding="utf-8")
        cls.migration_dir = ROOT / "database/migrations"

    def test_expanded_manifest_contains_diagnostic_allowlist(self):
        match = re.search(r"private const TABLES = \[(.*?)\];", self.diagnostic, re.S)
        self.assertIsNotNone(match, "diagnostic TABLES allowlist must remain discoverable")
        allowlist = set(re.findall(r"'([a-zA-Z0-9_]+)'", match.group(1)))
        manifest_tables = set(self.manifest["tables"])

        # The diagnostic allowlist is intentionally narrower than the
        # expanded source-provenance inventory. It must be fully represented,
        # but the expanded inventory must not be reduced to the old allowlist.
        self.assertTrue(allowlist, "diagnostic TABLES allowlist must not be empty")
        self.assertTrue(allowlist.issubset(manifest_tables))
        self.assertEqual(self.manifest["table_count"], 63)
        self.assertEqual(self.manifest["application_table_count"], 62)
        self.assertEqual(self.manifest["framework_managed_table_count"], 1)
        self.assertEqual(len(manifest_tables), 63)

        ledger = self.manifest["tables"]["migrations"]
        self.assertEqual(ledger["managed_by"], "Laravel DatabaseMigrationRepository")
        self.assertEqual(ledger["create"], [])
        self.assertEqual(ledger["alter"], [])

    def test_every_provenance_source_file_exists(self):
        for table, provenance in self.manifest["tables"].items():
            with self.subTest(table=table):
                create_sources = provenance.get("create", [])
                alter_sources = provenance.get("alter", [])
                if table == "migrations":
                    self.assertEqual(create_sources, [])
                    self.assertEqual(alter_sources, [])
                    continue
                self.assertTrue(create_sources, f"{table} must have a create source")
                for filename in create_sources + alter_sources:
                    self.assertTrue((self.migration_dir / filename).is_file(), f"missing source file: {filename}")

    def test_create_sources_contain_table_creation_contract(self):
        for table, provenance in self.manifest["tables"].items():
            if table == "migrations":
                continue
            for filename in provenance["create"]:
                source = (self.migration_dir / filename).read_text(encoding="utf-8")
                with self.subTest(table=table, migration=filename):
                    self.assertIn(table, source)
                    self.assertRegex(source, r"Schema::create|createTableIfMissing")

    def test_alter_sources_reference_their_mapped_table(self):
        for table, provenance in self.manifest["tables"].items():
            for filename in provenance.get("alter", []):
                source = (self.migration_dir / filename).read_text(encoding="utf-8")
                with self.subTest(table=table, migration=filename):
                    self.assertIn(table, source)


if __name__ == "__main__":
    unittest.main()
