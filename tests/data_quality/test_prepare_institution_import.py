import csv
import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[2] / "scripts" / "data-quality" / "prepare_institution_import.py"
sys.path.insert(0, str(SCRIPT.parent))
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

    def test_evidence_sidecar_flags_incomplete_or_non_https_sources(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            source = root / "candidates.csv"
            clean = root / "clean.csv"
            report = root / "report.json"
            evidence = root / "evidence.csv"
            with source.open("w", encoding="utf-8", newline="") as f:
                writer = csv.DictWriter(f, fieldnames=[
                    "name", "official_name", "short_name", "slug", "institution_type", "status",
                    "official_source_url", "source_excerpt"])
                writer.writeheader()
                writer.writerow({"name": "Verified University", "slug": "verified-university",
                                 "official_source_url": "https://example.edu/about",
                                 "source_excerpt": "A copied official statement with enough detail."})
                writer.writerow({"name": "Missing Evidence University", "slug": "missing-evidence-university",
                                 "official_source_url": "https://example.edu/about"})
                writer.writerow({"name": "Unsafe URL University", "slug": "unsafe-url-university",
                                 "official_source_url": "http://example.edu/about",
                                 "source_excerpt": "This excerpt is long enough but URL is not HTTPS."})
            result = subprocess.run([
                sys.executable, str(SCRIPT), "--input", str(source), "--output", str(clean),
                "--report", str(report), "--evidence-output", str(evidence)
            ], capture_output=True, text=True, check=False)
            self.assertEqual(result.returncode, 0, result.stderr)
            with evidence.open(encoding="utf-8", newline="") as f:
                rows = list(csv.DictReader(f))
            self.assertEqual([r["evidence_preflight"] for r in rows],
                             ["ready_for_review", "missing_or_incomplete", "missing_or_incomplete"])
            with clean.open(encoding="utf-8", newline="") as f:
                clean_rows = list(csv.DictReader(f))
            self.assertEqual(list(clean_rows[0].keys()),
                             ["name", "official_name", "short_name", "slug", "institution_type", "status"])
            report_data = json.loads(report.read_text(encoding="utf-8"))
            self.assertEqual(report_data["evidence_rows_ready_for_review"], 1)
            self.assertEqual(report_data["evidence_rows_missing_or_incomplete"], 2)

    def test_without_canonical_export_makes_no_database_check_claim(self):
        rows = [{"name": "New Test University", "slug": "new-test-university"}]
        accepted, excluded = prepare(rows, None)
        self.assertEqual(len(accepted), 1)
        self.assertEqual(excluded, [])


if __name__ == "__main__":
    unittest.main()
