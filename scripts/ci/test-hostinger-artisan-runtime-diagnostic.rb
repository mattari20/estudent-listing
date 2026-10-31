#!/usr/bin/env ruby
# frozen_string_literal: true

require "yaml"
require "open3"
require "tmpdir"
require "fileutils"
require "json"

root = File.expand_path("../..", __dir__)
workflow_path = File.join(root, ".github/workflows/hostinger-readonly-diagnostics.yml")
script_path = File.join(root, "scripts/ops/phase-5b18-runtime-diagnostic.php")
source = File.read(workflow_path)
php_source = File.read(script_path)
YAML.parse(source)

def assert_contract(condition, message)
  abort("Hostinger Artisan runtime diagnostic contract failed: #{message}") unless condition
end

required = [
  "Phase 5B.18 read-only Artisan runtime diagnosis",
  "PHP_BIN='/opt/alt/php85/usr/bin/php'",
  "PHASE_5B18_TARGET_REVISION",
  "PHASE_5B18_PARITY_REPO",
  "phase_5b18_php_lint=PASS",
  "runtime-diagnostic.stderr",
  "install -m 600",
  "mv -n",
  "phase-5b18-artisan-runtime.json",
  "DIAG_BYTES",
  "EXISTS_REFUSED_TO_OVERWRITE"
]
required.each { |fragment| assert_contract(source.include?(fragment), "workflow missing #{fragment}") }

assert_contract(source.include?('"$PHP_BIN" -l "$DIAG_SCRIPT"'), "temporary/standalone PHP must be linted before execution")
assert_contract(source.include?('chmod 700 "$DIAG_ROOT"'), "temporary diagnostic directory must be private")
assert_contract(source.include?('2> "$DIAG_STDERR"'), "PHP stderr must be captured in a restricted temporary log")
assert_contract(source.include?('sed -n \'1,80p\' "$DIAG_STDERR"'), "diagnostic stderr must be surfaced when report generation fails")
assert_contract(source.scan("PHP_BIN='/opt/alt/php85/usr/bin/php'").length == 1, "approved PHP binary must be initialized exactly once")
assert_contract(!source.match?(/artisan\s+institutes:qualify-official-sources\s+--/), "workflow must not invoke qualification or a source request")
assert_contract(!source.include?("artisan institutes:qualify-official-sources"), "workflow must not invoke qualification command")
assert_contract(source.include?("artisan list --raw --no-ansi") && source.include?("artisan help"), "native Artisan inspection must remain available")
assert_contract(source.include?("scripts/ops/phase-5b18-runtime-diagnostic.php"), "workflow must execute the committed diagnostic script")

php_required = [
  "REPORT_LIMIT_BYTES = 262144",
  "overall_status",
  "exception_class",
  "source_hashes",
  "composer_autoload",
  "bootstrap_and_command_registry",
  "bootstrap_cache_inventory",
  "two_way_file_parity",
  "runtime_inventory",
  "qualification_command_registered",
  "source_option_present",
  "artisan_entrypoint",
  "reflection_source_file",
  "autload",
  "fopen($temporary, 'x')",
  "rename($temporary, $reportPath)",
  "chmod($reportPath, 0600)",
  "refusing to overwrite report",
  "source_requests_performed",
  "qualification_command_executed"
]
php_required.each { |fragment| assert_contract(php_source.include?(fragment), "PHP script missing #{fragment}") }

assert_contract(php_source.include?("PHASE_5B18_TARGET_REVISION") == false, "PHP diagnostic must read expected revision from explicit environment")
assert_contract(php_source.include?("getenv('PHASE_5B18_TARGET_REVISION')"), "expected revision must be read from environment")
assert_contract(php_source.include?("getenv('PHASE_5B18_PARITY_REPO')"), "isolated repository must be read from environment")
assert_contract(!php_source.include?("institutes:qualify-official-sources --"), "PHP diagnostic must not invoke qualification")

# Exercise script lint, partial-report generation, no-overwrite, atomic report and mode bits
php = ENV.fetch("PHP_BIN", "php")
lint_out, lint_status = Open3.capture2e(php, "-l", script_path)
assert_contract(lint_status.success?, "PHP diagnostic syntax invalid: #{lint_out}")

Dir.mktmpdir("phase-5b21-contract-") do |dir|
  report = File.join(dir, "runtime.json")
  repo = File.join(dir, "empty-repo")
  Dir.mkdir(repo)
  env = {
    "PHASE_5B18_TARGET_REVISION" => "invalid-revision-for-partial-test",
    "PHASE_5B18_PARITY_REPO" => repo
  }
  _out, status = Open3.capture2e(env, php, script_path, "--report=#{report}")
  assert_contract(File.file?(report), "partial JSON report must exist after inspection failures (exit #{status.exitstatus})")
  payload = JSON.parse(File.read(report))
  assert_contract(payload["overall_status"] == "FAIL", "inspection failures must mark report FAIL")
  assert_contract(payload["steps"].is_a?(Hash) && payload["errors"].is_a?(Array), "partial report must preserve step-level errors")
  assert_contract(payload["errors"].any? { |error| error["step"] == "source_hashes" }, "failed step name must be recorded")
  assert_contract((File.stat(report).mode & 0o777) == 0o600, "report permissions must be 0600")
  size = File.size(report)
  assert_contract(size <= 262_144, "report exceeds 256 KiB")
  original = File.binread(report)
  _second_out, second_status = Open3.capture2e(env, php, script_path, "--report=#{report}")
  assert_contract(second_status.exitstatus == 65, "existing report must not be overwritten")
  assert_contract(File.binread(report) == original, "no-overwrite guard must preserve existing report")
end

puts "Hostinger Artisan runtime diagnostic contract: PASS"
