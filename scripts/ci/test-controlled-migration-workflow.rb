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

openers = lines.each_index.select { |index| lines[index] == "php <<'PHP'" }
abort "ERROR: expected exactly one PHP heredoc opener, found #{openers.length}" unless openers.length == 1
opener = openers.first
closers = ((opener + 1)...lines.length).select { |index| lines[index] == 'PHP' }
abort "ERROR: expected one column-zero PHP heredoc terminator after the opener, found #{closers.length}" unless closers.length == 1
closer = closers.first
abort 'ERROR: PHP heredoc body must begin with <?php' unless lines[opener + 1] == '<?php'
abort 'ERROR: expected shell statement after the PHP heredoc terminator' unless
  lines[closer + 1] == 'echo "production_canonical_records_changed=NO"'

Tempfile.create(['controlled-migration-remote', '.sh']) do |shell_file|
  shell_file.write(script)
  shell_file.flush
  output, status = Open3.capture2e('sh', '-n', shell_file.path)
  abort "ERROR: rendered remote shell script fails sh -n:\n#{output}" unless status.success?
end

php_source = lines[(opener + 1)...closer].join("\n") + "\n"
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

puts 'PASS: parsed workflow uses workflow_dispatch with both confirmations default false'
puts 'PASS: rendered remote script has a column-zero PHP heredoc terminator'
puts 'PASS: rendered remote shell passes sh -n'
puts 'PASS: extracted PHP program passes php -l'
puts 'PASS: migration confirmation guards remain independent and reject both true'
