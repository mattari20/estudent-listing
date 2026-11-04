#!/usr/bin/env python3
"""Structural regression tests for production workflow isolation."""
from pathlib import Path
import json
import os
import re
import subprocess
import tempfile
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
RECOVERY_WORKFLOW = WORKFLOWS / "secure-institutes-admin-recovery.yml"
ADMIN_RECOVERY_NAME = re.compile(r"(?:admin.*recover|recover.*admin)", re.IGNORECASE)
GUARD_COMMAND = re.compile(r"^\s*(?:bash\s+)?scripts/ci/validate-production-dispatch\.sh(?:\s|$)", re.MULTILINE)


def workflow_files():
    """Return every supported GitHub Actions YAML workflow file."""
    return sorted(set(WORKFLOWS.glob("*.yml")) | set(WORKFLOWS.glob("*.yaml")))
PRODUCTION_REMOTE_COMMAND = re.compile(r"\b(?:ssh|scp|sftp)\s+(?:-[A-Za-z][^\s]*\s+)*[^\s]+")
PRODUCTION_DATABASE_COMMAND = re.compile(r"\b(?:mysql|mariadb|psql)\b(?=[^\n]*(?:--host(?:=|\s+)(?!127\.0\.0\.1\b|localhost\b)|-h\s+(?!127\.0\.0\.1\b|localhost\b)))", re.IGNORECASE)
PRODUCTION_ARTISAN_COMMAND = re.compile(r"\b(?:php(?:[0-9]+(?:\.[0-9]+)*)?\s+artisan\b|\$[A-Z_][A-Z0-9_]*\s+artisan\b)")
PRODUCTION_SECRET_NAME_PARTS = (
    "HOSTINGER_SSH_", "OFFERING_GAP_DB_", "INSTITUTES_DIAGNOSTICS_TOKEN",
    "INSTITUTES_PRIVATE_REPORTS_TOKEN", "INSTITUTES_ADMIN_RECOVERY_PASSWORD",
    "PRODUCTION", "PROD_DB", "DATABASE", "DB_HOST", "DB_PASSWORD",
    "DB_USERNAME", "SSH_PRIVATE_KEY",
)
PRODUCTION_SECRET_REFERENCE = re.compile(r"\$\{\{\s*secrets\.([A-Z0-9_]+)\s*\}\}")


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
    """Retain executable run scripts; ignore only descriptive labels."""
    if isinstance(value, dict):
        return {
            key: access_configuration(item)
            for key, item in value.items()
            if key not in {"name", "description"}
        }
    if isinstance(value, list):
        return [access_configuration(item) for item in value]
    return value


def has_production_access_configuration(value):
    """Detect production credentials and remote-access commands in parsed YAML."""
    if isinstance(value, dict):
        if str(value.get("uses", "")).startswith(SSH_ACTION):
            return True
        run_script = value.get("run")
        if isinstance(run_script, str) and (
            PRODUCTION_REMOTE_COMMAND.search(run_script)
            or PRODUCTION_DATABASE_COMMAND.search(run_script)
        ):
            return True
        return any(
            has_production_access_configuration(item)
            for key, item in value.items()
            if key not in {"run", "name", "description"}
        )
    if isinstance(value, list):
        return any(has_production_access_configuration(item) for item in value)
    if isinstance(value, str):
        if any(marker in value for marker in PRODUCTION_SECRET_MARKERS):
            return True
        for match in PRODUCTION_SECRET_REFERENCE.finditer(value):
            secret_name = match.group(1)
            if any(part in secret_name for part in PRODUCTION_SECRET_NAME_PARTS):
                return True
    return False


def has_production_artisan_command(value):
    if isinstance(value, dict):
        run_script = value.get("run")
        if isinstance(run_script, str) and PRODUCTION_ARTISAN_COMMAND.search(run_script):
            return True
        return any(
            has_production_artisan_command(item)
            for key, item in value.items()
            if key not in {"run", "name", "description"}
        )
    if isinstance(value, list):
        return any(has_production_artisan_command(item) for item in value)
    return False


def production_access_kind(job):
    configuration = access_configuration(job)
    if has_production_access_configuration(configuration):
        return "production"
    if job.get("environment") in {
        "production-readonly-diagnostics",
        "production-operations",
        "offering-gap-readonly-audit",
    } and has_production_artisan_command(configuration):
        return "production"
    return None


