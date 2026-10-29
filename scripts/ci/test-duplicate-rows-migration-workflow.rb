#!/usr/bin/env ruby
# frozen_string_literal: true

require 'yaml'

root = File.expand_path('../..', __dir__)
workflow_path = File.join(root, '.github/workflows/controlled-import-batch-duplicate-rows-migration.yml')
workflow = YAML.load_file(workflow_path)
triggers = workflow['on'] || workflow[true]
dispatch = triggers.fetch('workflow_dispatch')
input = dispatch.fetch('inputs').fetch('confirm_migration')
abort 'ERROR: confirm_migration must remain a required boolean defaulting false' unless input['required'] == true && input['type'] == 'boolean' && input['default'] == false
abort 'ERROR: workflow permissions must remain read-only for contents' unless workflow.fetch('permissions').fetch('contents') == 'read'
abort 'ERROR: migration concurrency guard changed' unless workflow.fetch('concurrency')['cancel-in-progress'] == false

script = workflow.fetch('jobs').fetch('migrate').fetch('steps')
  .find { |step| step['name'] == 'Verify deployed code and run guarded single migration' }
  .fetch('with').fetch('script')

required_fields = %w[
  mutation_performed write_requested database_driver table_exists column_exists
  column_definition column_definition_matches_migration column_definition_equivalence column_position_verified
  candidate_rows_precedes_duplicate_rows table_definition indexes foreign_keys
  migration_file_exists migration_ledger_exists matching_migration_count
  matching_migrations related_migration_ledger_entries related_migration_files schema_equivalence
  metadata_introspection_succeeded migration_origin diagnosis
]
required_fields.each do |field|
  abort "ERROR: read-only preflight report is missing #{field}" unless script.include?("'#{field}'")
end

%w[
  COLUMN_PRESENT_LEDGER_ABSENT COLUMN_ABSENT_LEDGER_PRESENT
  COLUMN_DEFINITION_MISMATCH DUPLICATE_MIGRATION_LEDGER_ENTRIES SCHEMA_EQUIVALENCE_UNVERIFIED
].each do |diagnosis|
  abort "ERROR: missing explicit diagnosis #{diagnosis}" unless script.include?(diagnosis)
end

abort 'ERROR: diagnostic must explicitly leave migration origin unknown unless established' unless script.include?("'migration_origin' => 'UNKNOWN'")
abort 'ERROR: column position must be checked against candidate_rows' unless script.include?("$duplicatePosition === $candidatePosition + 1")
abort 'ERROR: migration history must be reported from Git without writes' unless script.include?("git log --all --format='%H %s' -S'duplicate_rows' -- database/migrations") && script.include?("git log --all --format='%H %s' -S'import_batches' -- database/migrations")
abort 'ERROR: index and foreign-key inventory must be read-only schema introspection' unless script.include?('$schema->getIndexes($table)') && script.include?('$schema->getForeignKeys($table)')
abort 'ERROR: MySQL table metadata must be read-only' unless script.include?('information_schema.TABLES')
abort 'ERROR: alternate related migration history must be inventoried' unless script.include?("like', '%duplicate_rows%'") && script.include?("like', '%import_batches%'") && script.include?("glob(base_path('database/migrations/*.php'))")
abort 'ERROR: duplicate ledger entries must fail closed' unless script.include?('$matchingMigrationCount > 1') && script.include?('exit(15);')
abort 'ERROR: partial schema must fail closed' unless script.include?('$columnExists && $matchingMigrationCount === 0') && script.include?('exit(14);')
abort 'ERROR: preflight must not auto-reconcile the migration ledger' if script.match?(/insert\s+into\s+['"]?migrations|table\(['"]migrations['"]\)->insert/i)
abort 'ERROR: preflight must not drop or recreate the column' if script.match?(/dropColumn|drop\s+column|add\s+column/i)
abort 'ERROR: the workflow must remain restricted to the exact target migration' unless script.include?('2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php') && script.include?('--path="$MIGRATION_FILE"')


require 'tempfile'
require 'open3'
php_preflight = script.match(/cat > "\$PREFLIGHT_SCRIPT_FILE" <<'PHP'\n(.*?)\nPHP/m)
abort 'ERROR: embedded PHP preflight could not be extracted for syntax validation' unless php_preflight
Tempfile.create(['duplicate-rows-preflight', '.php']) do |file|
  file.write(php_preflight[1].lstrip)
  file.flush
  output, status = Open3.capture2e('php', '-l', file.path)
  abort "ERROR: embedded PHP preflight fails php -l:\n#{output}" unless status.success?
end

diagnostic_workflow = YAML.load_file(File.join(root, '.github/workflows/hostinger-readonly-diagnostics.yml'))
diagnostic_script = diagnostic_workflow.fetch('jobs').fetch('diagnostics').fetch('steps')
  .find { |step| step['name'] == 'Run diagnostics on Hostinger' }
  .fetch('with').fetch('script')
diagnostic_php = diagnostic_script.match(/php <<'PHP'\n(.*?)\nPHP/m)
abort 'ERROR: read-only diagnostic PHP block could not be extracted' unless diagnostic_php
%w[mutation_performed write_requested column_definition_equivalence schema_equivalence metadata_introspection_succeeded migration_origin diagnosis related_migration_files].each do |field|
  abort "ERROR: read-only diagnostic report is missing #{field}" unless diagnostic_php[1].include?("'#{field}'")
end
abort 'ERROR: read-only diagnostic must report no requested write' unless diagnostic_php[1].include?("'write_requested' => false")
abort 'ERROR: read-only diagnostic must not mutate schema or migration ledger' if diagnostic_php[1].match?(/->insert|->update|->delete|Schema::table|Artisan::call/i)
Tempfile.create(['duplicate-rows-readonly-diagnostic', '.php']) do |file|
  file.write(diagnostic_php[1].lstrip)
  file.flush
  output, status = Open3.capture2e('php', '-l', file.path)
  abort "ERROR: read-only diagnostic PHP fails php -l:\n#{output}" unless status.success?
end

puts 'PASS: dry-run default, least-privilege permissions, and concurrency guard'
puts 'PASS: JSON output includes column, position, table, index, foreign-key, and ledger evidence'
puts 'PASS: explicit unsafe-state diagnoses and fail-closed guards'
puts 'PASS: migration origin is not inferred from basic column attributes'
puts 'PASS: embedded migration preflight and read-only diagnostic PHP both pass php -l'
puts 'PASS: read-only path contains no migration-ledger or schema-repair write'
