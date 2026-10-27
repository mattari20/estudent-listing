import sys,tempfile,unittest
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[2]/"scripts"))
import project_command_orchestrator as o
class Tests(unittest.TestCase):
 def fixture(self,gate="NOT YET"):
  d=tempfile.TemporaryDirectory(); r=Path(d.name); (r/"docs").mkdir()
  (r/"docs/PROJECT_MASTER_TRACKER.md").write_text("\\n\\n".join(h+"\\n**Completion gate:** "+gate for h in o.STEPS.values()))
  (r/"docs/AUTONOMOUS_STEP_EXECUTION_PROTOCOL.md").write_text("Do not start the next Step until current gate passes.")
  return d,r
 def test_short_commands(self):
  self.assertTrue(o.parse_command(" Continue ")["valid"]); self.assertEqual(o.parse_command("Move to Step 3")["requested_step"],3); self.assertFalse(o.parse_command("skip")["valid"])
 def test_step_two_blocked(self):
  d,r=self.fixture()
  with d: self.assertEqual(o.evaluate(r,"Move to Step 2")["decision"],"STOP")
 def test_continue_runs_only_supported_checks(self):
  d,r=self.fixture()
  with d:
   x=o.evaluate(r,"Continue"); self.assertEqual(x["decision"],"RUN_SUPPORTED_CHECKS"); self.assertIn("LLM-driven code edits/repair: no model-provider integration configured",x["not_automated"])
 def test_missing_protocol_fails_closed(self):
  d,r=self.fixture()
  with d:
   (r/"docs/AUTONOMOUS_STEP_EXECUTION_PROTOCOL.md").unlink(); self.assertEqual(o.evaluate(r,"Resume")["decision"],"STOP")
if __name__=="__main__": unittest.main()
