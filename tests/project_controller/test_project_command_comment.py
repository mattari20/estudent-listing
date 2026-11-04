import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / "scripts"))
import project_command_comment as parser


class ProjectCommandCommentTests(unittest.TestCase):
    def test_all_exact_commands_are_accepted(self):
        commands = (
            "Continue", "continue", "Resume", "resume",
            "Move to Step 2", "move to step 2",
            "Move to Step 3", "move to step 3",
        )
        for command in commands:
            with self.subTest(command=command):
                result = parser.resolve_command("issue_comment", comment=command, comment_issue="1011")
                self.assertTrue(result["accepted"])
                self.assertEqual(result["value"], command)
                self.assertEqual(result["issue"], "1011")

    def test_leading_and_trailing_whitespace_is_accepted(self):
        result = parser.resolve_command(
            "issue_comment", comment=" \tContinue \r\n", comment_issue="1011"
        )
        self.assertEqual(result, {"value": "Continue", "issue": "1011", "accepted": True})

    def test_multiline_unrelated_comment_is_rejected(self):
        result = parser.resolve_command(
            "issue_comment", comment="Please review this.\nContinue\nMore details", comment_issue="1011"
        )
        self.assertEqual(result, {"value": "", "issue": "", "accepted": False})

    def test_supported_command_as_substring_is_rejected(self):
        for comment in ("I agree: Continue", "Continue please", "not Continue"):
            with self.subTest(comment=comment):
                self.assertFalse(parser.resolve_command(
                    "issue_comment", comment=comment, comment_issue="1011"
                )["accepted"])

    def test_shell_metacharacters_quotes_and_markdown_are_rejected(self):
        comment = "Continue; $(touch /tmp/pwned) 'quoted' \"quoted\" **markdown**"
        result = parser.resolve_command("issue_comment", comment=comment, comment_issue="1011")
        self.assertFalse(result["accepted"])
        self.assertNotIn("touch", parser.serialize_outputs(result))

    def test_empty_and_unsupported_comments_are_rejected(self):
        for comment in ("", "   ", "Deploy", "Move to Step 4"):
            with self.subTest(comment=comment):
                self.assertFalse(parser.resolve_command(
                    "issue_comment", comment=comment, comment_issue="1011"
                )["accepted"])

    def test_issue_number_must_be_positive_ascii_decimal(self):
        for issue in ("", "0", "-1", "1\nvalue=Resume", "１２３", "abc"):
            with self.subTest(issue=issue):
                result = parser.resolve_command(
                    "issue_comment", comment="Continue", comment_issue=issue
                )
                self.assertFalse(result["accepted"])
                self.assertEqual(result["issue"], "")

    def test_dispatch_validation_and_push_behavior_are_preserved(self):
        self.assertEqual(
            parser.resolve_command("workflow_dispatch", "Resume", "1012"),
            {"value": "Resume", "issue": "1012", "accepted": True},
        )
        self.assertEqual(
            parser.resolve_command("push"),
            {"value": "Continue", "issue": "", "accepted": True},
        )
        self.assertEqual(
            parser.resolve_command("pull_request"),
            {"value": "Continue", "issue": "", "accepted": True},
        )

    def test_output_serialization_is_single_line_and_allow_listed(self):
        result = parser.resolve_command(
            "issue_comment", comment="Continue\nissue=999", comment_issue="1011"
        )
        serialized = parser.serialize_outputs(result)
        self.assertEqual(serialized, "value=\nissue=\naccepted=false\n")
        self.assertEqual(len(serialized.splitlines()), 3)

    def test_unrelated_comment_cannot_trigger_project_execution(self):
        result = parser.resolve_command(
            "issue_comment", comment="Review feedback: please Continue after fixing", comment_issue="1011"
        )
        self.assertFalse(result["accepted"])
        self.assertEqual(result["value"], "")


if __name__ == "__main__":
    unittest.main()
