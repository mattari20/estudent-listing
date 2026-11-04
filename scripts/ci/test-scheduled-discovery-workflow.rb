#!/usr/bin/env ruby
# frozen_string_literal: true
# Static regression checks for manually authorized governed institute discovery.

require 'yaml'

root = File.expand_path('../..', __dir__)
workflow_path = File.join(root, '.github/workflows/scheduled-institute-discovery.yml')
workflow = YAML.load_file(workflow_path)
triggers = workflow['on'] || workflow[true]
abort 'FAIL: discovery must use workflow_dispatch only; automatic schedule/workflow_run handoffs are prohibited' unless triggers.is_a?(Hash) && triggers.keys == ['workflow_dispatch']

inputs = triggers.fetch('workflow_dispatch').fetch('inputs')
%w[target_sha operation_reason confirm_production_operation confirm_discovery].each do |name|
  abort "FAIL: required dispatch input #{name} is missing" unless inputs.fetch(name)['required'] == true
end

concurrency = workflow.fetch('concurrency')
abort 'FAIL: shared production concurrency policy is missing' unless concurrency['group'] == 'estudent-production-access' && concurrency['cancel-in-progress'] == false && concurrency['queue'] == 'max'

discover = workflow.fetch('jobs').fetch('discover')
abort 'FAIL: discover job must use the production-operations environment' unless discover['environment'] == 'production-operations'
steps = discover.fetch('steps')
guard_index = steps.index { |step| step.fetch('run', '').include?('scripts/ci/validate-production-dispatch.sh') }
ssh_index = steps.index { |step| step.fetch('uses', '').start_with?('appleboy/ssh-action') }
abort 'FAIL: dispatch guard must run before the production SSH action' unless guard_index && ssh_index && guard_index < ssh_index
guard = steps.fetch(guard_index)
guard_env = guard.fetch('env', {})
abort 'FAIL: discovery guard must bind the dedicated confirmation input' unless guard_env['OPERATION_CONFIRMATION'] == '${{ inputs.confirm_discovery }}'
abort 'FAIL: discovery guard must require the exact operation-specific phrase' unless guard_env['EXPECTED_OPERATION_CONFIRMATION'] == 'AUTHORIZE-GOVERNED-INSTITUTE-DISCOVERY'

required = {
  'explicit dependencies include revision resolver and verified handoff' =>
    discover['needs'] == ['resolve-target', 'source-refresh-handoff'],
  'skipped handoff does not suppress eligible manual runs' =>
    discover.fetch('if', '').include?("always() && needs.resolve-target.result == 'success'"),
  'legacy handoff validation remains fail-closed if invoked' =>
    discover.fetch('if', '').include?("github.event_name != 'workflow_run' || needs.source-refresh-handoff.outputs.verified == 'true'"),
  'discovery still uses the guarded Hostinger SSH action' =>
    ssh_index != nil,
  'canonical mutation safeguards remain present' =>
    steps.any? { |step| step.fetch('with', {}).fetch('script', '').include?('echo "canonical_mutations=NONE"') } &&
    steps.any? { |step| step.fetch('with', {}).fetch('script', '').include?('echo "automatic_approval=NONE"') } &&
    steps.any? { |step| step.fetch('with', {}).fetch('script', '').include?('echo "automatic_publication=NONE"') }
}

failed = required.select { |_description, passed| !passed }
required.each do |description, passed|
  puts "#{passed ? 'PASS' : 'FAIL'}: #{description}"
end
abort "#{failed.length} discovery workflow contract check(s) failed" unless failed.empty?

puts 'PASS: discovery has no automatic push, pull-request, schedule, or workflow_run trigger'
puts 'PASS: exact-SHA and discovery-specific authorization guard precede SSH'
