#!/usr/bin/env python3
"""Repository-only regression tests for production workflow isolation."""
from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[2]
WORKFLOWS = ROOT / ".github" / "workflows"
PRODUCTION_MARKERS = (
    "appleboy/ssh-action",
    "HOSTINGER_SSH_",
    "OFFERING_GAP_DB_",
    "INSTITUTES_DIAGNOSTICS_TOKEN",
    "INSTITUTES_PRIVATE_REPORTS_TOKEN",
    "institutes:bootstrap-agent",
    "institutes:monitor-changes",
    "php artisan migrate",
)

class ProductionWorkflowIsolationTest(unittest.TestCase):
    def test_production_workflows_are_manual_only(self):
        for path in WORKFLOWS.glob("*.yml"):
            text = path.read_text(encoding="utf-8")
            if not any(marker in text for marker in PRODUCTION_MARKERS):
                continue
            with self.subTest(workflow=path.name):
                trigger = text.split("\npermissions:", 1)[0]
                self.assertIn("workflow_dispatch:", trigger)
                for forbidden in ("  push:", "  pull_request:", "  schedule:", "  workflow_run:"):
                    self.assertNotIn(forbidden, trigger)
                self.assertRegex(text, r"(?m)^    environment: (production-readonly-diagnostics|production-operations|offering-gap-readonly-audit)$")
                self.assertIn("group: estudent-production-access", text)
                self.assertIn("TARGET_SHA", text)
                self.assertIn("scripts/ci/validate-production-dispatch.sh", text)

    def test_ci_workflows_do_not_use_production_credentials_or_ssh(self):
        for name in ("test.yml", "laravel-ci.yml", "isolated-mysql-approval-migration-validation.yml",
                     "project-controller.yml", "project-command-orchestrator.yml",
                     "build-frontend-assets.yml", "institution-import-preflight.yml"):
            path = WORKFLOWS / name
            text = path.read_text(encoding="utf-8")
            with self.subTest(workflow=name):
                self.assertNotIn("appleboy/ssh-action", text)
                self.assertNotRegex(text, r"secrets\.(HOSTINGER_SSH_|OFFERING_GAP_DB_|INSTITUTES_DIAGNOSTICS_TOKEN|INSTITUTES_PRIVATE_REPORTS_TOKEN)")

    def test_production_guard_rejects_invalid_sha_and_unapproved_event(self):
        guard = (ROOT / "scripts/ci/validate-production-dispatch.sh").read_text(encoding="utf-8")
        self.assertIn('GITHUB_EVENT_NAME:-}" == "workflow_dispatch"', guard)
        self.assertIn('GITHUB_REF:-}" == "refs/heads/main"', guard)
        self.assertIn(r'\[\[ "$target" =~ ^[0-9a-f]{40}$ \]\]', guard)
        self.assertIn('target" == "$GITHUB_SHA"', guard)
        self.assertIn('confirmation" == "$expected"', guard)
        self.assertIn('target" == "$main_sha"', guard)

    def test_production_workflows_have_no_automatic_or_pr_handoffs(self):
        for path in WORKFLOWS.glob("*.yml"):
            text = path.read_text(encoding="utf-8")
            if any(marker in text for marker in PRODUCTION_MARKERS):
                with self.subTest(workflow=path.name):
                    self.assertNotIn("workflow_run:", text)
                    self.assertNotIn("pull_request:", text)

if __name__ == "__main__":
    unittest.main()
