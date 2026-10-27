#!/usr/bin/env python3
"""Fail-closed short-command interpreter for the Institutes Directory project."""
import argparse, json, re
from pathlib import Path
STEPS = {
1:"### Step 1 — Foundation, Access & Automation Readiness",
2:"### Step 2 — Deployment, Database Schema & Migration Integrity",
3:"### Step 3 — Data Integrity, Verification & Duplicate Audit",
4:"### Step 4 — Import, Approval, Promotion & Recovery Testing",
5:"### Step 5 — Live Regression, Automated Reporting & Phase 2 Closure",
}
ALLOWED={"continue","resume","move to step 2","move to step 3"}
def parse_command(value):
 c=re.sub(r"\\s+"," ",value.strip().lower())
 if c not in ALLOWED: return {"valid":False,"command":c,"error":"Use Continue, Resume, Move to Step 2, or Move to Step 3."}
 return {"valid":True,"command":c,"requested_step":int(c[-1]) if c.startswith("move to step ") else None}
def evaluate(root, command, previous=None):
 p=parse_command(command)
 if not p["valid"]: return {**p,"decision":"STOP","reason":p["error"]}
 tracker=root/"docs/PROJECT_MASTER_TRACKER.md"; protocol=root/"docs/AUTONOMOUS_STEP_EXECUTION_PROTOCOL.md"
 if not tracker.is_file() or not protocol.is_file(): return {**p,"decision":"STOP","reason":"Authoritative tracker/protocol missing."}
 t=tracker.read_text(); g=protocol.read_text()
 if any(h not in t for h in STEPS.values()) or "Do not start the next Step" not in g:
  return {**p,"decision":"STOP","reason":"Governance validation failed."}
 # Default is NOT_EVIDENCED. A gate is PASS only with an explicit step-specific PASS
 # status in the tracker; prose describing requirements is never treated as evidence.
 gates={}
 for n in STEPS:
  explicit=re.search(rf"(?im)^(?:Final )?Step {n} gate:\\s*\\*\\*PASS\\*\\*|^Step {n} closeout status:\\s*PASS\\b",t)
  blocked=re.search(rf"(?im)^(?:Final )?Step {n} gate:\\s*\\*\\*(?:NOT YET|BLOCKED)\\*\\*|^Step {n} closeout status:\\s*(?:BLOCKED|NOT CLOSED)",t)
  gates[n]="PASS" if explicit and not blocked else ("BLOCKED" if blocked else "NOT_EVIDENCED")
 current=next((n for n in STEPS if gates[n]!="PASS"),5)
 target=p.get("requested_step") or current
 if target!=current:
  return {**p,"decision":"STOP","target_step":target,"current_step":current,"gate_status":gates,"resume_point":f"Step {current} acceptance gate","reason":f"Step {target} cannot run; earliest unpassed gate is Step {current}.","execution_mode":"governed_checks_only"}
 return {**p,"decision":"RUN_SUPPORTED_CHECKS","target_step":target,"current_step":current,"gate_status":gates,"resume_point":(previous or {}).get("resume_point",f"Step {current} acceptance gate"),"execution_mode":"governed_checks_only","supported_tasks":["controller unit tests","offline governance smoke test","read-only GitHub inventory"],"not_automated":["LLM-driven code edits/repair: no model-provider integration configured","workflow dispatch from chat: current GitHub connector exposes no dispatch action","Hostinger diagnostics: must be explicitly dispatched with configured SSH secrets","production writes, deletion, migrations, deployment"],"reason":"Only supported non-mutating checks may run; step acceptance remains evidence-gated."}
def main():
 a=argparse.ArgumentParser(); a.add_argument("--root",default="."); a.add_argument("--command",required=True); a.add_argument("--state-file"); a.add_argument("--output",default="controller-report/command-state.json"); x=a.parse_args()
 prev=json.loads(Path(x.state_file).read_text()) if x.state_file and Path(x.state_file).is_file() else None
 r=evaluate(Path(x.root).resolve(),x.command,prev); out=Path(x.output); out.parent.mkdir(parents=True,exist_ok=True); out.write_text(json.dumps(r,indent=2)+"\\n"); print(json.dumps(r,indent=2)); return 0 if r["decision"]!="STOP" else 3
if __name__=="__main__": raise SystemExit(main())
