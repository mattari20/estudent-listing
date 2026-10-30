#!/usr/bin/env ruby
# frozen_string_literal: true

require "yaml"

workflow_path = File.expand_path("../../.github/workflows/hostinger-gcu-single-source-qualification.yml", __dir__)
source = File.read(workflow_path)
YAML.parse(source)

def assert_contract(condition, message)
  abort("GCU qualification workflow contract failed: #{message}") unless condition
end

assert_contract(source.include?("workflow_dispatch:"), "manual workflow_dispatch trigger is required")
assert_contract(source.include?('default: false'), "explicit confirmation must default to false")
assert_contract(!source.include?("appleboy/scp-action"), "the upload-oriented SCP action must not be used")
assert_contract(source.include?('"$HOSTINGER_USER@$HOSTINGER_HOST:$REMOTE_REPORT" "$PARTIAL_FILE"'), "SCP must pull the fixed Hostinger file to the runner")
assert_contract(source.include?("REMOTE_REPORT='/home/u537436044/domains/estudent.pk/public_html/institutes/storage/app/private/source-qualification/gcu-lahore-phase-5b14-20261030.json'"), "remote project and private report path must remain fixed")
assert_contract(source.include?("gcu-lahore-phase-5b14-20261030.json"), "fixed GCU report filename must remain")
assert_contract(source.include?("1048576"), "1 MiB cap must be enforced")
assert_contract(source.include?("timeout 120s scp") && source.include?("timeout-minutes: 2"), "SCP transfer must have a two-minute timeout")
assert_contract(source.include?('sha256sum "$FILE"') && source.include?('[[ "$ACTUAL_SHA256" == "$EXPECTED_SHA256" ]]'), "Hostinger SHA-256 must be independently compared on runner")
assert_contract(source.include?('[[ "$SIZE" == "$EXPECTED_SIZE" ]]'), "exact Hostinger/runner byte count must be compared")
assert_contract(source.include?('[[ "$EXPECTED_SHA256" =~ ^[a-fA-F0-9]{64}$ ]]'), "remote SHA-256 metadata must be validated")
assert_contract(source.include?('trap \'rm -f "$PARTIAL_FILE"\' EXIT') && source.include?('mv "$PARTIAL_FILE" "$DEST_FILE"'), "partial downloads must be removed and only completed files promoted")
assert_contract(source.index('name: Verify transferred report integrity') < source.index('name: Upload controlled GCU qualification report'), "artifact upload must follow integrity validation")
assert_contract(source.include?('if-no-files-found: error') && source.include?('retention-days: 7'), "artifact upload must fail closed and retain seven days")
assert_contract(source.scan(/artisan institutes:qualify-official-sources/).length == 1, "exactly one Artisan invocation must exist")
assert_contract(source.include?('--stage=hec-universities') && source.include?('--source=gcu-lahore'), "Artisan scope must remain exactly GCU in HEC universities")
assert_contract(source.include?('($d["stage"] ?? null) !== "hec-universities"') &&
                source.include?('($d["source_filter"] ?? null) !== "gcu-lahore"') &&
                source.include?('($d["source_count"] ?? null) !== 1') &&
                source.include?('($d["results"][0]["source_id"] ?? null) !== "gcu-lahore"'), "exact GCU-only JSON validation must remain")
assert_contract(source.include?('EXPECTED_HOST_FINGERPRINT: ${{ secrets.HOSTINGER_SSH_FINGERPRINT }}') &&
                source.include?('StrictHostKeyChecking=yes') &&
                source.include?('fingerprint: ${{ secrets.HOSTINGER_SSH_FINGERPRINT }}'), "both SSH paths must pin the Hostinger host key")
assert_contract(source.include?('git -C "$PARITY_REPO" fetch') && source.include?('PARITY_REPO="$(mktemp -d') &&
                !source.include?('git -C "$PROJECT_PATH" fetch') &&
                !source.include?('git -C "$PROJECT_PATH" checkout'), "revision preflight must use an isolated repository, not production refs/index/working tree")
assert_contract(!source.include?('base64') && !source.match?(/cat\s+["']?\$REPORT_PATH/) &&
                !source.include?('cat "$FILE"'), "report payload must not be printed or base64-encoded")
assert_contract(source.include?('echo "expected_size=$EXPECTED_SIZE" >> "$GITHUB_OUTPUT"') &&
                source.include?('echo "expected_sha256=$EXPECTED_SHA256" >> "$GITHUB_OUTPUT"') &&
                !source.include?('REPORT_CONTENT') && !source.include?('REPORT_BASE64'), "only non-sensitive size/digest metadata may use GITHUB_OUTPUT")
assert_contract(source.include?('timeout 120s ssh') && source.include?('timeout 120s scp'), "metadata and file-transfer SSH operations must be bounded")
assert_contract(!source.include?('continue-on-error: true') && !source.include?('if: always()'), "transfer/validation failures must stop before artifact upload and must not be ignored")

puts "GCU qualification workflow syntax and security contract: PASS"
