#!/usr/bin/env ruby
require "yaml"
require "json"

path = ARGV.fetch(0)
document = YAML.load_file(path, aliases: true)
abort("workflow YAML root must be a mapping") unless document.is_a?(Hash)
normalized = document.each_with_object({}) do |(key, value), result|
  normalized_key = key == true ? "on" : key.to_s
  result[normalized_key] = value
end
puts JSON.generate(normalized)
