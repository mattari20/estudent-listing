#!/usr/bin/env ruby
# frozen_string_literal: true

require 'yaml'
require 'date'
require 'set'

ROOT = File.expand_path('../..', __dir__)
WORKFLOWS = File.join(ROOT, '.github/workflows')

READ_ONLY = {
  'audit-next-institution-catalog.yml' => 'production-readonly-diagnostics',
  'automated-live-integrity-audit.yml' => 'production-readonly-diagnostics',
  'diagnose-hostinger-artisan-path.yml' => 'production-readonly-diagnostics',
  'discovery-runtime-readiness.yml' => 'production-readonly-diagnostics',
  'hostinger-artisan-runtime-only.yml' => 'production-readonly-diagnostics',
  'hostinger-readonly-diagnostics.yml' => 'production-readonly-diagnostics',
  'hostinger-readonly-offering-gap-audit.yml' => 'offering-gap-readonly-audit',
  'institutes-diagnostics-verify.yml' => 'production-readonly-diagnostics',
  'institutes-private-diagnostics-report.yml' => 'production-readonly-diagnostics',
  'institutes-public-monitor.yml' => 'production-readonly-diagnostics',
  'offering-gap-preflight.yml' => 'offering-gap-readonly-audit',
  'public-browser-runtime-diagnostics.yml' => 'production-readonly-diagnostics',
  'official-source-refresh-readiness.yml' => 'production-readonly-diagnostics',
  'readonly-reset-inventory.yml' => 'production-readonly-diagnostics',
  'step3-candidate-audit.yml' => 'production-readonly-diagnostics',
  'step3-record-level-remediation-audit.yml' => 'production-readonly-diagnostics',
  'verify-uvas-public-directory.yml' => 'production-readonly-diagnostics'
}.freeze

BLOCKED_OPERATIONS = %w[
  controlled-discovery-outcome-migration.yml
  controlled-hostinger-code-deploy.yml
  controlled-import-batch-duplicate-rows-migration.yml
  controlled-institution-field-approvals-migration.yml
  controlled-official-source-refresh-migration.yml
  controlled-pakistan-geography-seed.yml
  hostinger-gcu-single-source-qualification.yml
  scheduled-institute-discovery.yml
].to_set.freeze

RESET_WORKFLOW = 'secure-institutes-admin-recovery.yml'

CONCURRENCY_GROUPS = {
  'controlled-discovery-outcome-migration.yml' => ['controlled-discovery-outcome-migration', false],
  'controlled-hostinger-code-deploy.yml' => ['controlled-hostinger-code-deployment', false],
  'controlled-import-batch-duplicate-rows-migration.yml' => ['controlled-import-batch-duplicate-rows-migration', false],
  'controlled-institution-field-approvals-migration.yml' => ['controlled-institution-field-approvals-migration', false],
  'controlled-pakistan-geography-seed.yml' => ['controlled-pakistan-geography-seed', false],
  'institutes-diagnostics-verify.yml' => ['institutes-protected-diagnostics-verification', false],
  'institutes-private-diagnostics-report.yml' => ['institutes-private-diagnostics-report', false],
  'institutes-public-monitor.yml' => ['institutes-public-site-monitor', true],
  'public-browser-runtime-diagnostics.yml' => ['public-browser-runtime-diagnostics', false],
  'step3-candidate-audit.yml' => ['step3-discovery-candidate-audit', false],
  'step3-record-level-remediation-audit.yml' => ['step3-record-level-remediation-audit', false],
  'offering-gap-preflight.yml' => ['offering-gap-preflight', false]
}.freeze

def fail!(message)
  warn "FAIL: #{message}"
  exit 1
end

def pass(message)
  puts "PASS: #{message}"
end

def parse_workflow(path)
  YAML.load_file(path, permitted_classes: [Date, Time], aliases: true)
rescue ArgumentError
  YAML.load_file(path)
end

def events_for(workflow)
  workflow['on'] || workflow[true] || {}
end

def production_job?(job)
  serialized = YAML.dump(job)
    serialized.match?(/appleboy\/ssh-action|secrets\.(?:PRODUCTION_READONLY_|OFFERING_GAP_|HOSTINGER_SSH_|INSTITUTES_)/) ||
    serialized.include?('https://estudent.pk/institutes')
end
def guard_index(steps)
  steps.index do |step|
    step.is_a?(Hash) && step.fetch('run', '').include?('scripts/ci/validate-production-dispatch.sh')
  end
end

def first_access_index(steps)
  steps.index do |step|
    serialized = YAML.dump(step)
    serialized.match?(/appleboy\/ssh-action|secrets\.(?:PRODUCTION_READONLY_|OFFERING_GAP_|HOSTINGER_SSH_|INSTITUTES_)/) ||
      serialized.include?('https://estudent.pk/institutes')
  end
