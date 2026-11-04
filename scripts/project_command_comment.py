"""Fail-closed parser for GitHub issue-comment and dispatch commands."""

from __future__ import annotations

import os
from pathlib import Path

SUPPORTED_COMMANDS = {
    "Continue", "continue",
    "Resume", "resume",
    "Move to Step 2", "move to step 2",
    "Move to Step 3", "move to step 3",
}


def _valid_issue(value: object, *, optional: bool) -> str | None:
    if value is None or value == "":
        return "" if optional else None
    text = str(value).strip()
    if not text.isascii() or not text.isdecimal() or int(text) < 1:
        return None
    return text


def resolve_command(
    event: str,
    input_command: object = "",
    input_issue: object = "",
    comment: object = "",
    comment_issue: object = "",
) -> dict[str, object]:
    """Return only allow-listed single-line commands and validated issue IDs."""
    if event in {"push", "pull_request"}:
        return {"value": "Continue", "issue": "", "accepted": True}

    if event == "issue_comment":
        candidate = comment.strip() if isinstance(comment, str) else ""
        issue = _valid_issue(comment_issue, optional=False)
    elif event == "workflow_dispatch":
        candidate = input_command.strip() if isinstance(input_command, str) else ""
        issue = _valid_issue(input_issue, optional=True)
    else:
        return {"value": "", "issue": "", "accepted": False}

    accepted = candidate in SUPPORTED_COMMANDS and issue is not None
    if not accepted:
        return {"value": "", "issue": "", "accepted": False}

    # Both outputs are now constrained to an allow-list or ASCII decimal digits.
    return {"value": candidate, "issue": issue, "accepted": True}


def serialize_outputs(result: dict[str, object]) -> str:
    """Serialize validated values as single-line GitHub Actions outputs."""
    value = result["value"] if result["accepted"] else ""
    issue = result["issue"] if result["accepted"] else ""
    accepted = "true" if result["accepted"] else "false"
    return f"value={value}\nissue={issue}\naccepted={accepted}\n"


def main() -> None:
    result = resolve_command(
        os.environ.get("EVENT", ""),
        os.environ.get("INPUT_COMMAND", ""),
        os.environ.get("INPUT_ISSUE", ""),
        os.environ.get("COMMENT", ""),
        os.environ.get("COMMENT_ISSUE", ""),
    )
    output_path = os.environ.get("GITHUB_OUTPUT")
    if not output_path:
        raise SystemExit("GITHUB_OUTPUT is required.")
    with Path(output_path).open("a", encoding="utf-8", newline="\n") as output:
        output.write(serialize_outputs(result))
    if not result["accepted"]:
        print("Ignoring unrelated or invalid command; no project execution requested.")


if __name__ == "__main__":
    main()
