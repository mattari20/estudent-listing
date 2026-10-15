<?php

namespace Tests\\Feature;

use App\\Models\\Geography;
use App\\Models\\Institution;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class DataGapReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_gap_report_outputs_json_and_explicitly_reports_read_only_mode(): void
    {
        $this->artisan('institutes:data-gap-report', ['--json' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('institutes_data_gap_report')
            ->expectsOutputToContain('mutation_performed')
            ->expectsOutputToContain('canonical_population')
            ->expectsOutputToContain('source_evidence');
    }

    public function test_report_counts_canonical_records_without_changing_them(): void
    {
        Institution::query()->create([
            'name' => 'Audit Test Institute',
            'slug' => 'audit-test-institute',
            'institution_type' => 'university',
            'status' => 'candidate',
        ]);

        Geography::query()->create([
            'parent_id' => null,
            'name' => 'Pakistan',
            'slug' => 'pakistan',
            'type' => 'country',
            'code' => 'PK',
            'status' => 'active',
        ]);

        $this->artisan('institutes:data-gap-report')
            ->assertExitCode(0)
            ->expectsOutputToContain('Read-only report; mutation performed: no');

        $this->assertSame(1, Institution::query()->where('slug', 'audit-test-institute')->count());
        $this->assertSame(1, Geography::query()->where('slug', 'pakistan')->count());
    }
}
