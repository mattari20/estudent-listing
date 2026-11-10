from __future__ import annotations

import os
import subprocess
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
COLLECTOR = ROOT / "scripts/phase1c/readonly-schema-diagnostic.php"
CLEANUP = ROOT / "scripts/phase1c/cleanup-evidence-transfer.sh"


class MetadataVisibilityTests(unittest.TestCase):
    def php(self, expression: str) -> str:
        result = subprocess.run(
            ["php", "-r", f"require {str(COLLECTOR)!r}; echo {expression};"],
            check=True, capture_output=True, text=True,
        )
        return result.stdout

    def test_collector_contains_no_show_grants(self):
        source = COLLECTOR.read_text(encoding="utf-8").upper()
        self.assertNotIn("SHOW GRANTS", source)

    def test_global_select_proves_complete_metadata_visibility(self):
        self.assertEqual(self.php("phase1cMetadataVisibilityComplete(['SELECT'], []) ? 'true' : 'false'"), "true")

    def test_schema_select_proves_complete_metadata_visibility(self):
        self.assertEqual(self.php("phase1cMetadataVisibilityComplete([], ['SELECT']) ? 'true' : 'false'"), "true")

    def test_incomplete_privileges_do_not_prove_absence(self):
        self.assertEqual(self.php("phase1cMetadataVisibilityComplete([], []) ? 'true' : 'false'"), "false")

    def test_non_select_privileges_do_not_prove_absence(self):
        self.assertEqual(self.php("phase1cMetadataVisibilityComplete(['INSERT','UPDATE'], []) ? 'true' : 'false'"), "false")

    def test_absent_object_is_unknown_without_visibility_proof(self):
        self.assertEqual(self.php("phase1cMetadataAbsenceState(false)"), "UNKNOWN")

    def test_absent_object_is_missing_with_visibility_proof(self):
        self.assertEqual(self.php("phase1cMetadataAbsenceState(true)"), "MISSING")


class RemoteCleanupTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.bin = self.root / "bin"
        self.bin.mkdir()
        self.log = self.root / "ssh.log"
        self.fake_ssh = self.bin / "ssh"
        self.fake_ssh.write_text(
            "#!/usr/bin/env python3\n"
            "import os, pathlib, sys\n"
            "pathlib.Path(os.environ['SSH_LOG']).write_text('\\n'.join(sys.argv[1:]))\n"
            "sys.exit(int(os.environ.get('SSH_EXIT', '0')))\n",
            encoding="utf-8",
        )
        self.fake_ssh.chmod(0o755)
        self.key = self.root / "phase1c-hostinger-readonly-key"
        self.known = self.root / "phase1c-hostinger-known-hosts"
        self.key.write_text("private-key-placeholder")
        self.known.write_text("pinned-host-key")
        for name in ("evidence.gz", "evidence.gz.part", "remote-evidence-manifest.json"):
            (self.root / name).write_text("temporary")

    def run_cleanup(self, ssh_exit: int = 0, attempted: str = "true"):
        env = os.environ.copy()
        env.update({
            "PATH": str(self.bin) + os.pathsep + env.get("PATH", ""),
            "SSH_LOG": str(self.log),
            "SSH_EXIT": str(ssh_exit),
            "REMOTE_TRANSFER_ATTEMPTED": attempted,
        })
        return subprocess.run(
            ["bash", str(CLEANUP), "185.232.14.237", "readonly-user", "65002",
             str(self.key), str(self.known), "123456789", "2", str(self.root)],
            env=env, capture_output=True, text=True,
        )

    def test_successful_cleanup_uses_exact_run_files_and_removes_runner_files(self):
        result = self.run_cleanup()
        self.assertEqual(result.returncode, 0, result.stderr)
        command = self.log.read_text(encoding="utf-8")
        self.assertIn("/tmp/phase1c-readonly-evidence-123456789-2.gz", command)
        self.assertIn("/tmp/phase1c-readonly-evidence-123456789-2.manifest.json", command)
        self.assertNotIn("123456789-1", command)
        self.assertIn("StrictHostKeyChecking=yes", command)
        self.assertIn("test ! -e", command)
        for path in (self.key, self.known, self.root / "evidence.gz",
                     self.root / "evidence.gz.part", self.root / "remote-evidence-manifest.json"):
            self.assertFalse(path.exists(), str(path))
        self.assertIn("remote_transfer_cleanup=PASS", result.stdout)

    def test_remote_cleanup_failure_fails_and_still_removes_runner_files(self):
        result = self.run_cleanup(ssh_exit=255)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("remote_transfer_cleanup=FAIL", result.stderr)
        for path in (self.key, self.known, self.root / "evidence.gz",
                     self.root / "evidence.gz.part", self.root / "remote-evidence-manifest.json"):
            self.assertFalse(path.exists(), str(path))

    def test_cleanup_is_scoped_to_run_and_attempt(self):
        result = self.run_cleanup()
        self.assertEqual(result.returncode, 0, result.stderr)
        command = self.log.read_text(encoding="utf-8")
        self.assertEqual(command.count("phase1c-readonly-evidence-123456789-2"), 4)
        self.assertNotIn("phase1c-readonly-evidence-123456789-1", command)
        self.assertNotIn("phase1c-readonly-evidence-123456788-2", command)

    def test_skipped_collector_does_not_attempt_remote_ssh(self):
        result = self.run_cleanup(attempted="false")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse(self.log.exists())
        self.assertIn("remote_transfer_cleanup=NOT_REQUIRED", result.stdout)
        self.assertFalse(self.key.exists())
        self.assertFalse(self.known.exists())


if __name__ == "__main__":
    unittest.main()
