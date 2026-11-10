import importlib.util
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch
from subprocess import CompletedProcess

MODULE_PATH = Path(__file__).parents[2] / "scripts/phase1c/verify-host-identity.py"
SPEC = importlib.util.spec_from_file_location("verify_host_identity", MODULE_PATH)
MODULE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)


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
            CompletedProcess([], 0, "[185.232.14.237]:65002 ssh-ed25519 AAAATESTKEY\n", ""),
            CompletedProcess([], 0, "256 SHA256:W9YXwb9g6kNtTQxZvw+/cxOevMUeyw+Y4v4iIc/eHAk host (ED25519)\n", ""),
        ]
        with tempfile.TemporaryDirectory() as tmp:
            path = Path(tmp) / "known_hosts"
            MODULE.verify(MODULE.EXPECTED_HOST, MODULE.EXPECTED_PORT, MODULE.EXPECTED_FINGERPRINT, path)
            self.assertTrue(path.exists())
            self.assertEqual(run.call_count, 2)
            self.assertIn("ssh-keyscan", run.call_args_list[0].args[0][0])
            self.assertIn("ssh-keygen", run.call_args_list[1].args[0][0])

    def test_workflow_pins_action_and_both_openssh_connections(self):
        workflow = (Path(__file__).parents[2] / ".github/workflows/phase1c-readonly-schema-diagnostic.yml").read_text()
        self.assertIn("fingerprint: ${{ secrets.PRODUCTION_READONLY_HOSTINGER_SSH_HOST_FINGERPRINT }}", workflow)
        self.assertIn("verify-host-identity.py", workflow)
        self.assertIn("StrictHostKeyChecking=yes", workflow)
        self.assertNotIn('ssh-keyscan -p "$SSH_PORT" "$SSH_HOST" > "$known_hosts_file"', workflow)
        self.assertGreaterEqual(workflow.count('UserKnownHostsFile=$known_hosts_file'), 2)


if __name__ == "__main__":
    unittest.main()
