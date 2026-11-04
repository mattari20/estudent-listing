#!/usr/bin/env ruby
# Static regression checks for the fail-closed governed institute discovery workflow.
workflow_path = ".github/workflows/scheduled-institute-discovery.yml"
workflow = File.read(workflow_path)

events = workflow[/^on:\n(?<body>.*?)(?=^[^ \t#][^\n]*:\s*$|\z)/m, :body]
abort "FAIL: workflow event block not found" unless events
event_names = events.scan(/^  ([a-zA-Z0-9_-]+):/).flatten
abort "FAIL: production discovery must be manual-dispatch only" unless event_names == ["workflow_dispatch"]

discover = workflow[/^  discover:\n(?<body>.*?)(?=^  [a-zA-Z0-9_-]+:\n|\z)/m, :body]
abort "FAIL: discover job not found" unless discover

required = {
  "production discovery job is explicitly skipped" =>
    discover.match?(/^    if: false\b/),
  "no duplicate job-level if can override the block" =>
    discover.scan(/^    if:/).length == 1,
  "workflow retains the guarded Hostinger SSH action but cannot reach it" =>
    discover.include?("uses: appleboy/ssh-action@v1.2.0"),
  "canonical mutation safeguards remain present" =>
    discover.include?('echo "canonical_mutations=NONE"') &&
    discover.include?('echo "automatic_approval=NONE"') &&
    discover.include?('echo "automatic_publication=NONE"'),
  "verified migration handoff and revision dependencies remain documented" =>
    discover.include?("needs: [resolve-target, source-refresh-handoff]") &&
    workflow.include?("schema_handoff=verified_and_bound_to_confirmed_migration_run")
}

failed = required.select { |_description, passed| !passed }
required.each do |description, passed|
  puts "#{passed ? 'PASS' : 'FAIL'}: #{description}"
end
abort "#{failed.length} blocked discovery workflow contract check(s) failed" unless failed.empty?
