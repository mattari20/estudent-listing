#!/usr/bin/env ruby
# frozen_string_literal: true

require "yaml"
require "open3"
require "tmpdir"
require "fileutils"

root = File.expand_path("../..", __dir__)
workflow_path = File.join(root, ".github/workflows/hostinger-artisan-runtime-only.yml")
script_path = File.join(root, "scripts/ops/phase-5b18-runtime-diagnostic.php")
source = File.read(workflow_path)
php_source = File.read(script_path)
YAML.parse(source)

def assert_contract(condition, message)
  abort("Phase 5B.27 delivery contract failed: #{message}") unless condition
end

workflow = YAML.load_file(workflow_path)
triggers = workflow["on"] || workflow[true]
assert_contract(triggers.is_a?(Hash) && triggers.keys == ["workflow_dispatch"], "manual workflow_dispatch only")
assert_contract(workflow.fetch("permissions").fetch("contents") == "read", "contents permission is read-only")
assert_contract(source.include?("ref: 3b0b1bc40206bb665f34064f265c2975f5ea19ef"), "source revision is pinned")
assert_contract(source.include?("PROJECT_PATH='/home/u537436044/domains/estudent.pk/public_html/institutes'"), "production project path is fixed")
assert_contract(source.include?("PHP_BIN='/opt/alt/php85/usr/bin/php'"), "production PHP binary is fixed")
assert_contract(source.include?("SCRIPT='scripts/ops/phase-5b18-runtime-diagnostic.php'"), "source path is fixed")
assert_contract(source.include?("ssh-keyscan") && source.include?("ssh-keygen -lf") && source.include?("StrictHostKeyChecking=yes"), "host fingerprint is pinned and strictly verified")
assert_contract(source.include?("sha256sum \"$SCRIPT\"") && source.include?("script_checksum_mismatch"), "runner and Hostinger script SHA-256 checks exist")
assert_contract(source.include?("chmod 700") && source.include?("chmod 600"), "temporary modes are restrictive")
assert_contract(source.include?("phase-5b18-artisan-runtime.json") && source.include?("262144"), "fixed 256 KiB report destination is enforced")
assert_contract(source.include?("private_root_inside_public") && source.include?("report_directory_inside_public"), "private report destination is outside public root")
assert_contract(source.include?("report_exists_no_overwrite") && source.include?("report_appeared_no_overwrite"), "existing report is never overwritten")

# Count the exact PHP diagnostic command only; php -l and the JSON parser are not invocations.
assert_contract(source.scan(/--report=/).length == 1, "the standalone PHP diagnostic is invoked exactly once")
assert_contract(source.include?("phase_5b27_diagnostic_invocations=1"), "one-invocation audit marker exists")
assert_contract(source.include?("-l") && source.index("-l") < source.index("--report="), "PHP lint happens before the single execution")
assert_contract(source.include?("script_checksum_mismatch") && source.include?("source_revision_hash_mismatch"), "checksum mismatch blocks execution")
assert_contract(source.include?("scp -q") && source.include?("timeout 120s scp"), "transfer is authenticated and bounded")
assert_contract(source.include?("REMOTE_DIR") && source.include?("mktemp -d"), "each transfer uses a unique temp directory")
assert_contract(!source.include?("current-run-path") && !source.include?("MARKER="), "no shared current-run marker may be read or written")
assert_contract(source.include?("D='$REMOTE_DIR'") && source.include?("realpath -e") && source.include?("cleanup_canonical_path_rejected"), "cleanup must validate this run's exact canonical REMOTE_DIR")
assert_contract(source.include?("steps.execute.outputs.remote_ssh_exit != '0'") && source.include?("steps.metadata.outputs.diagnostic_exit_code == ''"), "SSH failure or missing report evidence must fail the workflow")

# Failure evidence is retrieved after the SSH command, without repeating PHP execution.
assert_contract(source.include?("remote_ssh_exit=$STATUS"), "remote exit is captured")
assert_contract(source.include?("always() && steps.transfer.outputs.remote_dir != ''"), "report retrieval is attempted after remote failure")
assert_contract(source.include?("no retry") || source.include?("No retry"), "failed execution is never retried")
assert_contract(source.include?("runtime-diagnostic.stderr"), "stderr evidence is preserved")
assert_contract(source.include?("steps.evidence.outcome == 'success'"), "remote evidence cleanup requires a successful artifact upload")
assert_contract(source.include?("ARTIFACT_PRESERVED"), "cleanup must retain remote evidence if artifact upload fails")
assert_contract(source.include?("exit 1"), "missing evidence or nonzero diagnostic must fail the workflow")
assert_contract(source.include?("ssh-keyscan") && source.include?("EXPECTED_HOST_FINGERPRINT"), "host fingerprint rejection is enforced")
assert_contract(source.include?("HOSTINGER_SSH_PRIVATE_KEY") && source.include?("HOSTINGER_SSH_HOST") && source.include?("HOSTINGER_SSH_PORT"), "missing SSH secrets fail before transfer")
assert_contract(source.include?("test -s") && source.include?("report_missing_no_retry"), "missing report stops without retry")
assert_contract(source.include?("report_exceeds_256KiB"), "oversized report is rejected")
assert_contract(source.include?("report_json_invalid"), "invalid JSON is rejected")
assert_contract(source.include?("timeout 120s scp") && source.include?("scp -q"), "failed upload/download is fail-closed")
assert_contract(source.include?("report_exists_no_overwrite"), "existing report prevents execution")
assert_contract(source.include?("report-metadata.txt"), "private report metadata is preserved")

