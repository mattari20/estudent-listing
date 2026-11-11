import importlib.util
import os
import stat
import subprocess
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch
from subprocess import CompletedProcess

MODULE_PATH = Path(__file__).parents[2] / "scripts/phase1c/verify-host-identity.py"
SPEC = importlib.util.spec_from_file_location("verify_host_identity", MODULE_PATH)
MODULE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)
VERIFIED_HOST_FINGERPRINT = "SHA256:rLqM002opFW/1fGj8IAj/Sdr6u1tVaN8Tb89lcl1eQE"


class HostIdentityTests(unittest.TestCase):
    def test_correct_fingerprint_accepted(self):
        MODULE.validate_reference(MODULE.EXPECTED_HOST, MODULE.EXPECTED_PORT, MODULE.EXPECTED_FINGERPRINT)
        MODULE.validate_presented_fingerprint(MODULE.EXPECTED_FINGERPRINT, MODULE.EXPECTED_FINGERPRINT)

    def test_incorrect_fingerprint_rejected(self):
        with self.assertRaises(ValueError):
            MODULE.validate_reference(MODULE.EXPECTED_HOST, MODULE.EXPECTED_PORT, "SHA256:incorrect")
        with self.assertRaises(ValueError):
            MODULE.validate_presented_fingerprint("SHA256:incorrect", MODULE.EXPECTED_FINGERPRINT)

    def test_missing_fingerprint_rejected(self):
        with self.assertRaises(ValueError):
            MODULE.validate_reference(MODULE.EXPECTED_HOST, MODULE.EXPECTED_PORT, "")

    def test_unexpected_host_rejected(self):
        with self.assertRaises(ValueError):
            MODULE.validate_reference("192.0.2.10", MODULE.EXPECTED_PORT, MODULE.EXPECTED_FINGERPRINT)

    def test_unexpected_port_rejected(self):
        with self.assertRaises(ValueError):
            MODULE.validate_reference(MODULE.EXPECTED_HOST, "22", MODULE.EXPECTED_FINGERPRINT)

    @patch.object(MODULE.subprocess, "run")
    def test_presented_key_scanned_and_verified_before_use(self, run):
        run.side_effect = [
            CompletedProcess([], 0, "[185.232.14.237]:65002 ecdsa-sha2-nistp256 AAAATESTKEY\n", ""),
            CompletedProcess([], 0, f"256 {VERIFIED_HOST_FINGERPRINT} host (ECDSA)\n", ""),
        ]
        with tempfile.TemporaryDirectory() as tmp:
            path = Path(tmp) / "known_hosts"
            MODULE.verify(MODULE.EXPECTED_HOST, MODULE.EXPECTED_PORT, MODULE.EXPECTED_FINGERPRINT, path)
            self.assertTrue(path.exists())
            self.assertEqual(run.call_count, 2)
            self.assertEqual(
                run.call_args_list[0].args[0],
                ["ssh-keyscan", "-T", "10", "-t", "ecdsa", "-p", MODULE.EXPECTED_PORT, MODULE.EXPECTED_HOST],
            )
            self.assertIn("ssh-keygen", run.call_args_list[1].args[0][0])


    @staticmethod
    def extract_prepare_transfer_shell(workflow):
        lines = workflow.splitlines()
        step_index = next(
            i for i, line in enumerate(lines)
            if line.strip() == "- name: Prepare binary-safe SSH transfer client"
        )
        run_index = next(i for i in range(step_index + 1, len(lines)) if lines[i].strip() == "run: |")
        body = []
        for line in lines[run_index + 1:]:
            if line and len(line) - len(line.lstrip()) <= 8:
                break
            body.append(line[10:] if line.startswith("          ") else "")
        return "\n".join(body) + "\n"

    def test_all_workflow_shell_continuations_are_single_backslashes(self):
        workflow = (Path(__file__).parents[2] / ".github/workflows/phase1c-readonly-schema-diagnostic.yml").read_text()
        for number, line in enumerate(workflow.splitlines(), start=1):
            stripped = line.rstrip()
            if stripped.endswith("\\"):
                trailing = len(stripped) - len(stripped.rstrip("\\"))
                self.assertEqual(trailing, 1, f"line {number} has {trailing} trailing backslashes")


    def test_every_extracted_workflow_shell_block_passes_bash_n(self):
        workflow = (Path(__file__).parents[2] / ".github/workflows/phase1c-readonly-schema-diagnostic.yml").read_text()
        lines = workflow.splitlines()
        extracted = 0
        for index, line in enumerate(lines):
            if line.strip() not in ("run: |", "script: |"):
                continue
            parent_indent = len(line) - len(line.lstrip())
            body = []
            for child in lines[index + 1:]:
                if child and len(child) - len(child.lstrip()) <= parent_indent:
                    break
                body.append(child[parent_indent + 2:] if child.startswith(" " * (parent_indent + 2)) else "")
            shell = "\n".join(body) + "\n"
            result = subprocess.run(["bash", "-n"], input=shell, text=True, capture_output=True)
            self.assertEqual(result.returncode, 0, f"shell block at line {index + 1}: {result.stderr}")
            extracted += 1
        self.assertGreaterEqual(extracted, 5)

    def test_extracted_prepare_step_is_valid_shell_and_preserves_private_key(self):
        workflow = (Path(__file__).parents[2] / ".github/workflows/phase1c-readonly-schema-diagnostic.yml").read_text()
        shell = self.extract_prepare_transfer_shell(workflow)
        syntax = subprocess.run(["bash", "-n"], input=shell, text=True, capture_output=True)
        self.assertEqual(syntax.returncode, 0, syntax.stderr)

        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            source_key = root / "source-key"
            subprocess.run(
                ["ssh-keygen", "-q", "-t", "ed25519", "-N", "", "-f", str(source_key)],
                check=True, capture_output=True, text=True,
            )
            private_key = source_key.read_text()
            fake_bin = root / "bin"
            fake_bin.mkdir()
            fake_python = fake_bin / "python3"
            fake_python.write_text(
                "#!/usr/bin/env bash\n"
                "set -euo pipefail\n"
                "printf '%s\\n' \"$@\" > \"$CAPTURE_ARGS\"\n"
                "while (($#)); do\n"
                "  if [[ \"$1\" == \"--known-hosts\" ]]; then shift; : > \"$1\"; fi\n"
                "  shift\n"
                "done\n"
            )
            fake_python.chmod(0o755)
            capture = root / "args.txt"
            runner_temp = root / "runner-temp"
            runner_temp.mkdir()
            env = os.environ.copy()
            env.update({
                "PATH": str(fake_bin) + os.pathsep + env["PATH"],
                "RUNNER_TEMP": str(runner_temp),
                "SSH_HOST": MODULE.EXPECTED_HOST,
                "SSH_PORT": MODULE.EXPECTED_PORT,
                "SSH_KEY": private_key,
                "HOST_FINGERPRINT": MODULE.EXPECTED_FINGERPRINT,
                "CAPTURE_ARGS": str(capture),
            })
            result = subprocess.run(["bash", "-euo", "pipefail", "-c", shell], env=env, capture_output=True, text=True)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertNotIn(private_key, result.stdout + result.stderr)

            key_file = runner_temp / "phase1c-hostinger-readonly-key"
            known_hosts = runner_temp / "phase1c-hostinger-known-hosts"
            self.assertEqual(key_file.read_text().rstrip("\n"), private_key.rstrip("\n"))
            self.assertEqual(stat.S_IMODE(key_file.stat().st_mode), 0o600)
            self.assertTrue(known_hosts.is_file())
            parsed = subprocess.run(["ssh-keygen", "-y", "-f", str(key_file)], capture_output=True, text=True)
            self.assertEqual(parsed.returncode, 0, parsed.stderr)

            args = capture.read_text().splitlines()
            self.assertEqual(args, [
                ".target-source/scripts/phase1c/verify-host-identity.py",
                "--host", MODULE.EXPECTED_HOST,
                "--port", MODULE.EXPECTED_PORT,
                "--fingerprint", MODULE.EXPECTED_FINGERPRINT,
                "--known-hosts", str(known_hosts),
            ])

    def test_workflow_pins_action_and_both_openssh_connections(self):
        workflow = (Path(__file__).parents[2] / ".github/workflows/phase1c-readonly-schema-diagnostic.yml").read_text()
        self.assertEqual(MODULE.EXPECTED_FINGERPRINT, VERIFIED_HOST_FINGERPRINT)
        self.assertIn(f'test "$HOST_FINGERPRINT" = "{VERIFIED_HOST_FINGERPRINT}"', workflow)
        self.assertIn('"-t", "ecdsa"', MODULE_PATH.read_text())
        self.assertIn("fingerprint: ${{ secrets.PRODUCTION_READONLY_HOSTINGER_SSH_HOST_FINGERPRINT }}", workflow)
        self.assertIn("verify-host-identity.py", workflow)
        self.assertIn("StrictHostKeyChecking=yes", workflow)
        self.assertNotIn('ssh-keyscan -p "$SSH_PORT" "$SSH_HOST" > "$known_hosts_file"', workflow)
        cleanup = (Path(__file__).parents[2] / "scripts/phase1c/cleanup-evidence-transfer.sh").read_text()
        self.assertIn("StrictHostKeyChecking=yes", cleanup)
        self.assertGreaterEqual(
            workflow.count('UserKnownHostsFile=$known_hosts_file')
            + cleanup.count('UserKnownHostsFile=$known_hosts_file'),
            2,
        )


if __name__ == "__main__":
    unittest.main()
