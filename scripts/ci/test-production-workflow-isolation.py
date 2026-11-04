#!/usr/bin/env python3
"""Structural regression tests for production workflow isolation."""
from pathlib import Path
import json
import os
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[2]
WORKFLOWS = ROOT / ".github" / "workflows"
YAML_READER = ROOT / "scripts" / "ci" / "read-workflow-yaml.rb"

PRODUCTION_SECRET_MARKERS = (
    "secrets.HOSTINGER_SSH_",
    "secrets.OFFERING_GAP_DB_",
    "secrets.INSTITUTES_DIAGNOSTICS_TOKEN",
    "secrets.INSTITUTES_PRIVATE_REPORTS_TOKEN",
    "secrets.INSTITUTES_ADMIN_RECOVERY_PASSWORD",
)
SSH_ACTION = "appleboy/ssh-action"


def parse_workflow(path):
    result = subprocess.run(
        ["ruby", str(YAML_READER), str(path)],
        check=True, text=True, capture_output=True,
    )
    document = json.loads(result.stdout)
    events = document.get("on", {})
    if not isinstance(events, dict):
        raise AssertionError(f"{path.name}: top-level on must be a mapping")
    jobs = document.get("jobs", {})
    if not isinstance(jobs, dict):
        raise AssertionError(f"{path.name}: jobs must be a mapping")
    return document, events, jobs


def flatten(value):
    if isinstance(value, dict):
        return " ".join(flatten(k) + " " + flatten(v) for k, v in value.items())
    if isinstance(value, list):
        return " ".join(flatten(item) for item in value)
    return str(value)


def access_configuration(value):
    """Return action/secret configuration only; ignore run scripts and labels."""
    if isinstance(value, dict):
        return {
            key: access_configuration(item)
            for key, item in value.items()
            if key not in {"run", "name", "description"}
        }
    if isinstance(value, list):
        return [access_configuration(item) for item in value]
    return value


def has_production_access_configuration(value):
    if isinstance(value, dict):
        if str(value.get("uses", "")).startswith(SSH_ACTION):
            return True
        return any(
            has_production_access_configuration(item)
            for key, item in value.items()
            if key not in {"run", "name", "description"}
        )
    if isinstance(value, list):
        return any(has_production_access_configuration(item) for item in value)
    if isinstance(value, str):
        return any(marker in value for marker in PRODUCTION_SECRET_MARKERS)
    return False


def production_access_kind(job):
    if has_production_access_configuration(access_configuration(job)):
        return "production"
    return None


def first_production_step_index(steps):
    for index, step in enumerate(steps or []):
        if has_production_access_configuration(access_configuration(step)):
            return index
    return None


def validate_production_workflow(document, events):
    jobs = document.get("jobs", {})
    production_jobs = {
        name: job for name, job in jobs.items()
        if production_access_kind(job)
    }
    if not production_jobs:
        return []

    errors = []
    if set(events) != {"workflow_dispatch"}:
        errors.append(f"production workflow has non-manual triggers: {sorted(events)}")
    dispatch = events.get("workflow_dispatch", {})
    inputs = dispatch.get("inputs", {}) if isinstance(dispatch, dict) else {}
    for required in ("target_sha", "operation_reason", "confirm_production_operation"):
        if required not in inputs:
            errors.append(f"missing workflow_dispatch input: {required}")

    concurrency = document.get("concurrency", {})
    if not isinstance(concurrency, dict):
        errors.append("missing shared concurrency mapping")
    else:
        if concurrency.get("group") != "estudent-production-access":
            errors.append("wrong production concurrency group")
        if concurrency.get("cancel-in-progress") is not False:
            errors.append("production concurrency must not cancel active runs")
        if concurrency.get("queue") != "max":
            errors.append("production concurrency must queue pending runs")

    for name, job in production_jobs.items():
        if job.get("environment") not in {
            "production-readonly-diagnostics",
            "production-operations",
            "offering-gap-readonly-audit",
        }:
            errors.append(f"job {name} missing approved production environment")
        steps = job.get("steps", [])
        access_index = first_production_step_index(steps)
        if access_index is None:
            errors.append(f"job {name} has no identifiable production access step")
            continue
        guarded = any(
            "scripts/ci/validate-production-dispatch.sh" in flatten(step)
            for step in steps[:access_index]
        )
        if not guarded:
            errors.append(f"job {name} has no dispatch guard before production access")
    return errors


