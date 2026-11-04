#!/usr/bin/env ruby
# frozen_string_literal: true

require 'yaml'

root = File.expand_path('../..', __dir__)
workflow_path = File.join(root, '.github/workflows/controlled-institution-field-approvals-migration.yml')
workflow = YAML.load_file(workflow_path)
triggers = workflow['on'] || workflow[true]

abort 'ERROR: workflow must expose workflow_dispatch as its only trigger' unless triggers.is_a?(Hash) && triggers.keys == ['workflow_dispatch']
abort 'ERROR: push trigger must not exist' if triggers.key?('push')
%w[target_sha operation_reason confirm_production_operation].each do |name|
  item = triggers.fetch('workflow_dispatch').fetch('inputs').fetch(name)
  abort "ERROR: #{name} must be required text" unless item['required'] == true && item['type'] == 'string'
end
abort 'ERROR: production-operations environment is required' unless workflow.fetch('jobs').fetch('migrate')['environment'] == 'production-operations'
steps = workflow.fetch('jobs').fetch('migrate').fetch('steps')
guard_index = steps.index { |item| item.fetch('run', '').include?('scripts/ci/validate-production-dispatch.sh') }
ssh_index = steps.index { |item| item.fetch('uses', '').start_with?('appleboy/ssh-action') }
abort 'ERROR: dispatch guard must precede migration SSH action' unless guard_index && ssh_index && guard_index < ssh_index
abort 'ERROR: shared production concurrency must queue without cancellation' unless workflow.fetch('concurrency')['group'] == 'estudent-production-access' && workflow.fetch('concurrency')['cancel-in-progress'] == false && workflow.fetch('concurrency')['queue'] == 'max'

input = triggers.fetch('workflow_dispatch').fetch('inputs').fetch('confirm_migration')
abort 'ERROR: manual confirmation must be a required boolean defaulting false' unless input['required'] == true && input['type'] == 'boolean' && input['default'] == false

step = workflow.fetch('jobs').fetch('migrate').fetch('steps').find do |item|
  item['name'] == 'Verify deployed code, preflight, and run only the authorized migration'
end
abort 'ERROR: guarded production SSH step is missing' unless step

env = step.fetch('env')
abort 'ERROR: confirmation must derive exclusively from explicit manual input' unless env['CONFIRM_MIGRATION'] == '${{ inputs.confirm_migration == true }}'
abort 'ERROR: event type must never authorize migration' if env['CONFIRM_MIGRATION'].include?('github.event_name') || env['CONFIRM_MIGRATION'].include?('push')

script = step.fetch('with').fetch('script')
preflight = script.index('if [ "$PREFLIGHT_EXIT" -ne 0 ]')
read_only_gate = script.index('elif [ "$CONFIRM_MIGRATION" != "true" ]; then')
migration_command = script.index('php artisan migrate --force --path="$MIGRATION_FILE"')
post_verify = script.index('migration_status=TARGET_SCHEMA_AND_LEDGER_VERIFIED')
abort 'ERROR: preflight failure must stop before any migration' unless preflight && migration_command && preflight < migration_command
abort 'ERROR: missing/false confirmation must exit after read-only preflight' unless read_only_gate && read_only_gate < migration_command && script.include?('migration_status=READ_ONLY_PREFLIGHT_ONLY')
abort 'ERROR: migration command must occur exactly once behind the explicit confirmation gate' unless script.scan('php artisan migrate --force --path="$MIGRATION_FILE"').length == 1
abort 'ERROR: single-migration path restriction was changed' unless script.include?('MIGRATION_FILE="database/migrations/2026_10_29_000001_create_institution_field_approvals_table.php"')
abort 'ERROR: ambiguous table/ledger state must continue to refuse repair or rerun' unless script.include?('if ($tableExists || $ledgerCount !== 0)') && script.include?('refusing repair or rerun')
abort 'ERROR: deployed-code parity guard was removed' unless script.include?('application_code_parity=VERIFIED') && script.include?('git diff --quiet "$TARGET_REVISION" "$PREVIOUS_REVISION" -- $APP_CODE_PATHS')
abort 'ERROR: post-migration verification was removed' unless post_verify && script.include?('migration_ledger_count') && script.include?('approval_rows')

puts 'PASS: workflow_dispatch is the only trigger; no push trigger exists'
puts 'PASS: required manual boolean confirmation defaults false'
puts 'PASS: event type cannot authorize migration; only explicit manual true can'
puts 'PASS: missing/false confirmation stops after read-only preflight'
puts 'PASS: parity, ambiguity refusal, single-migration restriction and post-verification remain'