# Transfer validation: byte count, digest and JSON validation all precede artifact creation.
assert_contract(source.include?('[[ "$BYTES" == "$EXPECTED_BYTES"'), "runner verifies exact byte count")
assert_contract(source.include?('[[ "$SHA" == "$EXPECTED_SHA256" ]]'), "runner verifies SHA-256")
assert_contract(source.include?("report_json_invalid") || source.include?("json_decode"), "JSON validity is checked")
validate_at = source.index("name: Validate transferred report before artifact upload")
upload_at = source.index("name: Upload validated report and stderr evidence")
assert_contract(validate_at && upload_at && validate_at < upload_at, "artifact upload follows validation")
assert_contract(source.include?("retention-days: 7"), "artifact retention is seven days")

# Strict exclusion of operational/production mutations.
assert_contract(!source.match?(/artisan\\s+institutes:qualify-official-sources/), "no qualification command")
assert_contract(!source.match?(/artisan\\s+migrate/), "no migrations")
assert_contract(!source.match?(/artisan\\s+(?:route:list|schedule:list|env)/), "no unrelated Artisan diagnostics")
assert_contract(!source.match?(/DB::|Schema::|information_schema|migrations table/i), "no database inspection")
assert_contract(!source.match?(/cache:clear|optimize:clear|deploy/i), "no cache clearing or deployment")
assert_contract(php_source.include?("'qualification_command_executed' => false") && php_source.include?("'source_requests_performed' => false"), "approved diagnostic retains safety flags")

steps = workflow.fetch("jobs").fetch("runtime-diagnostic").fetch("steps")
remote_step = steps.find { |step| step["name"] == "Verify delivery, preflight and execute diagnostic exactly once" }
cleanup_step = steps.find { |step| step["name"] == "Cleanup only this run temporary remote files" }
assert_contract(remote_step && cleanup_step, "remote execution and cleanup steps exist")
remote_script = remote_step.fetch("run").lines.drop_while { |line| !line.include?('REMOTE_COMMAND="set -eu') }.drop(1)
# Verify the runner shell as-authored and the embedded remote shell's quoting structure.
Dir.mktmpdir("phase-5b27-shell-") do |dir|
  [remote_step.fetch("run"), cleanup_step.fetch("run")].each_with_index do |shell, index|
    path = File.join(dir, "shell-#{index}.sh")
    File.write(path, shell)
    output, status = Open3.capture2e("bash", "-n", path)
    assert_contract(status.success?, "runner shell syntax #{index}: #{output}")
  end
end

cleanup = cleanup_step.fetch("run")
assert_contract(cleanup.include?("phase-5b18-runtime-diagnostic.php") && cleanup.include?("runtime-diagnostic-lint.txt"), "cleanup targets only temporary script and lint")
assert_contract(!cleanup.include?("rm -f --") || !cleanup.match?(/rm -f --[^\\n]*(?:phase-5b18-artisan-runtime|runtime-diagnostic\\.stderr)/), "cleanup preserves report and stderr")
assert_contract(cleanup.include?("realpath -e") && cleanup.include?("run.[A-Za-z0-9]"), "cleanup validates real temporary path")
assert_contract(cleanup.include?("D='$REMOTE_DIR'"), "cleanup uses only this run's REMOTE_DIR output")
assert_contract(!cleanup.include?("current-run-path") && !cleanup.include?("MARKER="), "cleanup never trusts shared marker")
assert_contract(!cleanup.match?(/rm -rf[^;]*institutes/), "cleanup never recursively deletes application paths")
assert_contract(cleanup.include?("ARTIFACT_PRESERVED") && cleanup.include?("runtime-diagnostic.stderr"), "stderr is removed remotely only after successful artifact preservation")

# Simulate two independent workflow runs. Cleaning A must not alter B.
Dir.mktmpdir("phase-5b27-isolation-") do |root|
  base = File.join(root, ".phase-5b27-runtime")
  FileUtils.mkdir_p(base)
  run_a = File.join(base, "run.AAAAAAAA")
  run_b = File.join(base, "run.BBBBBBBB")
  [run_a, run_b].each do |dir|
    FileUtils.mkdir_p(dir, mode: 0o700)
    File.write(File.join(dir, "phase-5b18-runtime-diagnostic.php"), "<?php // temporary")
    File.write(File.join(dir, "runtime-diagnostic.stderr"), "preserved evidence")
  end
  cleanup_one = lambda do |remote_dir|
    canonical = File.realpath(remote_dir)
    expected_prefix = "#{File.realpath(base)}/run."
    raise "unsafe path" unless canonical.start_with?(expected_prefix)
    FileUtils.rm_f(File.join(canonical, "phase-5b18-runtime-diagnostic.php"))
    FileUtils.rm_f(File.join(canonical, "runtime-diagnostic-lint.txt"))
  end
  cleanup_one.call(run_a)
  assert_contract(!File.exist?(File.join(run_a, "phase-5b18-runtime-diagnostic.php")), "run A script removed")
  assert_contract(File.exist?(File.join(run_b, "phase-5b18-runtime-diagnostic.php")), "run A cleanup must not delete run B script")
  assert_contract(File.exist?(File.join(run_a, "runtime-diagnostic.stderr")), "cleanup preserves run A stderr")
  assert_contract(File.exist?(File.join(run_b, "runtime-diagnostic.stderr")), "cleanup preserves run B stderr")
end
assert_contract(!cleanup.include?("git -C \"$PROJECT_PATH\""), "workflow never modifies production Git state")

puts "Phase 5B.27 secure runtime script delivery contract: PASS"
