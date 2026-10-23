<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataGapReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_gap_report_outputs_json(): void
    {
        $this->artisan('institutes:data-gap-report', ['--json' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('institutes_data_gap_report');
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

    public function test_json_data_gap_report_explicitly_reports_no_mutation(): void
 {
  $this->artisan('institutes:data-gap-report',['--json'=>true])->assertExitCode(0)->expectsOutputToContain('"mutation_performed": false');
 }
    public function test_report_includes_coverage_distributions_and_missing_relationship_counts(): void
    {
        $this->artisan('institutes:data-gap-report', ['--json' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('"coverage"')
            ->expectsOutputToContain('"institutions_by_type_and_status"')
            ->expectsOutputToContain('"campus_geography_coverage"')
            ->expectsOutputToContain('"institutions_without_campuses"')
            ->expectsOutputToContain('"institutions_without_programs"')
            ->expectsOutputToContain('"programs_without_offerings"');
    }


}