def first_production_step_index(steps, include_artisan=False):
    for index, step in enumerate(steps or []):
        configuration = access_configuration(step)
        if has_production_access_configuration(configuration):
            return index
        if include_artisan and has_production_artisan_command(configuration):
            return index
    return None


def validate_production_workflow(document, events):
    jobs = document.get("jobs", {})
    production_jobs = {
        name: job for name, job in jobs.items()
        if production_access_kind(job)
    }
    errors = []
    if has_production_access_configuration(access_configuration(document.get("env", {}))):
        errors.append("workflow exposes production credentials at workflow scope")
    if not production_jobs:
        return errors
    if set(events) != {"workflow_dispatch"}:
        errors.append(f"production workflow has non-manual triggers: {sorted(events)}")
    dispatch = events.get("workflow_dispatch", {})
    inputs = dispatch.get("inputs", {}) if isinstance(dispatch, dict) else {}
    for required in ("target_sha", "operation_reason", "confirm_production_operation"):
        input_config = inputs.get(required)
        if not isinstance(input_config, dict):
            errors.append(f"missing workflow_dispatch input: {required}")
        elif input_config.get("required") is not True or input_config.get("type") != "string":
            errors.append(f"workflow_dispatch input {required} must be a required string")

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
        if has_production_access_configuration(access_configuration(job.get("env", {}))):
            errors.append(f"job {name} exposes production credentials at job scope before its guard")
        if job.get("environment") not in {
            "production-readonly-diagnostics",
            "production-operations",
            "offering-gap-readonly-audit",
        }:
            errors.append(f"job {name} missing approved production environment")
        steps = job.get("steps", [])
        access_index = first_production_step_index(
            steps,
            include_artisan=job.get("environment") in {
                "production-readonly-diagnostics",
                "production-operations",
                "offering-gap-readonly-audit",
            },
        )
        if access_index is None:
            errors.append(f"job {name} has no identifiable production access step")
            continue

        guard_index = next((
            index for index, step in enumerate(steps[:access_index])
            if isinstance(step, dict)
            and isinstance(step.get("run"), str)
            and GUARD_COMMAND.search(step["run"].strip())
        ), None)
        if guard_index is None:
            errors.append(f"job {name} has no dispatch guard before production access")
            continue

        guard_step = steps[guard_index]
        guard_env = guard_step.get("env", {}) if isinstance(guard_step, dict) else {}
        required_bindings = {
            "TARGET_SHA": "inputs.target_sha",
            "OPERATION_REASON": "inputs.operation_reason",
            "CONFIRM_PRODUCTION_OPERATION": "inputs.confirm_production_operation",
        }
        for variable, binding in required_bindings.items():
            if binding not in str(guard_env.get(variable, "")):
                errors.append(f"job {name} guard does not bind {variable} to {binding}")
        expected_confirmation = str(guard_env.get("EXPECTED_CONFIRMATION", "")).strip()
        if (not re.fullmatch(r"AUTHORIZE-[A-Z0-9-]{8,}", expected_confirmation)
                or expected_confirmation == "AUTHORIZE-PRODUCTION-OPERATION"):
            errors.append(f"job {name} guard lacks a unique operation-specific confirmation phrase")

        if "confirm_discovery" in inputs:
            if inputs["confirm_discovery"].get("required") is not True or inputs["confirm_discovery"].get("type") != "string":
                errors.append("discovery requires a dedicated confirm_discovery string input")
            if guard_env.get("OPERATION_CONFIRMATION") != "${{ inputs.confirm_discovery }}":
                errors.append(f"job {name} guard does not bind the dedicated discovery confirmation")
            if guard_env.get("EXPECTED_OPERATION_CONFIRMATION") != "AUTHORIZE-GOVERNED-INSTITUTE-DISCOVERY":
                errors.append(f"job {name} guard has the wrong discovery confirmation value")
        elif "confirm_discovery" in guard_env:
            errors.append(f"job {name} uses discovery confirmation without a matching workflow input")

        for prior_step in steps[:guard_index]:
            prior_env = prior_step.get("env", {}) if isinstance(prior_step, dict) else {}
            if has_production_access_configuration(access_configuration(prior_env)):
                errors.append(f"job {name} exposes production credentials before its dispatch guard")

        checkout_index = next((
            index for index, step in enumerate(steps[:guard_index])
            if isinstance(step, dict)
            and step.get("uses", "").startswith("actions/checkout")
            and step.get("with", {}).get("ref") == "${{ github.sha }}"
            and str(step.get("with", {}).get("fetch-depth")) == "0"
        ), None)
        if checkout_index is None:
            errors.append(f"job {name} must fetch the exact event SHA with full history before the guard")
    return errors


