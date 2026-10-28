#!/usr/bin/env ruby
# frozen_string_literal: true

require 'yaml'
require 'tempfile'
require 'open3'

workflow_path = File.expand_path('../../.github/workflows/controlled-official-source-refresh-migration.yml', __dir__)
workflow = YAML.load_file(workflow_path)

# YAML 1.1 parsers may resolve the bare key "on" as boolean true.
triggers = workflow['on'] || workflow[true]
abort 'ERROR: workflow has no trigger map' unless triggers.is_a?(Hash)

dispatch = triggers['workflow_dispatch']
abort 'ERROR: workflow_dispatch trigger is missing' unless dispatch.is_a?(Hash)
inputs = dispatch.fetch('inputs')
%w[confirm_index_repair confirm_migration].each do |name|
  input = inputs.fetch(name) { abort "ERROR: missing workflow input #{name}" }
  abort "ERROR: #{name} must be a required boolean defaulting false" unless
    input['required'] == true && input['type'] == 'boolean' && input['default'] == false
end

abort 'ERROR: workflow permissions changed unexpectedly' unless workflow.fetch('permissions').fetch('contents') == 'read'
concurrency = workflow.fetch('concurrency')
abort 'ERROR: migration concurrency protection changed unexpectedly' unless
  concurrency['group'] == 'controlled-official-source-refresh-migration' &&
  concurrency['cancel-in-progress'] == false

job = workflow.fetch('jobs').fetch('migrate')
step = job.fetch('steps').find { |item| item['name'] == 'Validate target and run guarded migration' }
abort 'ERROR: guarded migration SSH step was not found' unless step
script = step.fetch('with').fetch('script')
lines = script.lines.map(&:chomp)

openers = lines.each_index.select { |index| lines[index].match?(/\A\s*if php <<'PHP'\s*\z/) }
abort "ERROR: expected exactly one PHP heredoc opener, found #{openers.length}" unless openers.length == 1
opener = openers.first

# Validate the parsed YAML block scalar rather than assuming its visual source
# indentation survives parsing unchanged. The if-wrapper's indentation is the
# shell block's common prefix; PHP body/terminator checks use that exact prefix.
indent = lines[opener][0...lines[opener].index('if php')]
abort 'ERROR: PHP heredoc opener has unexpected indentation' unless indent.match?(/\A\s*\z/)
closers = ((opener + 1)...lines.length).select { |index| lines[index] == "#{indent}PHP" }
abort "ERROR: expected one correctly positioned PHP heredoc terminator after the opener, found #{closers.length}" unless closers.length == 1
closer = closers.first
abort 'ERROR: PHP heredoc body must begin with <?php at the script indentation' unless lines[opener + 1] == "#{indent}<?php"
abort 'ERROR: expected the conditional PHP result handler after the heredoc terminator' unless
  lines[closer + 1] == "#{indent}then"
abort 'ERROR: expected the PHP exit-status marker after the conditional handler' unless
  lines[closer + 2] == "#{indent}  PHP_EXIT_STATUS=0"
abort 'ERROR: the rendered script must retain the production-change assertion after the PHP status block' unless
  lines[(closer + 1)..].include?("#{indent}echo \"production_canonical_records_changed=NO\"")

Tempfile.create(['controlled-migration-remote', '.sh']) do |shell_file|
  shell_file.write(script)
  shell_file.flush
  output, status = Open3.capture2e('sh', '-n', shell_file.path)
  abort "ERROR: rendered remote shell script fails sh -n:\n#{output}" unless status.success?
end

php_source = lines[(opener + 1)...closer].map { |line| line.delete_prefix(indent) }.join("\n") + "\n"
Tempfile.create(['controlled-migration-php', '.php']) do |php_file|
  php_file.write(php_source)
  php_file.flush
  output, status = Open3.capture2e('php', '-l', php_file.path)
  abort "ERROR: extracted PHP program fails php -l:\n#{output}" unless status.success?
end

abort 'ERROR: index-repair confirmation guard missing' unless
  script.include?("if (getenv('CONFIRM_INDEX_REPAIR') === 'true')")
abort 'ERROR: migration confirmation guard missing' unless
  script.include?("if (getenv('CONFIRM_MIGRATION') !== 'true')")
abort 'ERROR: combined confirmations must be explicitly rejected' unless
  script.include?("getenv('CONFIRM_INDEX_REPAIR') === 'true' && getenv('CONFIRM_MIGRATION') === 'true'") &&
  script.include?('exit(19);')
abort 'ERROR: schema repair and migration-ledger operations must remain separate' unless
  script.include?("'migration_ledger_changed' => false") &&
  script.include?("'migration_executed_this_run' => false")
abort 'ERROR: PHP execution must preserve and report its exit status' unless
  script.include?('if php <<\'PHP\'') &&
  script.include?('php_program_exit_status=$PHP_EXIT_STATUS') &&
  script.include?('guarded PHP schema diagnostic failed')
abort 'ERROR: exception messages must never be written to workflow logs' if
  script.match?(/exception->getMessage\s*\(/) ||
  script.match?(/getPrevious\s*\(/) && script.match?(/exception.*message/i)
abort 'ERROR: migration exception handler must suppress exception details' unless
  script.include?('ERROR: targeted migration threw an exception (details suppressed).') &&
  !script.include?('diagnostic_exception_type=')

abort 'ERROR: shallow repository state must be queried explicitly' unless
  script.include?('git rev-parse --is-shallow-repository') &&
  script.include?('echo "git_is_shallow_repository=$SHALLOW_REPOSITORY"')

abort 'ERROR: shallow-file detection and fetch selection must share explicit branches' unless
  script.include?('echo "git_shallow_file_present=true"') &&
  script.include?('echo "git_shallow_file_present=false"') &&
  script.include?('diagnostic_checkpoint=before_main_fetch') &&
  script.include?('diagnostic_checkpoint=before_unshallow_fetch')

abort 'ERROR: PHP failure-stage diagnostics are missing' unless
  script.include?('php_shutdown_fatal=true stage=') &&
  script.include?('diagnostic_checkpoint=before_php_program') &&
  script.include?('diagnostic_checkpoint=composer_autoload') &&
  script.include?('diagnostic_checkpoint=laravel_bootstrap') &&
  script.include?('diagnostic_checkpoint=migrations_table_check') &&
  script.include?('diagnostic_checkpoint=target_table_check') &&
  script.include?('diagnostic_checkpoint=migration_ledger_lookup') &&
  script.include?('diagnostic_checkpoint=required_column_introspection') &&
  script.include?('diagnostic_checkpoint=required_index_introspection') &&
  script.include?('diagnostic_checkpoint=index_repair_preconditions') &&
  script.include?('diagnostic_checkpoint=index_repair_index_inventory') &&
  script.include?('diagnostic_checkpoint=index_repair_mutation') &&
  script.include?('diagnostic_checkpoint=migration_command') &&
  script.include?('diagnostic_checkpoint=post_migration_table_verification') &&
  script.include?('diagnostic_checkpoint=post_migration_ledger_verification')

puts 'PASS: parsed workflow uses workflow_dispatch with both confirmations default false'
puts 'PASS: rendered remote script has a column-zero PHP heredoc terminator'
puts 'PASS: rendered remote shell passes sh -n'
puts 'PASS: extracted PHP program passes php -l'
puts 'PASS: migration confirmation guards remain independent and reject both true'
