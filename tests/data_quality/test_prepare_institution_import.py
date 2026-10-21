import unittest
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / "scripts" / "data-quality"))
from prepare_institution_import import prepare


class PrepareInstitutionImportTests(unittest.TestCase):
    def test_exact_duplicate_inside_candidate_file_keeps_one(self):
        rows = [
            {"name": "Bahria University", "official_name": "Bahria University", "slug": "bahria-university"},
            {"name": "Bahria University", "official_name": "Bahria University", "slug": "bahria-university"},
        ]
        accepted, excluded = prepare(rows, None)
        self.assertEqual(len(accepted), 1)
        self.assertEqual(excluded[0]["decision"], "exclude_duplicate_in_file")

    def test_canonical_alias_match_is_excluded(self):
        rows = [{"name": "Lahore University of Management Sciences",
                 "official_name": "Lahore University of Management Sciences",
                 "slug": "lahore-university-of-management-sciences"}]
        canonical = [{"id": "8", "name": "LUMS",
                      "official_name": "Lahore University of Management Sciences",
                      "short_name": "LUMS", "slug": "lums"}]
        accepted, excluded = prepare(rows, canonical)
        self.assertEqual(len(accepted), 0)
        self.assertEqual(excluded[0]["decision"], "exclude_existing_match")

    def test_without_canonical_export_makes_no_database_check_claim(self):
        rows = [{"name": "New Test University", "slug": "new-test-university"}]
        accepted, excluded = prepare(rows, None)
        self.assertEqual(len(accepted), 1)
        self.assertEqual(excluded, [])


if __name__ == "__main__":
    unittest.main()
