#!/usr/bin/env ruby
# frozen_string_literal: true

require "yaml"
require "open3"
require "tmpdir"
require "fileutils"

root = File.expand_path("../..", __dir__)
workflow_path = File.join(root, ".github/workflows/hostinger-artisan-runtime-only.yml")
source_path = File.join(root, "scripts/ops/phase-5b18-runtime-diagnostic.php")
workflow_source = File.read(workflow_path)
php_source = File.read(source_path)
YAML.parse(workflow_source)

def assert_contract(condition, message)
  abort("Phase 5B.27 runtime delivery contract failed: #{message}") unless condition
end

workflow = YAML.load_file(workflow_path)
triggers = workflow["on"] || workflow[true]
assert_contract(triggers.is_a?(Hash) && triggers.keys == ["workflow_dispatch"], "workflow must be manually triggered only")
assert_contract(workflow.fetch("permissions").fetch("contents") == "read", "contents permission must be read-only")
assert_contract(workflow_source.include?("ref: 3b0b1bc40206bb665f34064f265c2975f5ea19ef"), "approved main SHA must be pinned")
assert_contract(workflow_source.include?("PROJECT_PATH='/home/u537436044/domains/estudent.pk/public_html/institutes'"), "production project path must be fixed")
assert_contract(workflow_source.include?("PHP_BIN='/opt/alt/php85/usr/bin/php'"), "PHP 8.5 path must be fixed")
assert_contract(workflow_source.include?("SCRIPT='scripts/ops/phase-5b18-runtime-diagnostic.php'"), "source path must be fixed")
assert_contract(workflow_source.include?("ssh-keyscan") && workflow_source.include?("ssh-keygen -lf") && workflow_source.include?("StrictHostKeyChecking=yes"), "SSH fingerprint verification must be strict")
assert_contract(workflow_source.include?("sha256sum \"$SCRIPT\"") && workflow_source.include?("sha256sum \"\\$SCRIPT\""), "runner and Hostinger must verify script SHA-256")
assert_contract(workflow_source.include?("chmod 700") && workflow_source.include?("chmod 600"), "private permissions are required")
assert_contract(workflow_source.include?("phase-5b18-artisan-runtime.json") && workflow_source.include?("262144"), "fixed private report path and 256 KiB cap are required")
assert_contract(workflow_source.include?("\"\\$PHP\" -l \"\\$SCRIPT\""), "remote lint must occur")
assert_contract(workflow_source.include?("phase_5b27_diagnostic_invocations=1"), "one-run marker must exist")
assert_contract(workflow_source.include?("steps.transfer.outputs.remote_dir !="), "partial report retrieval must run after remote execution result")
assert_contract(workflow_source.include?("no automatic retry") || workflow_source.include?("no retry"), "no automatic rerun may follow failure")
assert_contract(workflow_source.include?("runtime-diagnostic.stderr"), "stderr evidence must be preserved privately")
assert_contract(workflow_source.scan(/\$PHP.*\$SCRIPT.*--report=/).length == 1, "only one diagnostic invocation is allowed")
assert_contract(!workflow_source.match?(/artisan\s+institutes:qualify-official-sources/), "qualification must not be run")
assert_contract(!workflow_source.match?(/artisan\s+migrate/), "migrations must be excluded")
assert_contract(!workflow_source.match?(/artisan\s+(route:list|schedule:list|env)/), "unrelated operational checks must be excluded")
assert_contract(!workflow_source.match?(/DB::|Schema::|information_schema|migrations table/i), "database inspection must be excluded")
assert_contract(!workflow_source.match?(/cache:clear|optimize:clear|deploy/i), "deployment and cache clearing must be excluded")
assert_contract(workflow_source.include?("storage/app/private") && workflow_source.include?("PUBLIC_ROOT"), "report must be checked outside web root")
assert_contract(workflow_source.include?("mktemp -d") && workflow_source.include?("rmdir --"), "temporary directory must be unique and safely removed")
assert_contract(workflow_source.index("name: Validate transferred report before artifact upload") < workflow_source.index("name: Upload validated report artifact"), "artifact upload must follow validation")
assert_contract(workflow_source.include?("retention-days: 7"), "artifact retention must be seven days")
assert_contract(workflow_source.include?("EXPECTED_BYTES") && workflow_source.include?("EXPECTED_SHA256") && workflow_source.include?("report_sha256"), "report metadata validation required")
assert_contract(php_source.include?("'qualification_command_executed' => false") && php_source.include?("'source_requests_performed' => false"), "approved PHP diagnostic non-execution flags must remain")

steps = workflow.fetch("jobs").fetch("runtime-diagnostic").fetch("steps")
upload_step = steps.find { |step| step["name"] == "Upload approved script to unique private temporary directory" }
exec_step = steps.find { |step| step["name"] == "Verify delivery, preflight and execute diagnostic exactly once" }
cleanup_step = steps.find { |step| step["name"] == "Cleanup only this run temporary remote files" }
assert_contract(upload_step && exec_step && cleanup_step, "delivery, execution and cleanup steps must exist")

Dir.mktmpdir("phase-5b27-shell-") do |dir|
  [upload_step, exec_step, cleanup_step].each do |step|
    next unless step["run"]
    path = File.join(dir, "step.sh")
    File.write(path, step["run"])
    _output, status = Open3.capture2e("bash", "-n", path)
    assert_contract(status.success?, "runner bash syntax must be valid for #{step['name']}")
  end
end

puts "Phase 5B.27 runtime script delivery contract: PASS"