class ProductionWorkflowIsolationTest(unittest.TestCase):
    def test_retired_administrator_recovery_workflow_is_absent(self):
        self.assertFalse(
            RECOVERY_WORKFLOW.exists(),
            "unsafe administrator-recovery workflow must remain retired",
        )

    def test_unsafe_administrator_recovery_ssh_reintroduction_is_rejected(self):
        unsafe = {
            "concurrency": {
                "group": "institutes-existing-admin-recovery",
                "cancel-in-progress": False,
            },
            "jobs": {"recover": {
                "steps": [{
                    "uses": "appleboy/ssh-action@v1.2.0",
                    "env": {
                        "RECOVERY_PASSWORD": "${{ secrets.INSTITUTES_ADMIN_RECOVERY_PASSWORD }}",
                        "HOST": "${{ secrets.HOSTINGER_SSH_HOST }}",
                    },
                }],
            }},
        }
        events = {"workflow_dispatch": {"inputs": {}}}
        self.assertEqual(production_access_kind(unsafe["jobs"]["recover"]), "production")
        errors = validate_production_workflow(unsafe, events)
        self.assertTrue(any("wrong production concurrency group" in error for error in errors))
        self.assertTrue(any("missing approved production environment" in error for error in errors))
        self.assertTrue(any("no dispatch guard" in error for error in errors))
        self.assertTrue(any("missing workflow_dispatch input" in error for error in errors))

    def test_no_administrator_recovery_ssh_workflow_is_reintroduced(self):
        reintroduced = []
        for path in workflow_files():
            if not ADMIN_RECOVERY_NAME.search(path.stem):
                continue
            document, _, jobs = parse_workflow(path)
            if any(production_access_kind(job) for job in jobs.values()):
                reintroduced.append(path.name)
        self.assertEqual(reintroduced, [])

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
        # The test file itself contains the SSH action marker as a literal.
        # That text is not a YAML uses field and must never classify CI as production.
        self_job = {"steps": [{"run": "echo appleboy/ssh-action is only a literal reference"}]}
        self.assertIsNone(production_access_kind(self_job))

    def test_remote_ssh_shell_command_is_detected_as_production_access(self):
        job = {"steps": [{"run": "timeout 60s ssh -i key operator@example.invalid true"}]}
        self.assertEqual(production_access_kind(job), "production")

    def test_generic_database_secret_is_detected_as_production_access(self):
        job = {"steps": [{"env": {"DB_PASSWORD": "${{ secrets.PRODUCTION_DB_PASSWORD }}"},
                         "run": "php artisan institutes:offering-gap-preflight"}]}
        self.assertEqual(production_access_kind(job), "production")

    def test_remote_database_client_command_is_detected_as_production_access(self):
        job = {"steps": [{"run": "mysql --host=db.production.example --user=operator --password=redacted -e SELECT"}]}
        self.assertEqual(production_access_kind(job), "production")

    def test_local_isolated_mysql_command_is_not_production_access(self):
        job = {"steps": [{"run": "mysql --host=127.0.0.1 --user=ci --password=local -e SELECT"}]}
        self.assertIsNone(production_access_kind(job))

    def test_protected_artisan_command_requires_guard_before_execution(self):
        document = {
            "concurrency": {"group": "estudent-production-access",
                            "cancel-in-progress": False, "queue": "max"},
            "jobs": {"migrate": {
                "environment": "production-operations",
                "steps": [
                    {"uses": "actions/checkout@v4",
                     "with": {"ref": "${{ github.sha }}", "fetch-depth": 0}},
                    {"env": {
                        "TARGET_SHA": "${{ inputs.target_sha }}",
                        "OPERATION_REASON": "${{ inputs.operation_reason }}",
                        "CONFIRM_PRODUCTION_OPERATION": "${{ inputs.confirm_production_operation }}",
                        "EXPECTED_CONFIRMATION": "AUTHORIZE-MONITORING-MIGRATION",
                     }, "run": "bash scripts/ci/validate-production-dispatch.sh"},
                    {"run": "php artisan migrate --force"},
                ],
            }},
        }
        events = {"workflow_dispatch": {"inputs": {
            "target_sha": {"required": True, "type": "string"},
            "operation_reason": {"required": True, "type": "string"},
            "confirm_production_operation": {"required": True, "type": "string"},
        }}}
        self.assertEqual(validate_production_workflow(document, events), [])

    def test_artisan_command_in_protected_production_environment_is_gated(self):
        job = {"environment": "production-operations",
               "steps": [{"run": "php artisan migrate --force"}]}
        self.assertEqual(production_access_kind(job), "production")

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


    def test_valid_production_job_passes_only_when_guard_precedes_step_scoped_ssh(self):
        document = {
            "concurrency": {"group": "estudent-production-access",
                            "cancel-in-progress": False, "queue": "max"},
            "jobs": {"audit": {
                "environment": "production-readonly-diagnostics",
                "steps": [
                    {"uses": "actions/checkout@v4",
                     "with": {"ref": "${{ github.sha }}", "fetch-depth": 0}},
                    {"env": {
                        "TARGET_SHA": "${{ inputs.target_sha }}",
                        "OPERATION_REASON": "${{ inputs.operation_reason }}",
                        "CONFIRM_PRODUCTION_OPERATION": "${{ inputs.confirm_production_operation }}",
                        "EXPECTED_CONFIRMATION": "AUTHORIZE-READONLY-DIAGNOSTICS",
                     }, "run": "bash scripts/ci/validate-production-dispatch.sh"},
                    {"env": {"HOST": "${{ secrets.HOSTINGER_SSH_HOST }}"},
                     "uses": "appleboy/ssh-action@v1"},
                ],
            }},
        }
        events = {"workflow_dispatch": {"inputs": {
            "target_sha": {"required": True, "type": "string"},
            "operation_reason": {"required": True, "type": "string"},
            "confirm_production_operation": {"required": True, "type": "string"},
        }}}
        self.assertEqual(validate_production_workflow(document, events), [])

    def test_job_level_production_secret_is_rejected_before_guard(self):
        document = {
            "concurrency": {"group": "estudent-production-access",
                            "cancel-in-progress": False, "queue": "max"},
            "jobs": {"audit": {
                "environment": "production-readonly-diagnostics",
                "env": {"HOST": "${{ secrets.HOSTINGER_SSH_HOST }}"},
                "steps": [
                    {"run": "bash scripts/ci/validate-production-dispatch.sh"},
                    {"uses": "appleboy/ssh-action@v1"},
                ],
            }},
        }
        events = {"workflow_dispatch": {"inputs": {
            "target_sha": {}, "operation_reason": {},
            "confirm_production_operation": {},
        }}}
        errors = validate_production_workflow(document, events)
        self.assertTrue(any("job scope" in error for error in errors))

    def test_all_actual_production_workflows_are_manual_and_guarded(self):
        for path in workflow_files():
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
            "offering-gap-source-verification.yml", "institutes-public-monitor.yml",
        )
        for name in ordinary:
            document, _, jobs = parse_workflow(WORKFLOWS / name)
            for job_name, job in jobs.items():
                with self.subTest(workflow=name, job=job_name):
                    content = flatten(job)
                    self.assertNotIn(SSH_ACTION, content)
                    self.assertIsNone(production_access_kind(job))
                    for marker in PRODUCTION_SECRET_MARKERS:
                        self.assertNotIn(marker, content)

    def test_public_runtime_workflows_do_not_run_on_push_or_pull_request(self):
        for name in ("public-browser-runtime-diagnostics.yml",
                     "institutes-public-monitor.yml"):
            _, events, _ = parse_workflow(WORKFLOWS / name)
            with self.subTest(workflow=name):
                self.assertNotIn("push", events)
                self.assertNotIn("pull_request", events)


    def test_dispatch_guard_accepts_only_current_main_revision_with_valid_confirmation(self):
        guard = ROOT / "scripts" / "ci" / "validate-production-dispatch.sh"
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            remote = root / "remote.git"
            checkout = root / "checkout"
            subprocess.run(["git", "init", "--bare", str(remote)], check=True,
                           text=True, capture_output=True)
            checkout.mkdir()
            subprocess.run(["git", "init", "-b", "main"], cwd=checkout, check=True,
                           text=True, capture_output=True)
            subprocess.run(["git", "config", "user.email", "ci@example.invalid"],
                           cwd=checkout, check=True)
            subprocess.run(["git", "config", "user.name", "CI Test"],
                           cwd=checkout, check=True)
            (checkout / "README.md").write_text("isolated guard fixture\\n")
            subprocess.run(["git", "add", "README.md"], cwd=checkout, check=True)
            subprocess.run(["git", "commit", "-m", "fixture"], cwd=checkout, check=True,
                           text=True, capture_output=True)
            subprocess.run(["git", "remote", "add", "origin", str(remote)],
                           cwd=checkout, check=True)
            subprocess.run(["git", "push", "-u", "origin", "main"],
                           cwd=checkout, check=True, text=True, capture_output=True)
            target_sha = subprocess.run(
                ["git", "rev-parse", "HEAD"], cwd=checkout, check=True,
                text=True, capture_output=True
            ).stdout.strip()
            env = {
                **os.environ,
                "GITHUB_EVENT_NAME": "workflow_dispatch",
                "GITHUB_REF": "refs/heads/main",
                "GITHUB_SHA": target_sha,
                "TARGET_SHA": target_sha,
                "OPERATION_REASON": "ticket-12345",
                "CONFIRM_PRODUCTION_OPERATION": "AUTHORIZE-READONLY-DIAGNOSTICS",
                "EXPECTED_CONFIRMATION": "AUTHORIZE-READONLY-DIAGNOSTICS",
            }
            result = subprocess.run(
                ["bash", str(guard)], cwd=checkout, env=env,
                text=True, capture_output=True
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn("production_dispatch_authorized=true", result.stdout)
            self.assertIn(target_sha, result.stdout)

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


    def test_workflow_level_production_secret_is_rejected_without_production_job(self):
        document = {
            "env": {"HOST": "${{ secrets.HOSTINGER_SSH_HOST }}"},
            "jobs": {"ordinary": {"steps": [{"run": "echo ordinary CI"}]}},
        }
        errors = validate_production_workflow(document, {"pull_request": {}})
        self.assertTrue(any("workflow scope" in error for error in errors))

    def test_guard_path_in_step_name_is_not_treated_as_executed(self):
        document = {
            "concurrency": {"group": "estudent-production-access",
                            "cancel-in-progress": False, "queue": "max"},
            "jobs": {"audit": {
                "environment": "production-readonly-diagnostics",
                "steps": [
                    {"name": "scripts/ci/validate-production-dispatch.sh"},
                    {"uses": "appleboy/ssh-action@v1"},
                ],
            }},
        }
        events = {"workflow_dispatch": {"inputs": {
            "target_sha": {"required": True, "type": "string"},
            "operation_reason": {"required": True, "type": "string"},
            "confirm_production_operation": {"required": True, "type": "string"},
        }}}
        errors = validate_production_workflow(document, events)
        self.assertTrue(any("no dispatch guard" in error for error in errors))

    def test_generic_confirmation_phrase_is_rejected(self):
        document = {
            "concurrency": {"group": "estudent-production-access",
                            "cancel-in-progress": False, "queue": "max"},
            "jobs": {"audit": {
                "environment": "production-readonly-diagnostics",
                "steps": [
                    {"uses": "actions/checkout@v4",
                     "with": {"ref": "${{ github.sha }}", "fetch-depth": 0}},
                    {"env": {
                        "TARGET_SHA": "${{ inputs.target_sha }}",
                        "OPERATION_REASON": "${{ inputs.operation_reason }}",
                        "CONFIRM_PRODUCTION_OPERATION": "${{ inputs.confirm_production_operation }}",
                        "EXPECTED_CONFIRMATION": "AUTHORIZE-PRODUCTION-OPERATION",
                     }, "run": "bash scripts/ci/validate-production-dispatch.sh"},
                    {"uses": "appleboy/ssh-action@v1"},
                ],
            }},
        }
        events = {"workflow_dispatch": {"inputs": {
            "target_sha": {"required": True, "type": "string"},
            "operation_reason": {"required": True, "type": "string"},
            "confirm_production_operation": {"required": True, "type": "string"},
        }}}
        errors = validate_production_workflow(document, events)
        self.assertTrue(any("unique operation-specific" in error for error in errors))


if __name__ == "__main__":
    unittest.main()
