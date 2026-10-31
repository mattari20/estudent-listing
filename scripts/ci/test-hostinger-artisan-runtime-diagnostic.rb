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
assert_contract(php_source.include?("list --raw --no-ansi") && php_source.include?("help institutes:qualify-official-sources --no-ansi"), "native Artisan list/help inspection must remain available")
assert_contract(php_source.include?('(?:\s|$)'), "native list must match the exact command name")
assert_contract(php_source.include?('implode("\n", $helpOutput)'), "native help lines must be joined with real newlines")
assert_contract(php_source.include?(%q{'exit_code' => $listCode}) && php_source.include?(%q{'exit_code' => $helpCode}), "native command exit codes must be recorded")
assert_contract(php_source.include?(%q{'output_excerpt' => $sanitize}) && php_source.include?(%q{'options_excerpt' => $sanitize}), "native output must be bounded and sanitized")
assert_contract(php_source.include?(%q{'source_option_present' => $sourcePresent}), "native --source detection must be recorded")
assert_contract(source.include?("scripts/ops/phase-5b18-runtime-diagnostic.php"), "workflow must execute the committed diagnostic script")

php_required = [
  "REPORT_LIMIT_BYTES = 262144",
  "overall_status",
  "exception_class",
  "source_hashes",
  "composer_autoload",
  "bootstrap_and_command_registry",
  "native_artisan_inspection",
  "bootstrap_cache_inventory",
  "two_way_file_parity",
  "runtime_inventory",
  "qualification_command_registered",
  "source_option_present",
  "artisan_entrypoint",
  "reflection_source_file",
  "native_help",
  "source_option_present",
  %q{fopen($temporary, 'x')},
  %q{rename($temporary, $reportPath)},
  %q{chmod($reportPath, 0600)},
  "refusing to overwrite report",
  "source_requests_performed",
  "qualification_command_executed"
]
php_required.each { |fragment| assert_contract(php_source.include?(fragment), "PHP script missing #{fragment}") }

