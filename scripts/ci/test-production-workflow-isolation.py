#!/usr/bin/env python3
"""Repository-only regression tests for production workflow isolation."""
from pathlib import Path
import os
import re
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[2]
WORKFLOWS = ROOT / ".github" / "workflows"
OWNER_CONFIG_EXCEPTIONS = {"secure-institutes-admin-recovery.yml"}

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
                if path.name not in OWNER_CONFIG_EXCEPTIONS:
                    self.assertRegex(text, r"(?m)^    environment: (production-readonly-diagnostics|production-operations|offering-gap-readonly-audit)$")
                    self.assertIn("group: estudent-production-access", text)
                else:
                    # This legacy account-recovery workflow is deliberately held for owner protection configuration.
                    self.assertIn("RECOVER-EXISTING-ADMIN-1", text)
                if path.name not in OWNER_CONFIG_EXCEPTIONS:
                    self.assertIn("      target_sha:", trigger)
                    self.assertIn("TARGET_SHA", text)
                    self.assertIn("scripts/ci/validate-production-dispatch.sh", text)
                else:
                    self.assertNotIn("TARGET_SHA", text)
                    self.assertNotIn("environment:", text)

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
        self.assertIn('[[ "$target" =~ ^[0-9a-f]{40}$ ]]', guard)
        self.assertIn('target" == "$GITHUB_SHA"', guard)
        self.assertIn('confirmation" == "$expected"', guard)
        self.assertIn('target" == "$main_sha"', guard)

    def test_public_production_smoke_checks_are_not_push_or_pr_triggered(self):
        for name in ("public-browser-runtime-diagnostics.yml", "institutes-public-monitor.yml"):
            text = (WORKFLOWS / name).read_text(encoding="utf-8")
            trigger = text.split("\\npermissions:", 1)[0]
            with self.subTest(workflow=name):
                self.assertNotIn("  push:", trigger)
                self.assertNotIn("  pull_request:", trigger)

    def test_dispatch_guard_rejects_invalid_inputs_and_all_workflow_run_handoffs(self):
        guard = ROOT / "scripts/ci/validate-production-dispatch.sh"
        base = {
            "GITHUB_EVENT_NAME": "workflow_dispatch",
            "GITHUB_REF": "refs/heads/main",
            "GITHUB_SHA": "a" * 40,
            "TARGET_SHA": "a" * 40,
            "OPERATION_REASON": "change-ticket-123",
            "CONFIRM_PRODUCTION_OPERATION": "AUTHORIZE-READONLY-DIAGNOSTICS",
            "EXPECTED_CONFIRMATION": "AUTHORIZE-READONLY-DIAGNOSTICS",
        }
        cases = [
            ("invalid SHA", {"TARGET_SHA": "not-a-sha"}, "exactly 40 lowercase hexadecimal"),
            ("non-main ref", {"GITHUB_REF": "refs/heads/feature"}, "must target refs/heads/main"),
            ("missing confirmation", {"CONFIRM_PRODUCTION_OPERATION": ""}, "confirmation does not match"),
            ("mismatched SHA", {"TARGET_SHA": "b" * 40}, "must equal the dispatch event SHA"),
        ]
        for label, override, expected in cases:
            env = {**os.environ, **base, **override}
            result = subprocess.run(["bash", str(guard)], env=env, text=True, capture_output=True)
            with self.subTest(case=label):
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(expected, result.stderr)

        # No workflow_run handoff is accepted, regardless of source workflow,
        # branch, conclusion, artifact metadata, or SHA supplied by the event.
        for source, branch, conclusion, sha in (
            ("Untrusted workflow", "main", "success", "a" * 40),
            ("Controlled Official-Source Refresh Migration", "feature", "success", "a" * 40),
            ("Controlled Official-Source Refresh Migration", "main", "failure", "a" * 40),
            ("Controlled Official-Source Refresh Migration", "main", "success", "b" * 40),
        ):
            env = {**os.environ, **base, "GITHUB_EVENT_NAME": "workflow_run",
                   "WORKFLOW_RUN_NAME": source, "WORKFLOW_RUN_BRANCH": branch,
                   "WORKFLOW_RUN_CONCLUSION": conclusion, "WORKFLOW_RUN_HEAD_SHA": sha}
            result = subprocess.run(["bash", str(guard)], env=env, text=True, capture_output=True)
            with self.subTest(source=source, branch=branch, conclusion=conclusion, sha=sha):
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("only workflow_dispatch is accepted", result.stderr)

    def test_production_workflows_have_no_automatic_or_pr_handoffs(self):
        for path in WORKFLOWS.glob("*.yml"):
            text = path.read_text(encoding="utf-8")
            if any(marker in text for marker in PRODUCTION_MARKERS):
                with self.subTest(workflow=path.name):
                    self.assertNotIn("workflow_run:", text)
                    self.assertNotIn("pull_request:", text)

if __name__ == "__main__":
    unittest.main()
