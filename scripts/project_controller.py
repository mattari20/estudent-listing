#!/usr/bin/env python3
"""Read-only project controller for the Institutes Directory.

This controller inventories repository governance, open PRs, and selected workflow
runs. It never dispatches workflows, writes to production, merges PRs, or edits the
project tracker. Reports are intended for human review before any gated action.
"""
from __future__ import annotations

import argparse
import json
import os
import re
import sys
import urllib.error
import urllib.request
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Callable

API_ROOT = "https://api.github.com"
EXPECTED_STEPS = [
    "Step 1 — Foundation, Access & Automation Readiness",
    "Step 2 — Deployment, Database Schema & Migration Integrity",
    "Step 3 — Data Integrity, Verification & Duplicate Audit",
    "Step 4 — Import, Approval, Promotion & Recovery Testing",
    "Step 5 — Live Regression, Automated Reporting & Phase 2 Closure",
]
TRACKER_PATH = Path("docs/PROJECT_MASTER_TRACKER.md")
PROTOCOL_PATH = Path("docs/AUTONOMOUS_STEP_EXECUTION_PROTOCOL.md")
WATCHED_WORKFLOWS = {
    "scheduled-institute-discovery.yml": "governed_discovery",
    "hostinger-readonly-diagnostics.yml": "hostinger_readonly_diagnostics",
    "controlled-hostinger-code-deploy.yml": "controlled_code_deployment",
}


def api_get(path: str, token: str) -> Any:
    request = urllib.request.Request(
        API_ROOT + path,
        headers={
            "Accept": "application/vnd.github+json",
            "Authorization": f"Bearer {token}",
            "X-GitHub-Api-Version": "2022-11-28",
            "User-Agent": "estudent-institutes-project-controller",
        },
    )
    with urllib.request.urlopen(request, timeout=20) as response:
        return json.loads(response.read().decode("utf-8"))


def local_governance(root: Path) -> dict[str, Any]:
    tracker_file = root / TRACKER_PATH
    protocol_file = root / PROTOCOL_PATH
    tracker = tracker_file.read_text(encoding="utf-8") if tracker_file.exists() else ""
    protocol = protocol_file.read_text(encoding="utf-8") if protocol_file.exists() else ""
    found_steps = [step for step in EXPECTED_STEPS if step in tracker]
    # Count only exact numbered Phase 2 headings; avoid matching references in prose.
    heading_steps = re.findall(r"^### Step [1-5] — .+$", tracker, flags=re.MULTILINE)
    stale_blocker = "Step 1 closeout status: BLOCKED / NOT CLOSED" in tracker
    return {
        "master_protocol_present": bool(protocol),
        "master_protocol_path": str(PROTOCOL_PATH),
        "tracker_present": bool(tracker),
        "tracker_path": str(TRACKER_PATH),
        "expected_phase2_steps": 5,
        "phase2_step_headings_found": len(heading_steps),
        "all_expected_step_titles_found": len(found_steps) == 5,
        "tracker_step1_blocker_text_present": stale_blocker,
        "protocol_requires_verified_completion": (
            "mandatory test" in protocol.lower()
            and "Do not report 100%" in protocol
            and "Do not start the next Step" in protocol
        ),
    }


def latest_workflow_run(api: Callable[[str], Any], workflow_id: int) -> dict[str, Any]:
    payload = api(f"/repos/{os.environ['GITHUB_REPOSITORY']}/actions/workflows/{workflow_id}/runs?per_page=1")
    runs = payload.get("workflow_runs", [])
    if not runs:
        return {"available": False, "status": "no_runs"}
    run = runs[0]
    return {
        "available": True,
        "id": run.get("id"),
        "name": run.get("name"),
        "status": run.get("status"),
        "conclusion": run.get("conclusion"),
        "event": run.get("event"),
        "head_branch": run.get("head_branch"),
        "head_sha": run.get("head_sha"),
        "updated_at": run.get("updated_at"),
        "html_url": run.get("html_url"),
    }


