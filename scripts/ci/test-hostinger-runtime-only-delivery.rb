#!/usr/bin/env ruby
# frozen_string_literal: true

require "yaml"
require "open3"
require "tmpdir"

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
diagnostic_lines = source.lines.select { |line| line.include?('\\$PHP\\" \\"\\$SCRIPT\\" --report=') }
assert_contract(diagnostic_lines.length == 1, "the standalone PHP diagnostic is invoked exactly once")
assert_contract(source.include?("phase_5b27_diagnostic_invocations=1"), "one-invocation audit marker exists")
assert_contract(source.include?('\\$PHP\\" -l \\"\\$SCRIPT\\"'), "PHP lint happens before the single execution")
assert_contract(source.include?("script_checksum_mismatch") && source.include?("source_revision_hash_mismatch"), "checksum mismatch blocks execution")
assert_contract(source.include?("scp -q") && source.include?("timeout 120s scp"), "transfer is authenticated and bounded")
assert_contract(source.include?("REMOTE_DIR") && source.include?("mktemp -d"), "each transfer uses a unique temp directory")

# Failure evidence is retrieved after the SSH command, without repeating PHP execution.
assert_contract(source.include?("remote_ssh_exit=$STATUS"), "remote exit is captured")
assert_contract(source.include?("always() && steps.transfer.outputs.remote_dir != ''"), "report retrieval is attempted after remote failure")
assert_contract(source.include?("no retry") || source.include?("No retry"), "failed execution is never retried")
assert_contract(source.include?("runtime-diagnostic.stderr"), "stderr evidence is preserved")
assert_contract(source.include?("report-metadata.txt"), "private report metadata is preserved")

# Transfer validation: byte count, digest and JSON validation all precede artifact creation.
assert_contract(source.include?('[[ "$BYTES" == "$EXPECTED_BYTES"'), "runner verifies exact byte count")
assert_contract(source.include?('[[ "$SHA" == "$EXPECTED_SHA256" ]]'), "runner verifies SHA-256")
assert_contract(source.include?("report_json_invalid") || source.include?("json_decode"), "JSON validity is checked")
validate_at = source.index("name: Validate transferred report before artifact upload")
upload_at = source.index("name: Upload validated report artifact")
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
assert_contract(cleanup.include?("realpath -e") && cleanup.include?("run.*"), "cleanup validates real temporary path")
assert_contract(!cleanup.include?("git -C \"$PROJECT_PATH\""), "workflow never modifies production Git state")

puts "Phase 5B.27 secure runtime script delivery contract: PASS"