class ProductionWorkflowIsolationTest(unittest.TestCase):
    def test_ordinary_test_workflow_keeps_normal_ci_triggers(self):
        _, events, _ = parse_workflow(WORKFLOWS / "test.yml")
        self.assertIn("pull_request", events)
        self.assertIn("push", events)

    def test_production_ssh_job_is_rejected_on_push_or_pull_request(self):
        for event in ("push", "pull_request"):
            document = {
                "concurrency": {"group": "estudent-production-access",
                                "cancel-in-progress": False, "queue": "max"},
                "jobs": {"audit": {
                    "environment": "production-readonly-diagnostics",
                    "steps": [
                        {"run": "bash scripts/ci/validate-production-dispatch.sh"},
                        {"uses": "appleboy/ssh-action@v1"},
                    ],
                }},
            }
            errors = validate_production_workflow(document, {
                event: {"branches": ["main"]}
            })
            self.assertTrue(any("non-manual triggers" in error for error in errors))

    def test_production_database_job_is_rejected_on_push_or_pull_request(self):
        for event in ("push", "pull_request"):
            document = {
                "concurrency": {"group": "estudent-production-access",
                                "cancel-in-progress": False, "queue": "max"},
                "jobs": {"preflight": {
                    "environment": "offering-gap-readonly-audit",
                    "steps": [
                        {"run": "bash scripts/ci/validate-production-dispatch.sh"},
                        {"env": {"DB_PASSWORD": "${{ secrets.OFFERING_GAP_DB_PASSWORD }}"},
                         "run": "php artisan institutes:offering-gap-preflight"},
                    ],
                }},
            }
            errors = validate_production_workflow(document, {
                event: {"branches": ["main"]}
            })
            self.assertTrue(any("non-manual triggers" in error for error in errors))

    def test_non_workflow_test_file_mentioning_ssh_action_is_not_production_access(self):
        fixture_workflow = {
            "on": {"pull_request": {}, "push": {"branches": ["main"]}},
            "jobs": {"test": {"steps": [
                {"run": "assert the literal text appleboy/ssh-action is rejected in production jobs"}
            ]}},
        }
        self.assertFalse(any(
            production_access_kind(job)
            for job in fixture_workflow["jobs"].values()
        ))
        self.assertIn("pull_request", fixture_workflow["on"])
        self.assertIn("push", fixture_workflow["on"])

    def test_real_ssh_action_is_detected_as_production_access(self):
        self.assertEqual(
            production_access_kind({"steps": [{"uses": "appleboy/ssh-action@v1"}]}),
            "production",
        )

    def test_production_job_missing_environment_or_guard_is_rejected(self):
        document = {
            "concurrency": {"group": "estudent-production-access",
                            "cancel-in-progress": False, "queue": "max"},
            "jobs": {"audit": {
                "steps": [{"uses": "appleboy/ssh-action@v1"}],
            }},
        }
        events = {"workflow_dispatch": {"inputs": {
            "target_sha": {}, "operation_reason": {},
            "confirm_production_operation": {},
        }}}
        errors = validate_production_workflow(document, events)
        self.assertTrue(any("missing approved production environment" in e for e in errors))
        self.assertTrue(any("no dispatch guard" in e for e in errors))

    def test_all_actual_production_workflows_are_manual_and_guarded(self):
        for path in WORKFLOWS.glob("*.yml"):
            with self.subTest(workflow=path.name):
                document, events, jobs = parse_workflow(path)
                if not any(production_access_kind(job) for job in jobs.values()):
                    continue
                errors = validate_production_workflow(document, events)
                self.assertEqual(errors, [], "; ".join(errors))

    def test_ordinary_ci_jobs_have_no_production_credentials_or_ssh(self):
        ordinary = (
            "test.yml", "laravel-ci.yml",
            "isolated-mysql-approval-migration-validation.yml",
            "project-controller.yml", "project-command-orchestrator.yml",
            "build-frontend-assets.yml", "institution-import-preflight.yml",
        )
        for name in ordinary:
            document, _, jobs = parse_workflow(WORKFLOWS / name)
            for job_name, job in jobs.items():
                with self.subTest(workflow=name, job=job_name):
                    content = flatten(job)
                    self.assertNotIn(SSH_ACTION, content)
                    for marker in PRODUCTION_SECRET_MARKERS:
                        self.assertNotIn(marker, content)

    def test_public_runtime_workflows_do_not_run_on_push_or_pull_request(self):
        for name in ("public-browser-runtime-diagnostics.yml",
                     "institutes-public-monitor.yml"):
            _, events, _ = parse_workflow(WORKFLOWS / name)
            with self.subTest(workflow=name):
                self.assertNotIn("push", events)
                self.assertNotIn("pull_request", events)

    def test_dispatch_guard_rejects_invalid_inputs_and_workflow_run_events(self):
        guard = ROOT / "scripts" / "ci" / "validate-production-dispatch.sh"
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
            ("invalid SHA", {"TARGET_SHA": "not-a-sha"},
             "exactly 40 lowercase hexadecimal"),
            ("non-main ref", {"GITHUB_REF": "refs/heads/feature"},
             "must target refs/heads/main"),
            ("missing confirmation", {"CONFIRM_PRODUCTION_OPERATION": ""},
             "confirmation does not match"),
            ("mismatched SHA", {"TARGET_SHA": "b" * 40},
             "must equal the dispatch event SHA"),
            ("short reason", {"OPERATION_REASON": "no"},
             "8-128 safe ASCII characters"),
            ("log injection reason", {"OPERATION_REASON": "ticket-123\\nforged=true"},
             "8-128 safe ASCII characters"),
        ]
        for label, override, expected in cases:
            env = {**os.environ, **base, **override}
            result = subprocess.run(
                ["bash", str(guard)], env=env, text=True, capture_output=True
            )
            with self.subTest(case=label):
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(expected, result.stderr)

        for source, branch, conclusion, sha in (
            ("Untrusted workflow", "main", "success", "a" * 40),
            ("Controlled Official-Source Refresh Migration", "feature", "success", "a" * 40),
            ("Controlled Official-Source Refresh Migration", "main", "failure", "a" * 40),
            ("Controlled Official-Source Refresh Migration", "main", "success", "b" * 40),
        ):
            env = {**os.environ, **base, "GITHUB_EVENT_NAME": "workflow_run",
                   "WORKFLOW_RUN_NAME": source, "WORKFLOW_RUN_BRANCH": branch,
                   "WORKFLOW_RUN_CONCLUSION": conclusion, "WORKFLOW_RUN_HEAD_SHA": sha}
            result = subprocess.run(
                ["bash", str(guard)], env=env, text=True, capture_output=True
            )
            with self.subTest(source=source, branch=branch,
                              conclusion=conclusion, sha=sha):
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("only workflow_dispatch is accepted", result.stderr)


if __name__ == "__main__":
    unittest.main()
