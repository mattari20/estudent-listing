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
  column_definition column_definition_matches_migration column_position_verified
  candidate_rows_precedes_duplicate_rows table_definition indexes foreign_keys
  migration_file_exists migration_ledger_exists matching_migration_count
  matching_migrations related_migration_ledger_entries related_migration_files schema_equivalence
  migration_origin diagnosis
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
abort 'ERROR: index and foreign-key inventory must be read-only schema introspection' unless script.include?('$schema->getIndexes($table)') && script.include?('$schema->getForeignKeys($table)')
abort 'ERROR: MySQL table metadata must be read-only' unless script.include?('information_schema.TABLES')
abort 'ERROR: alternate related migration history must be inventoried' unless script.include?("like', '%duplicate_rows%'") && script.include?("like', '%import_batches%'") && script.include?("glob(base_path('database/migrations/*.php'))")
abort 'ERROR: duplicate ledger entries must fail closed' unless script.include?('$matchingMigrationCount > 1') && script.include?('exit(15);')
abort 'ERROR: partial schema must fail closed' unless script.include?('$columnExists && $matchingMigrationCount === 0') && script.include?('exit(14);')
abort 'ERROR: preflight must not auto-reconcile the migration ledger' if script.match?(/insert\s+into\s+['"]?migrations|table\(['"]migrations['"]\)->insert/i)
abort 'ERROR: preflight must not drop or recreate the column' if script.match?(/dropColumn|drop\s+column|add\s+column/i)
abort 'ERROR: the workflow must remain restricted to the exact target migration' unless script.include?('2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php') && script.include?('--path="$MIGRATION_FILE"')

puts 'PASS: dry-run default, least-privilege permissions, and concurrency guard'
puts 'PASS: JSON output includes column, position, table, index, foreign-key, and ledger evidence'
puts 'PASS: explicit unsafe-state diagnoses and fail-closed guards'
puts 'PASS: migration origin is not inferred from basic column attributes'
puts 'PASS: read-only path contains no migration-ledger or schema-repair write'