end
def blocked?(job)
  value = job['if']
  value == false || value.to_s.strip == '${{ false }}'
end

paths = Dir.glob(File.join(WORKFLOWS, '*.{yml,yaml}')).sort
fail!("expected exactly 35 workflow files; found #{paths.length}") unless paths.length == 35
names = paths.map { |path| File.basename(path) }

confirmation_phrases = []

READ_ONLY.each do |filename, expected_environment|
  path = File.join(WORKFLOWS, filename)
  fail!("missing read-only production workflow #{filename}") unless File.file?(path)
  workflow = parse_workflow(path)
  events = events_for(workflow)
  fail!("#{filename} must be workflow_dispatch only; got #{events.keys.inspect}") unless events.keys.map(&:to_s) == ['workflow_dispatch']

  inputs = events.fetch('workflow_dispatch', {}).fetch('inputs', {})
  %w[target_sha operation_reason confirm_production_operation].each do |key|
    fail!("#{filename} missing required dispatch input #{key}") unless inputs.dig(key, 'required') == true
  end

  jobs = workflow.fetch('jobs', {})
  production_jobs = jobs.select { |_name, job| production_job?(job) }
  fail!("#{filename} has no detected production-access job") if production_jobs.empty?

  production_jobs.each do |job_name, job|
    fail!("#{filename}:#{job_name} environment must be #{expected_environment}") unless job['environment'] == expected_environment
    fail!("#{filename}:#{job_name} has job-scope production secrets") if YAML.dump(job.fetch('env', {})).match?(/secrets\./)
    steps = job.fetch('steps', [])
    gidx = guard_index(steps)
    aidx = first_access_index(steps)
    fail!("#{filename}:#{job_name} is missing executable dispatch guard") unless gidx
    fail!("#{filename}:#{job_name} guard must precede every production credential/access step") if aidx && gidx >= aidx
    if aidx
      first_access = YAML.dump(steps[aidx])
      if first_access.match?(/secrets\.|appleboy\/ssh-action/) &&
         steps[aidx].fetch('name', '') !~ /Validate environment-scoped production credentials|Validate requested revision and read-only credentials/
        fail!("#{filename}:#{job_name} must validate environment-scoped credentials before SSH/API access")
      end
    end
    guard = steps[gidx]
    env = guard.fetch('env', {})
    %w[TARGET_SHA OPERATION_REASON CONFIRM_PRODUCTION_OPERATION EXPECTED_CONFIRMATION].each do |key|
      fail!("#{filename}:#{job_name} guard missing #{key}") if env[key].to_s.empty?
    end
    phrase = env['EXPECTED_CONFIRMATION'].to_s
    fail!("#{filename}:#{job_name} uses a generic confirmation phrase") if phrase == 'AUTHORIZE-PRODUCTION-OPERATION'
    fail!("#{filename}:#{job_name} confirmation phrase is not operation-specific") unless phrase.match?(/\AAUTHORIZE-[A-Z0-9-]{8,}\z/)
    fail!("#{filename}:#{job_name} confirmation phrase is reused") if confirmation_phrases.include?(phrase)
    confirmation_phrases << phrase
  end

  fail!("#{filename} exposes workflow-level secrets") if YAML.dump(workflow.fetch('env', {})).match?(/secrets\./)
  pass("#{filename}: manual-only, #{expected_environment}, guard before access")
end

pass("#{confirmation_phrases.length} read-only operation confirmations are unique")

(BLOCKED_OPERATIONS + [RESET_WORKFLOW]).each do |filename|
  path = File.join(WORKFLOWS, filename)
  fail!("missing blocked production workflow #{filename}") unless File.file?(path)
  workflow = parse_workflow(path)
  events = events_for(workflow)
  fail!("#{filename} must not have automatic triggers") unless events.keys.map(&:to_s) == ['workflow_dispatch']
  jobs = workflow.fetch('jobs', {})
  protected_jobs = jobs.select { |_name, job| production_job?(job) }
  fail!("#{filename} has no production job to block") if protected_jobs.empty?
  protected_jobs.each do |job_name, job|
    fail!("#{filename}:#{job_name} is not explicitly fail-closed") unless blocked?(job)
    if filename == RESET_WORKFLOW
      fail!("#{filename}:#{job_name} must use production-reset") unless job['environment'] == 'production-reset'
    else
      fail!("#{filename}:#{job_name} must not use a read-only or undeclared operations environment") if job['environment']
    end
  end
  pass("#{filename}: production execution explicitly blocked pending owner authorization")
end


# Preserve workflow-specific serialization/cancellation contracts.
CONCURRENCY_GROUPS.each do |filename, (expected_group, expected_cancel)|
  workflow = parse_workflow(File.join(WORKFLOWS, filename))
  concurrency = workflow.fetch('concurrency', {})
  fail!("#{filename} concurrency group changed") unless concurrency['group'] == expected_group
  fail!("#{filename} cancel-in-progress changed") unless concurrency['cancel-in-progress'] == expected_cancel
