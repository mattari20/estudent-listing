import os
import tempfile
import unittest
import sys
from pathlib import Path
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / "scripts"))
import project_controller as controller


class ProjectControllerTests(unittest.TestCase):
    def test_local_governance_requires_protocol_and_exactly_five_step_headings(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "docs").mkdir()
            (root / "docs/PROJECT_MASTER_TRACKER.md").write_text(
                "\n".join("### " + step for step in controller.EXPECTED_STEPS),
                encoding="utf-8",
            )
            (root / "docs/AUTONOMOUS_STEP_EXECUTION_PROTOCOL.md").write_text(
                "mandatory test Never report 100% Do not start the next Step",
                encoding="utf-8",
            )
            report = controller.local_governance(root)
            self.assertTrue(report["master_protocol_present"])
            self.assertEqual(report["phase2_step_headings_found"], 5)
            self.assertTrue(report["all_expected_step_titles_found"])
            self.assertTrue(report["protocol_requires_verified_completion"])

    def test_missing_master_protocol_fails_closed(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "docs").mkdir()
            (root / "docs/PROJECT_MASTER_TRACKER.md").write_text(
                "\n".join("### " + step for step in controller.EXPECTED_STEPS),
                encoding="utf-8",
            )
            report = controller.local_governance(root)
            self.assertFalse(report["master_protocol_present"])
            self.assertFalse(report["protocol_requires_verified_completion"])

    def test_controller_is_read_only_and_never_closes_step_automatically(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "docs").mkdir()
            (root / "docs/PROJECT_MASTER_TRACKER.md").write_text(
                "\n".join("### " + step for step in controller.EXPECTED_STEPS),
                encoding="utf-8",
            )
            (root / "docs/AUTONOMOUS_STEP_EXECUTION_PROTOCOL.md").write_text(
                "mandatory test Never report 100% Do not start the next Step",
                encoding="utf-8",
            )
            with patch.dict(os.environ, {"GITHUB_REPOSITORY": "owner/repo"}, clear=False):
                def fake_api(path):
                    if path.endswith("/pulls?state=open&per_page=100"):
                        return []
                    if path.endswith("/actions/workflows?per_page=100"):
                        return {"workflows": [
                            {"id": 1, "path": ".github/workflows/scheduled-institute-discovery.yml"},
                            {"id": 2, "path": ".github/workflows/hostinger-readonly-diagnostics.yml"},
                            {"id": 3, "path": ".github/workflows/controlled-hostinger-code-deploy.yml"},
                        ]}
                    if path.endswith("/workflows/1/runs?per_page=1") or path.endswith("/workflows/2/runs?per_page=1"):
                        return {"workflow_runs": [{"id": 10, "name": "test", "status": "completed", "conclusion": "success", "html_url": "https://example.test/run"}]}
                    if path.endswith("/workflows/3/runs?per_page=1"):
                        return {"workflow_runs": [{"id": 11, "name": "deploy", "status": "completed", "conclusion": "success"}]}
                    raise AssertionError(path)
                report = controller.build_report(root, fake_api)
            self.assertFalse(report["autonomous_production_actions_enabled"])
            self.assertEqual(report["safety"]["production_writes"], "not performed")
            self.assertTrue(report["step1_closure_candidate"])
            self.assertEqual(report["status"], "REVIEW_REQUIRED")
            self.assertEqual(report["safety"]["workflow_dispatch"], "not performed")


if __name__ == "__main__":
    unittest.main()
