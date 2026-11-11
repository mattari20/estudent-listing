from __future__ import annotations

import json
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
MANIFEST = ROOT / "database/schema-contract/phase1d2b-final-schema-manifest.json"
PROVENANCE = ROOT / "database/schema-contract/phase1d2b-migration-object-provenance.json"
SCOPE = ROOT / "database/schema-contract/phase1d2b-schema-scope-inventory.json"


class Phase1D2BFinalContractTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.manifest = json.loads(MANIFEST.read_text(encoding="utf-8"))
        cls.provenance = json.loads(PROVENANCE.read_text(encoding="utf-8"))
        cls.scope = json.loads(SCOPE.read_text(encoding="utf-8"))

    def test_all_63_scope_entries_have_manifest_records(self):
        self.assertEqual(self.manifest["scope"]["total_table_count"], 63)
        self.assertEqual(set(self.manifest["tables"]), set(self.scope["tables"]))
        self.assertEqual(len(self.manifest["tables"]), 63)

    def test_framework_migration_ledger_is_separately_classified(self):
        ledger = self.manifest["tables"]["migrations"]
        self.assertEqual(ledger["ownership"], "framework_managed")
        self.assertEqual(ledger["managed_by"], "Laravel DatabaseMigrationRepository")
        self.assertEqual([c["name"] for c in ledger["columns"]], ["id", "migration", "batch"])
        self.assertEqual([c["type"] for c in ledger["columns"]], ["int unsigned", "varchar(255)", "int"])
        self.assertEqual(ledger["definition_state"], "COMPLETE")
        self.assertEqual(ledger["framework_version"], "laravel/framework v13.33.0")
        self.assertEqual(ledger["table_options"]["state"], "INHERITED_CONNECTION_DEFAULT")
        self.assertEqual(ledger["indexes"][0]["name"], "PRIMARY")

    def test_all_97_migration_sources_are_accounted_for(self):
        entries = self.provenance["migrations"]
        self.assertEqual(self.provenance["migration_directory_file_count"], 97)
        self.assertEqual(self.provenance["accounted_migration_count"], 97)
        self.assertEqual(len(entries), 97)
        self.assertEqual(len({entry["migration"] for entry in entries}), 97)
        for entry in entries:
            with self.subTest(migration=entry["migration"]):
                self.assertTrue((ROOT / entry["source_file"]).is_file())
                self.assertTrue(entry["git_blob_sha"])
                self.assertIn(entry["object_mapping_state"], {"PARTIAL", "NOT_CHECKED", "REVIEWED_NOOP"})
                if entry["object_mapping_state"] == "REVIEWED_NOOP":
                    self.assertEqual(entry["operation_review"]["up_effect"], "NONE")
                    self.assertEqual(entry["operation_review"]["down_effect"], "NONE")
                    self.assertEqual(entry["unmapped_operation_review"], "COMPLETE_NOOP")
                else:
                    self.assertEqual(entry["unmapped_operation_review"], "REQUIRED")

    def test_superseded_empty_migrations_are_explicitly_reviewed_as_noop(self):
        entries = self.provenance["migrations"]
        reviewed = [entry for entry in entries if entry["object_mapping_state"] == "REVIEWED_NOOP"]
        self.assertEqual(len(reviewed), 23)
        self.assertTrue(all(entry["operation_review"]["state"] == "REVIEWED_NOOP" for entry in reviewed))
        self.assertTrue(all(entry["data_backfills_or_transformations"] == "NONE" for entry in reviewed))

    def test_provenance_mapping_does_not_claim_unverified_object_operations(self):
        for entry in self.provenance["migrations"]:
            self.assertEqual(entry["columns_created"], [])
            self.assertEqual(entry["columns_changed"], [])
            self.assertEqual(entry["indexes_created"], [])
            self.assertEqual(entry["foreign_keys_created"], [])
            self.assertEqual(entry["triggers_installed"], [])
            if entry["object_mapping_state"] != "REVIEWED_NOOP":
                self.assertEqual(entry["data_backfills_or_transformations"], "NOT_CHECKED")

    def test_no_application_table_is_falsely_marked_complete(self):
        application = [v for v in self.manifest["tables"].values() if v["ownership"] == "application"]
        self.assertEqual(len(application), 62)
        self.assertTrue(all(v["definition_state"] == "NOT_CHECKED" for v in application))
        self.assertEqual(self.manifest["definition_coverage"]["complete_application_tables"], 0)


if __name__ == "__main__":
    unittest.main()
