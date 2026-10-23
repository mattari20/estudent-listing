<?php

namespace App\Console\Commands;

use App\Services\InstituteDataReadinessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstituteDataIntegrityReport extends Command
{
    protected $signature = 'institutes:data-integrity-report';
    protected $description = 'Generate a read-only canonical-data and import-promotion integrity report.';

    public function handle(): int
    {
        $report = [
            'check' => 'institutes_data_integrity_report',
            'checked_at' => now()->toIso8601String(),
            'mutation_performed' => false,
            'canonical_counts' => [],
            'promotion_failure_categories' => [],
            'readiness' => null,
            'warnings' => [],
        ];

        foreach (['institutions', 'campuses', 'programs', 'program_offerings', 'admission_intakes'] as $table) {
            if (Schema::hasTable($table)) {
                $report['canonical_counts'][$table] = DB::table($table)->count();
            } else {
                $report['canonical_counts'][$table] = null;
                $report['warnings'][] = 'canonical_table_missing:' . $table;
            }
        }

        if (Schema::hasTable('import_promotion_events')) {
            $categories = [
                'duplicate_slug_or_identity_collision' => 0,
                'unresolved_parent' => 0,
                'validation_or_missing_fields' => 0,
                'conflict_or_ambiguous_identity' => 0,
                'other_or_unspecified' => 0,
            ];

            foreach (DB::table('import_promotion_events')->select('reason')->cursor() as $event) {
                $reason = mb_strtolower((string) ($event->reason ?? ''));
                if (str_contains($reason, 'slug') || str_contains($reason, 'duplicate') || str_contains($reason, 'already exists')) {
                    $category = 'duplicate_slug_or_identity_collision';
                } elseif (str_contains($reason, 'parent') || str_contains($reason, 'institution reference')) {
                    $category = 'unresolved_parent';
                } elseif (str_contains($reason, 'validat') || str_contains($reason, 'required') || str_contains($reason, 'missing')) {
                    $category = 'validation_or_missing_fields';
                } elseif (str_contains($reason, 'conflict') || str_contains($reason, 'ambiguous')) {
                    $category = 'conflict_or_ambiguous_identity';
                } else {
                    $category = 'other_or_unspecified';
                }
                $categories[$category]++;
            }

            $report['promotion_failure_categories'] = $categories;
        } else {
            $report['warnings'][] = 'promotion_events_table_missing';
        }

        $required = ['institutions', 'campuses', 'programs', 'program_offerings', 'admission_intakes', 'geographies'];
        if (collect($required)->every(fn ($table) => Schema::hasTable($table))) {
            try {
                $report['readiness'] = app(InstituteDataReadinessService::class)->summarize();
            } catch (\Throwable $exception) {
                $report['warnings'][] = 'readiness_summary_unavailable';
            }
        } else {
            $report['warnings'][] = 'readiness_summary_skipped_missing_tables';
        }

        $report['status'] = empty($report['warnings']) ? 'completed' : 'completed_with_warnings';
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