def build_report(root: Path, api: Callable[[str], Any]) -> dict[str, Any]:
    repository = os.environ["GITHUB_REPOSITORY"]
    governance = local_governance(root)
    pull_payload = api(f"/repos/{repository}/pulls?state=open&per_page=100")
    open_prs = [
        {
            "number": pr.get("number"),
            "title": pr.get("title"),
            "head": (pr.get("head") or {}).get("ref"),
            "head_sha": (pr.get("head") or {}).get("sha"),
            "base": (pr.get("base") or {}).get("ref"),
            "url": pr.get("html_url"),
            "draft": pr.get("draft", False),
        }
        for pr in pull_payload
    ]
    workflow_payload = api(f"/repos/{repository}/actions/workflows?per_page=100")
    workflows = workflow_payload.get("workflows", [])
    by_path = {Path(item.get("path", "")).name: item for item in workflows}
    run_report: dict[str, Any] = {}
    for filename, key in WATCHED_WORKFLOWS.items():
        workflow = by_path.get(filename)
        if not workflow:
            run_report[key] = {"available": False, "status": "workflow_not_found", "path": filename}
            continue
        try:
            run_report[key] = latest_workflow_run(api, int(workflow["id"]))
            run_report[key]["workflow_path"] = filename
        except (KeyError, TypeError, ValueError, urllib.error.URLError) as exc:
            run_report[key] = {"available": False, "status": "lookup_error", "detail": str(exc)}
    green_runs = all(
        run.get("available") and run.get("status") == "completed" and run.get("conclusion") == "success"
        for key, run in run_report.items()
        if key in {"governed_discovery", "hostinger_readonly_diagnostics"}
    )
    protocol_ok = (
        governance["master_protocol_present"]
        and governance["tracker_present"]
        and governance["phase2_step_headings_found"] == 5
        and governance["all_expected_step_titles_found"]
        and governance["protocol_requires_verified_completion"]
    )
    # This is deliberately a review recommendation, not an automatic closeout.
    controller_status = "REVIEW_REQUIRED"
    blockers = []
    if not protocol_ok:
        blockers.append("Master protocol or five-step tracker validation failed.")
    for key in ("governed_discovery", "hostinger_readonly_diagnostics"):
        run = run_report.get(key, {})
        if not run.get("available") or run.get("status") != "completed" or run.get("conclusion") != "success":
            blockers.append(f"{key} does not have a latest successful completed run.")
    if governance["tracker_step1_blocker_text_present"]:
        blockers.append("Tracker still contains the earlier Step 1 blocker statement; reconcile it with current evidence.")
    if open_prs:
        blockers.append(f"{len(open_prs)} pull request(s) remain open and require status/review.")
    return {
        "schema_version": 1,
        "generated_at_utc": datetime.now(timezone.utc).isoformat(),
        "repository": repository,
        "controller_mode": "read_only_review_package",
        "autonomous_production_actions_enabled": False,
        "governance": governance,
        "open_pull_requests": open_prs,
        "latest_workflow_runs": run_report,
        "step1_closure_candidate": bool(protocol_ok and green_runs and not blockers),
        "status": controller_status,
        "blockers": blockers,
        "safety": {
            "production_writes": "not performed",
            "deletions": "not performed",
            "migrations": "not performed",
            "deployments": "not performed",
            "workflow_dispatch": "not performed",
            "tracker_mutation": "not performed",
        },
    }


def render_markdown(report: dict[str, Any]) -> str:
    lines = [
        "# Institutes Directory — Project Controller Report",
        "",
        f"- Generated (UTC): {report['generated_at_utc']}",
        f"- Repository: {report['repository']}",
        f"- Controller mode: `{report['controller_mode']}`",
        f"- Status: **{report['status']}**",
        f"- Step 1 closure candidate: **{'YES' if report['step1_closure_candidate'] else 'NO'}**",
        "",
        "## Governance checks",
    ]
    for key, value in report["governance"].items():
        lines.append(f"- {key}: `{value}`")
    lines += ["", "## Latest monitored workflow runs"]
    for key, run in report["latest_workflow_runs"].items():
        lines.append(f"- **{key}**: `{run.get('status', 'unknown')}` / `{run.get('conclusion', 'unknown')}` — {run.get('html_url', run.get('path', 'no link'))}")
    lines += ["", "## Open pull requests"]
    if report["open_pull_requests"]:
        for pr in report["open_pull_requests"]:
            lines.append(f"- [#{pr['number']} {pr['title']}]({pr['url']}) — `{pr['head']} → {pr['base']}`")
    else:
        lines.append("- None")
    lines += ["", "## Blockers / review items"]
    lines += [f"- {item}" for item in report["blockers"]] or ["- None detected by automated checks; human review is still required."]
    lines += [
        "",
        "## Safety declaration",
        "This report is read-only. It does not merge PRs, dispatch workflows, deploy code, run migrations, or mutate canonical data.",
        "",
    ]
    return "\n".join(lines)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--root", default=".", help="repository checkout root")
    parser.add_argument("--output-dir", default="controller-report", help="report output directory")
    parser.add_argument("--offline", action="store_true", help="run local governance checks only; intended for tests")
    args = parser.parse_args()
    root = Path(args.root).resolve()
    if args.offline:
        report = {
            "schema_version": 1,
            "generated_at_utc": datetime.now(timezone.utc).isoformat(),
            "repository": os.environ.get("GITHUB_REPOSITORY", "offline/test"),
            "controller_mode": "read_only_review_package",
            "autonomous_production_actions_enabled": False,
            "governance": local_governance(root),
            "open_pull_requests": [],
            "latest_workflow_runs": {},
            "step1_closure_candidate": False,
            "status": "OFFLINE_VALIDATION_ONLY",
            "blockers": ["Offline mode does not verify GitHub or live workflow state."],
            "safety": {"production_writes": "not performed", "deletions": "not performed", "migrations": "not performed", "deployments": "not performed", "workflow_dispatch": "not performed", "tracker_mutation": "not performed"},
        }
    else:
        token = os.environ.get("GITHUB_TOKEN")
        if not token or not os.environ.get("GITHUB_REPOSITORY"):
            print("GITHUB_TOKEN and GITHUB_REPOSITORY are required for online controller mode.", file=sys.stderr)
            return 2
        report = build_report(root, lambda path: api_get(path, token))
    output = Path(args.output_dir)
    output.mkdir(parents=True, exist_ok=True)
    (output / "project-controller-report.json").write_text(json.dumps(report, indent=2) + "\n", encoding="utf-8")
    (output / "project-controller-report.md").write_text(render_markdown(report), encoding="utf-8")
    print(render_markdown(report))
    # A blocked or review-required report is a valid report, not a failed controller execution.
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