assert_contract(php_source.include?("getenv('PHASE_5B18_TARGET_REVISION')"), "expected revision must be read from environment")
assert_contract(php_source.include?("getenv('PHASE_5B18_PARITY_REPO')"), "isolated repository must be read from environment")
assert_contract(!php_source.include?(%q{exec(escapeshellarg($php) . ' ' . escapeshellarg($artisan) . ' institutes:qualify-official-sources}), "PHP diagnostic must not execute qualification")

# Exercise script lint, partial-report generation, no-overwrite, atomic report and mode bits
php = ENV.fetch("PHP_BIN", "php")
lint_out, lint_status = Open3.capture2e(php, "-l", script_path)
assert_contract(lint_status.success?, "PHP diagnostic syntax invalid: #{lint_out}")

Dir.mktmpdir("phase-5b21-contract-") do |dir|
  empty_repo = File.join(dir, "empty-repo")
  Dir.mkdir(empty_repo)
  valid_report = File.join(dir, "missing-env.json")
  _out, missing_env_status = Open3.capture2e({ "PHASE_5B18_TARGET_REVISION" => nil, "PHASE_5B18_PARITY_REPO" => nil }, php, script_path, "--report=#{valid_report}")
  assert_contract(File.file?(valid_report), "missing environment must produce a partial report")
  missing_env = JSON.parse(File.read(valid_report))
  assert_contract(missing_env["overall_status"] == "FAIL", "missing environment must fail overall status")
  assert_contract(missing_env["errors"].any? { |error| error["exception_class"] == "RuntimeException" }, "exception class must be preserved")
  assert_contract(missing_env_status.exitstatus != 0, "missing environment must preserve nonzero exit")
  File.delete(valid_report)

  partial_report = File.join(dir, "partial-report.json")
  env = { "PHASE_5B18_TARGET_REVISION" => "invalid-revision-for-partial-test", "PHASE_5B18_PARITY_REPO" => empty_repo }
  _out, partial_status = Open3.capture2e(env, php, script_path, "--report=#{partial_report}")
  assert_contract(File.file?(partial_report), "partial JSON report must exist after inspection failure")
  payload = JSON.parse(File.read(partial_report))
  assert_contract(payload["overall_status"] == "FAIL", "inspection failures must mark report FAIL")
  assert_contract(payload["steps"].is_a?(Hash) && payload["errors"].is_a?(Array), "partial report must preserve step results and errors")
  assert_contract(payload["errors"].any? { |error| error["step"] == "source_hashes" }, "failed step name must be recorded")
  assert_contract((File.stat(partial_report).mode & 0o777) == 0o600, "report permissions must be 0600")
  assert_contract(File.size(partial_report) <= 262_144, "report exceeds 256 KiB")
  original = File.binread(partial_report)
  _again, second_status = Open3.capture2e(env, php, script_path, "--report=#{partial_report}")
  assert_contract(second_status.exitstatus == 65, "existing report must not be overwritten")
  assert_contract(File.binread(partial_report) == original, "no-overwrite guard must preserve the original report")
  assert_contract(Dir.children(dir).none? { |name| name.include?(".tmp.") }, "temporary report files must be cleaned up")

  malformed = File.join(dir, "malformed.php")
  File.write(malformed, "<?php function broken( {")
  malformed_output, malformed_status = Open3.capture2e(php, "-l", malformed)
  assert_contract(!malformed_status.success?, "malformed PHP must fail lint")
  assert_contract(malformed_output.match?(/Parse error|syntax error/i), "PHP parser evidence must be preserved")
end


# Exercise native Artisan evidence independently from Laravel's separately bootstrapped registry.
Dir.mktmpdir("phase-5b21-native-") do |dir|
  artisan = File.join(dir, "artisan")
  File.write(artisan, "<?php // native command fixture\n")
  fake_php = File.join(dir, "fake-php")
  File.write(fake_php, <<~SH)
    #!/bin/sh
    if [ "$2" = "list" ]; then
      printf '%s\n' 'institutes:qualify-official-sources'
      printf '%s\n' 'token=SHOULD_NOT_LEAK'
      exit "\${FAKE_LIST_EXIT:-0}"
    fi
    if [ "$2" = "help" ]; then
      printf '%s\n' 'Usage: institutes:qualify-official-sources'
      if [ "\${FAKE_SOURCE_OPTION:-yes}" = "yes" ]; then
        printf '%s\n' '  --source[=SOURCE]'
      fi
      exit "\${FAKE_HELP_EXIT:-0}"
    fi
    exit 99
  SH
  FileUtils.chmod(0o700, fake_php)

  run_native = lambda do |name, extra_env = {}|
    report_path = File.join(dir, "#{name}.json")
    env = {
      "PHASE_5B18_TARGET_REVISION" => "fixture-revision",
      "PHASE_5B18_PARITY_REPO" => dir,
      "PHASE_5B18_NATIVE_PHP" => fake_php,
      "PHASE_5B18_ARTISAN_PATH" => artisan
    }.merge(extra_env)
    output, status = Open3.capture2e(env, php, script_path, "--report=#{report_path}", chdir: dir)
    assert_contract(File.file?(report_path), "#{name}: partial JSON report must be written")
    payload = JSON.parse(File.read(report_path))
    [payload, status, output]
  end

  with_source, with_source_status, = run_native.call("source-present")
  native = with_source.dig("artisan", "native_help")
  assert_contract(native && native["source_option_present"] == true, "--source must be detected when present")
  assert_contract(with_source.dig("steps", "native_artisan_inspection") == "PASS", "native list/help success must be recorded")
  assert_contract(with_source.dig("steps", "composer_autoload") == "FAIL", "missing Composer autoload must be captured as a failed step")
  list_lines = with_source.dig("artisan", "native_list", "output_excerpt").join("\n")
  assert_contract(!list_lines.include?("SHOULD_NOT_LEAK") && list_lines.include?("[REDACTED]"), "native output secrets must be redacted")
  assert_contract(File.size(File.join(dir, "source-present.json")) <= 262_144, "native partial report must remain within 256 KiB")
  assert_contract(with_source_status.exitstatus != 0, "overall diagnostic remains failed when fixture lacks the deployed application")

  without_source, = run_native.call("source-absent", { "FAKE_SOURCE_OPTION" => "no" })
  assert_contract(without_source.dig("artisan", "native_help", "source_option_present") == false, "--source absence must be recorded")

  list_failure, list_status, = run_native.call("list-failure", { "FAKE_LIST_EXIT" => "7" })
  assert_contract(list_failure.dig("artisan", "native_list", "exit_code") == 7, "native list exit status must be preserved")
  assert_contract(list_failure.dig("steps", "native_artisan_inspection") == "FAIL" && list_status.exitstatus != 0, "native list failure must fail its step and process")

  help_failure, help_status, = run_native.call("help-failure", { "FAKE_HELP_EXIT" => "9" })
  assert_contract(help_failure.dig("artisan", "native_help", "exit_code") == 9, "native help exit status must be preserved")
  assert_contract(help_failure.dig("steps", "native_artisan_inspection") == "FAIL" && help_status.exitstatus != 0, "native help failure must fail its step and process")

  vendor = File.join(dir, "vendor")
  Dir.mkdir(vendor)
  File.write(File.join(vendor, "autoload.php"), <<~PHP)
    <?php
    class PhaseFiveBTestLoader {
      public function findFile(string $class): false { return false; }
    }
    return new PhaseFiveBTestLoader();
  PHP
  bootstrap_failure, = run_native.call("bootstrap-failure")
  assert_contract(bootstrap_failure.dig("steps", "composer_autoload") == "PASS", "fixture Composer autoload must pass before bootstrap test")
  assert_contract(bootstrap_failure.dig("steps", "bootstrap_and_command_registry") == "FAIL", "Laravel bootstrap failure must be captured")
end

puts "Hostinger Artisan runtime diagnostic contract: PASS"