end
pass('workflow-specific concurrency groups and cancellation semantics are preserved')


# The three owner-confirmed environment names are the only workflow environment
# names allowed. A fourth environment must never be silently introduced.
all_yaml = paths.map { |path| File.read(path) }.join("\n")
fail!('production-operations must not be declared in workflow YAML') if all_yaml.match?(/^\s*environment:\s*production-operations\s*$/)
fail!('unknown production environment declaration found') if all_yaml.scan(/^\s*environment:\s*([^\s#]+)/).flatten.any? { |name| !%w[production-readonly-diagnostics offering-gap-readonly-audit production-reset].include?(name) }
pass('only the three owner-confirmed environment names are declared')

# Environment-scoped SSH/API secret names are deliberately distinct from the
# remaining repository-level secret names, preventing silent fallback.
READ_ONLY.each do |filename, environment|
  source = File.read(File.join(WORKFLOWS, filename))
  if environment == 'production-readonly-diagnostics'
    fail!("#{filename} still references repository-level Hostinger secrets") if source.match?(/secrets\.HOSTINGER_SSH_/)
    fail!("#{filename} still references repository-level diagnostics tokens") if source.match?(/secrets\.INSTITUTES_(?:DIAGNOSTICS|PRIVATE_REPORTS)_TOKEN/)
  elsif filename == 'hostinger-readonly-offering-gap-audit.yml'
    fail!("#{filename} still references repository-level Hostinger secrets") if source.match?(/secrets\.HOSTINGER_SSH_/)
  end
end
pass('read-only production workflows use environment-specific secret identifiers')

# The offering-gap runner must not inject database secrets at job scope.
offering = parse_workflow(File.join(WORKFLOWS, 'offering-gap-preflight.yml'))
preflight_job = offering.fetch('jobs').fetch('preflight')
fail!('offering-gap DB secrets must not be exposed at job scope before the guard') if YAML.dump(preflight_job.fetch('env', {})).match?(/secrets\.OFFERING_GAP_DB_/)
pass('offering-gap DB secrets are step-scoped after authorization')

# Ordinary CI remains ordinary CI and cannot use production credentials.
%w[test.yml laravel-ci.yml].each do |filename|
  workflow = parse_workflow(File.join(WORKFLOWS, filename))
  events = events_for(workflow)
  fail!("#{filename} must retain push and pull_request CI") unless events.key?('push') && events.key?('pull_request')
  source = File.read(File.join(WORKFLOWS, filename))
  fail!("#{filename} contains a production SSH action or secret") if source.match?(/appleboy\/ssh-action|secrets\.(?:HOSTINGER_SSH_|OFFERING_GAP_DB_|PRODUCTION_READONLY_)/)
end
pass('ordinary push/pull_request CI remains production-secret-free')

# Exact-SHA dispatch guard contract.
guard = File.read(File.join(ROOT, 'scripts/ci/validate-production-dispatch.sh'))
%w[GITHUB_EVENT_NAME workflow_dispatch GITHUB_REF refs/heads/main TARGET_SHA OPERATION_REASON CONFIRM_PRODUCTION_OPERATION EXPECTED_CONFIRMATION GITHUB_SHA origin main].each do |marker|
  fail!("dispatch guard missing #{marker}") unless guard.include?(marker)
end
fail!('dispatch guard must reject non-lowercase/non-40-character SHAs') unless guard.include?('^[0-9a-f]{40}$')
fail!('dispatch guard must reject a generic confirmation') unless guard.include?('AUTHORIZE-PRODUCTION-OPERATION')
fail!('dispatch guard must execute from its isolated checkout') unless guard.include?('cd "$(dirname "${BASH_SOURCE[0]}")/../.."')
pass('shared guard enforces manual dispatch, main, exact current SHA, reason and unique confirmation')

if File.file?(File.join(WORKFLOWS, RESET_WORKFLOW))
  recovery = parse_workflow(File.join(WORKFLOWS, RESET_WORKFLOW))
  recovery_events = events_for(recovery)
  fail!('administrator recovery must remain manual-only') unless recovery_events.keys.map(&:to_s) == ['workflow_dispatch']
  puts 'BLOCKED OWNER ACTION: secure-institutes-admin-recovery.yml remains on main; it is explicitly skipped and bound to production-reset. Its legacy repository-level credential references must be migrated to production-reset environment secrets before any future re-authorization.'
end

puts "SUMMARY: #{READ_ONLY.length} read-only workflows, #{BLOCKED_OPERATIONS.length} blocked operations, one blocked reset/recovery workflow, #{paths.length} total workflows"
