#!/usr/bin/env ruby
# Static regression checks for the scheduled governed institute discovery workflow.
workflow_path = ".github/workflows/scheduled-institute-discovery.yml"
workflow = File.read(workflow_path)

discover = workflow[/^  discover:\n(?<body>.*?)(?=^  [a-zA-Z0-9_-]+:\n|\z)/m, :body]
abort "FAIL: discover job not found" unless discover

required = {
  "explicit dependencies include revision resolver and handoff" =>
    discover.match?(/^    needs: \[resolve-target, source-refresh-handoff\]$/),
  "skipped handoff does not suppress eligible manual/scheduled runs" =>
    discover.include?("if: ${{ always() && needs.resolve-target.result == 'success'"),
  "migration-triggered runs require verified handoff" =>
    discover.include?("github.event_name != 'workflow_run' || needs.source-refresh-handoff.outputs.verified == 'true'"),
  "discovery still runs on the guarded Hostinger SSH action" =>
    discover.include?("uses: appleboy/ssh-action@v1.2.0"),
  "canonical mutation safeguards remain present" =>
    discover.include?('echo "canonical_mutations=NONE"') &&
    discover.include?('echo "automatic_approval=NONE"') &&
    discover.include?('echo "automatic_publication=NONE"')
}

failed = required.select { |_description, passed| !passed }
required.each do |description, passed|
  puts "#{passed ? 'PASS' : 'FAIL'}: #{description}"
end
abort "#{failed.length} scheduled discovery workflow contract check(s) failed" unless failed.empty?
