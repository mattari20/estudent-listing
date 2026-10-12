<?php

namespace App\Console\Commands;

use App\Services\PakistanGeographySeedService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GeographySeedPreflight extends Command
{
    protected $signature = 'institutes:geography-seed-preflight {--json : Print the report as JSON}';

    protected $description = 'Preview the Pakistan geography seed impact without changing database records.';

    public function handle(): int
    {
        $startedAt = microtime(true);
        $report = [
            'check' => 'institutes_geography_seed_preflight',
            'checked_at' => now()->toIso8601String(),
            'status' => 'critical',
            'mutation_performed' => false,
            'seed_command_executed' => false,
            'existing_geography_count' => null,
            'expected_record_count' => count(PakistanGeographySeedService::TOP_LEVEL),
            'actions' => [],
            'conflicts' => [],
            'approval_required' => true,
        ];

        try {
            if (! Schema::hasTable('geographies')) {
                $report['status'] = 'critical';
                $report['error'] = 'Required geographies table is missing.';

                return $this->finish($report, $startedAt, self::FAILURE);
            }

            $report['existing_geography_count'] = DB::table('geographies')->count();
            $country = DB::table('geographies')
                ->where('slug', 'pakistan')
                ->where('type', 'country')
                ->first();

            foreach (PakistanGeographySeedService::TOP_LEVEL as $index => $expected) {
                $matches = DB::table('geographies')
                    ->where('slug', $expected['slug'])
                    ->where('type', $expected['type'])
                    ->get();

                if ($matches->count() > 1) {
                    $duplicate = [
                        'identity' => $expected['type'].':'.$expected['slug'],
                        'action' => 'duplicate_identity',
                        'record_ids' => $matches->pluck('id')->all(),
                        'matching_records' => $matches->count(),
                    ];
                    $report['actions'][] = $duplicate;
                    $report['conflicts'][] = $duplicate;
                    continue;
                }

                $existing = $matches->first();
                $expectedParentId = $index === 0 ? null : ($country->id ?? 'country_to_be_created');
                $expectedValues = [
                    'name' => $expected['name'],
                    'code' => $expected['code'],
                    'status' => 'active',
                    'parent_id' => $expectedParentId,
                ];

                if ($existing === null) {
                    $report['actions'][] = [
                        'identity' => $expected['type'].':'.$expected['slug'],
                        'action' => 'create',
                        'expected' => $expectedValues,
                    ];
                    continue;
                }

                $changes = [];
                foreach ($expectedValues as $field => $value) {
                    $actual = $existing->{$field};
                    if ($field === 'parent_id' && $value !== 'country_to_be_created') {
                        $actual = $actual === null ? null : (int) $actual;
                        $value = $value === null ? null : (int) $value;
                    }
                    if ($actual !== $value) {
                        $changes[$field] = ['current' => $actual, 'expected' => $value];
                    }
                }

                $action = $changes === [] ? 'no_change' : 'update_existing';
                $entry = [
                    'identity' => $expected['type'].':'.$expected['slug'],
                    'action' => $action,
                    'record_id' => $existing->id,
                    'changes' => $changes,
                ];
                $report['actions'][] = $entry;

                if ($changes !== []) {
                    $report['conflicts'][] = $entry;
                }
            }

            $report['create_count'] = count(array_filter($report['actions'], fn (array $action): bool => $action['action'] === 'create'));
            $report['update_count'] = count($report['conflicts']);
            $report['no_change_count'] = count(array_filter($report['actions'], fn (array $action): bool => $action['action'] === 'no_change'));
            $report['status'] = $report['conflicts'] === [] ? 'warning' : 'critical';
            $report['note'] = 'This is a preview only. It does not execute the seed command. Review the full impact and confirm a verified backup before any separately approved production write.';
        } catch (\Throwable $exception) {
            $report['status'] = 'critical';
            $report['error_type'] = class_basename($exception);
        }

        return $this->finish(
            $report,
            $startedAt,
            $report['status'] === 'critical' ? self::FAILURE : self::SUCCESS,
        );
    }

    private function finish(array $report, float $startedAt, int $exitCode): int
    {
        $report['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Identity', 'Proposed action', 'Record ID'],
                collect($report['actions'])
                    ->map(fn (array $action): array => [
                        $action['identity'],
                        $action['action'],
                        $action['record_id'] ?? '—',
                    ])
                    ->all(),
            );
            $this->line('Overall status: '.$report['status']);
            $this->line('Create: '.($report['create_count'] ?? 0).'; update: '.($report['update_count'] ?? 0).'; unchanged: '.($report['no_change_count'] ?? 0));
            $this->line('Read-only preflight; mutation performed: no');
            $this->line('Approval required: yes');
        }

        return $exitCode;
    }
}
