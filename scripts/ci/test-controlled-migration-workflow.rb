#!/usr/bin/env ruby
# frozen_string_literal: true

require 'yaml'
require 'tempfile'
require 'open3'

root = File.expand_path('../..', __dir__)
workflow_path = File.join(root, '.github/workflows/controlled-official-source-refresh-migration.yml')
workflow = YAML.load_file(workflow_path)
triggers = workflow['on'] || workflow[true]
abort 'ERROR: workflow_dispatch trigger is missing' unless triggers.is_a?(Hash) && triggers['workflow_dispatch'].is_a?(Hash)
inputs = triggers.fetch('workflow_dispatch').fetch('inputs')
%w[confirm_index_repair confirm_migration].each do |name|
  input = inputs.fetch(name) { abort "ERROR: missing workflow input #{name}" }
  abort "ERROR: #{name} must be a required boolean defaulting false" unless input['required'] == true && input['type'] == 'boolean' && input['default'] == false
end
abort 'ERROR: workflow permissions changed unexpectedly' unless workflow.fetch('permissions').fetch('contents') == 'read'
abort 'ERROR: concurrency protection changed unexpectedly' unless workflow.fetch('concurrency')['group'] == 'controlled-official-source-refresh-migration' && workflow.fetch('concurrency')['cancel-in-progress'] == false

job = workflow.fetch('jobs').fetch('migrate')
step = job.fetch('steps').find { |item| item['name'] == 'Validate target and run guarded migration' }
abort 'ERROR: guarded migration SSH step was not found' unless step
script = step.fetch('with').fetch('script')
abort 'ERROR: workflow must load the PHP diagnostic from the verified main revision' unless script.include?('git show "$BOOTSTRAP_REF:scripts/ops/official-source-refresh-diagnostic.php" > "$PHP_DIAGNOSTIC_FILE"')
abort 'ERROR: workflow must report source loading failure safely' unless script.include?('php_diagnostic_source_load_status=$PHP_SOURCE_LOAD_STATUS')
abort 'ERROR: workflow must execute the checked-in PHP diagnostic' unless script.include?('if php "$PHP_DIAGNOSTIC_FILE"; then')
abort 'ERROR: production change assertion is missing' unless script.include?('production_canonical_records_changed=NO')
abort 'ERROR: workflow must not lint a temporary remote PHP file' if script.include?('php -l "$PHP_DIAGNOSTIC_FILE"')

Tempfile.create(['controlled-migration-remote', '.sh']) do |file|
  file.write(script)
  file.flush
  %w[bash sh].each do |shell|
    output, status = Open3.capture2e(shell, '-n', file.path)
    abort "ERROR: rendered remote shell fails #{shell} -n:\n#{output}" unless status.success?
  end
end

php_path = File.join(root, 'scripts/ops/official-source-refresh-diagnostic.php')
php_source = File.read(php_path)
output, status = Open3.capture2e('php', '-l', php_path)
abort "ERROR: committed PHP diagnostic fails php -l:\n#{output}" unless status.success?

%w[CONFIRM_INDEX_REPAIR CONFIRM_MIGRATION].each do |name|
  abort "ERROR: #{name} guard is missing" unless php_source.include?("getenv('#{name}')")
end
abort 'ERROR: combined confirmations must be explicitly rejected' unless php_source.include?("getenv('CONFIRM_INDEX_REPAIR') === 'true' && getenv('CONFIRM_MIGRATION') === 'true'") && php_source.include?('exit(19);')
abort 'ERROR: schema repair and migration-ledger operations must remain separate' unless php_source.include?("'migration_ledger_changed' => false") && php_source.include?("'migration_executed_this_run' => false")
abort 'ERROR: exception messages must never be written to workflow logs' if php_source.match?(/exception->getMessage\s*\(/) || (php_source.match?(/getPrevious\s*\(/) && php_source.match?(/exception.*message/i))
abort 'ERROR: migration exception handler must suppress exception details' unless php_source.include?('ERROR: targeted migration threw an exception (details suppressed).') && !php_source.include?('diagnostic_exception_type=')
abort 'ERROR: PHP failure-stage diagnostics are missing' unless %w[php_shutdown_fatal= before_php_program composer_autoload laravel_bootstrap migrations_table_check target_table_check migration_ledger_lookup required_column_introspection required_index_introspection index_repair_preconditions index_repair_index_inventory index_repair_mutation migration_command post_migration_table_verification post_migration_ledger_verification].all? { |marker| php_source.include?(marker) }

puts 'PASS: workflow dispatch confirmations default false and remain independent'
puts 'PASS: rendered remote shell passes bash -n and sh -n'
puts 'PASS: diagnostic is loaded from the verified main revision'
puts 'PASS: committed PHP diagnostic passes php -l'
