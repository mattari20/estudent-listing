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
                self.assertIn(
                    entry["object_mapping_state"],
                    {"PARTIAL", "NOT_CHECKED", "REVIEWED_NOOP", "SOURCE_REVIEWED_PARTIAL"},
                )
                if entry["object_mapping_state"] == "REVIEWED_NOOP":
                    self.assertEqual(entry["operation_review"]["up_effect"], "NONE")
                    self.assertEqual(entry["operation_review"]["down_effect"], "NONE")
                    self.assertEqual(entry["unmapped_operation_review"], "COMPLETE_NOOP")
                elif entry["object_mapping_state"] == "SOURCE_REVIEWED_PARTIAL":
                    self.assertEqual(entry["unmapped_operation_review"], "PARTIAL_REVIEW; FINAL_TABLE_CUMULATION_AND_OBJECT_RECONCILIATION_PENDING")
                    self.assertEqual(entry["operation_review"]["state"], "REVIEWED_SOURCE_EFFECTS")
                    self.assertTrue(entry["operation_review"]["source_evidence"])
                else:
                    self.assertEqual(entry["unmapped_operation_review"], "REQUIRED")

    def test_migration_review_checkpoint_counts_are_exact(self):
        entries = self.provenance["migrations"]
        reviewed_noop = [entry for entry in entries if entry["object_mapping_state"] == "REVIEWED_NOOP"]
        reviewed_non_empty = [entry for entry in entries if entry["object_mapping_state"] == "SOURCE_REVIEWED_PARTIAL"]
        remaining = [entry for entry in entries if entry["object_mapping_state"] in {"PARTIAL", "NOT_CHECKED"}]

        self.assertEqual(len(reviewed_noop), 23)
        self.assertEqual(len(reviewed_non_empty), 16)
        self.assertEqual(len(remaining), 58)
        self.assertEqual(self.provenance["source_review_counts"]["migration_file_count"], 97)
        self.assertEqual(self.provenance["source_review_counts"]["reviewed_noop_count"], 23)
        self.assertEqual(self.provenance["source_review_counts"]["reviewed_non_empty_count"], 16)
        self.assertEqual(self.provenance["source_review_counts"]["remaining_non_empty_count"], 58)

    def test_superseded_empty_migrations_remain_reviewed_noop(self):
        entries = self.provenance["migrations"]
        reviewed = [entry for entry in entries if entry["object_mapping_state"] == "REVIEWED_NOOP"]
        self.assertEqual(len(reviewed), 23)
        self.assertTrue(all(entry["operation_review"]["state"] == "REVIEWED_NOOP" for entry in reviewed))
        self.assertTrue(all(entry["data_backfills_or_transformations"] == "NONE" for entry in reviewed))

    def test_reviewed_operation_object_names_are_qualified(self):
        reviewed = [
            entry for entry in self.provenance["migrations"]
            if entry["object_mapping_state"] == "SOURCE_REVIEWED_PARTIAL"
        ]
        for entry in reviewed:
            with self.subTest(migration=entry["migration"]):
                self.assertTrue(entry["operation_review"]["source_evidence"])
                self.assertEqual(
                    entry["operation_review"]["related_objects"],
                    sorted(set(entry["tables_created"] + entry["tables_altered"])),
                )
                for collection in (
                    "columns_created",
                    "columns_changed",
                    "columns_removed",
                    "indexes_created",
                    "indexes_removed",
                ):
                    self.assertTrue(
                        all("." in name for name in entry[collection]),
                        f"{entry['migration']} has unqualified {collection}",
                    )

    def test_no_application_table_is_falsely_marked_complete(self):
        application = [v for v in self.manifest["tables"].values() if v["ownership"] == "application"]
        self.assertEqual(len(application), 62)
        self.assertTrue(all(v["definition_state"] == "NOT_CHECKED" for v in application))
        self.assertEqual(self.manifest["definition_coverage"]["complete_application_tables"], 0)
        self.assertEqual(self.manifest["definition_coverage"]["not_checked_application_tables"], 62)


if __name__ == "__main__":
    unittest.main()
