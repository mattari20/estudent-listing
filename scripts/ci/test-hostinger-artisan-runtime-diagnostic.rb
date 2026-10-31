#!/usr/bin/env ruby
# frozen_string_literal: true

require "yaml"

workflow_path = File.expand_path("../../.github/workflows/hostinger-readonly-diagnostics.yml", __dir__)
source = File.read(workflow_path)
YAML.parse(source)

def assert_contract(condition, message)
  abort("Hostinger Artisan runtime diagnostic contract failed: #{message}") unless condition
end

required = [
  "Phase 5B.18 read-only Artisan runtime diagnosis",
  "PHP_BINARY",
  "PHP_VERSION",
  "working_directory",
  "artisan_entrypoint",
  "qualification_command_registered",
  "reflection_source_file",
  "source_option_present",
  "composer_autoload",
  "bootstrap_cache",
  "two_way_file_parity",
  "unexpected_extra_files",
  "mutation_performed",
  "source_requests_performed",
  "qualification_command_executed",
  "storage/app/private/diagnostics/phase-5b18-artisan-runtime.json",
  "PHASE_5B18_TARGET_REVISION",
  "PHASE_5B18_PARITY_REPO"
]
required.each { |fragment| assert_contract(source.include?(fragment), "missing #{fragment}") }

assert_contract(source.include?("TARGET_REVISION") && source.include?("git -C \"$PARITY_REPO\" fetch"), "expected revision must be inspected in isolated Git repository")
assert_contract(source.include?("install -m 600 \"$DIAG_REPORT\" \"$DIAG_FINAL\""), "report must be private and permission-restricted")
assert_contract(source.include?("DIAG_BYTES\" -le 262144"), "diagnostic report must be bounded to 256 KiB")
assert_contract(source.include?("[ ! -e \\"$DIAG_FINAL\\" ]") && source.include?("EXISTS_REFUSED_TO_OVERWRITE"), "report overwrite must be refused")
assert_contract(source.include?(%q{"$PHP_BIN" artisan env}) && source.include?(%q{"$PHP_BIN" artisan route:list}), "existing read-only diagnostics must use the same PHP binary")
assert_contract(!source.include?("artisan institutes:qualify-official-sources"), "diagnostic workflow must not invoke qualification command")
assert_contract(!source.match?(/artisan\s+institutes:qualify-official-sources\s+--/), "diagnostic must not invoke a source request")
assert_contract(source.include?("RecursiveDirectoryIterator") && source.include?("unexpected_extra_files"), "diagnostic must inspect extra files in production roots")
assert_contract(source.include?("composer/autoload_classmap.php") && source.include?("composer/autoload_psr4.php"), "Composer-generated metadata must be inventoried")
assert_contract(source.include?("bootstrap/cache"), "Laravel generated cache must be inventoried")
assert_contract(source.include?("hash_file('sha256'"), "diagnostic must hash deployed files")
assert_contract(source.include?("ReflectionClass") && source.include?("signature"), "loaded command class and signature must be reflected")
assert_contract(source.include?("source_filter") == false, "diagnostic must not execute or create a qualification report")

puts "Hostinger Artisan runtime diagnostic contract: PASS"
