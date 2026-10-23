<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
        $this->assertSame(0, Artisan::call('institutes:data-gap-report', ['--json' => true]));
        $report = json_decode(Artisan::output(), true);

        $this->assertIsArray($report);
        $coverage = $report['sections']['coverage'] ?? null;
        $this->assertIsArray($coverage);
        $this->assertArrayHasKey('institutions_by_type_and_status', $coverage);
        $this->assertArrayHasKey('programs_by_level_and_status', $coverage);
        $this->assertArrayHasKey('program_offerings_by_delivery_mode_and_status', $coverage);
        $this->assertArrayHasKey('admission_intakes_by_status', $coverage);
        $this->assertArrayHasKey('campus_geography_coverage', $coverage);
        $this->assertArrayHasKey('active_institutions_without_active_campuses', $coverage);
        $this->assertArrayHasKey('active_institutions_without_active_programs', $coverage);
        $this->assertArrayHasKey('programs_without_offerings', $coverage);
        $this->assertFalse($report['mutation_performed']);
    }


    public function test_coverage_summary_counts_unlinked_records_without_mutating_them(): void
    {
        $unlinked = Institution::query()->create([
            'name' => 'Coverage Gap University',
            'slug' => 'coverage-gap-university',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $linked = Institution::query()->create([
            'name' => 'Coverage Linked University',
            'slug' => 'coverage-linked-university',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        Campus::query()->create([
            'institution_id' => $linked->id,
            'name' => 'Campus Without Geography',
            'slug' => 'campus-without-geography',
            'status' => 'active',
        ]);

        $program = Program::query()->create([
            'institution_id' => $linked->id,
            'name' => 'Program Without Offering',
            'slug' => 'program-without-offering',
            'program_level' => 'undergraduate',
            'status' => 'active',
        ]);

        $this->assertSame(0, Artisan::call('institutes:data-gap-report', ['--json' => true]));
        $report = json_decode(Artisan::output(), true);
        $coverage = $report['sections']['coverage'];

        $this->assertSame(1, $coverage['active_institutions_without_active_campuses']);
        $this->assertSame(1, $coverage['active_institutions_without_active_programs']);
        $this->assertSame(1, $coverage['programs_without_offerings']);
        $this->assertSame(1, $coverage['campus_geography_coverage']['active_campuses_without_geography']);
        $this->assertSame(1, $coverage['programs_by_level_and_status'][0]['count']);
        $this->assertSame('undergraduate', $coverage['programs_by_level_and_status'][0]['program_level']);
        $this->assertSame(1, Institution::query()->whereKey($unlinked->id)->count());
        $this->assertSame(1, Program::query()->whereKey($program->id)->count());
        $this->assertFalse($report['mutation_performed']);
    }


    public function test_coverage_summary_groups_active_campuses_by_province_and_district(): void
    {
        $province = Geography::query()->create([
            'parent_id' => null,
            'name' => 'Coverage Province',
            'slug' => 'coverage-province',
            'type' => 'province',
            'status' => 'active',
        ]);
        $district = Geography::query()->create([
            'parent_id' => $province->id,
            'name' => 'Coverage District',
            'slug' => 'coverage-district',
            'type' => 'district',
            'status' => 'active',
        ]);
        $city = Geography::query()->create([
            'parent_id' => $district->id,
            'name' => 'Coverage City',
            'slug' => 'coverage-city',
            'type' => 'city',
            'status' => 'active',
        ]);
        $institution = Institution::query()->create([
            'name' => 'Geography Coverage University',
            'slug' => 'geography-coverage-university',
            'institution_type' => 'university',
            'status' => 'active',
        ]);
        Campus::query()->create([
            'institution_id' => $institution->id,
            'geography_id' => $city->id,
            'name' => 'Geography Coverage Campus',
            'slug' => 'geography-coverage-campus',
            'status' => 'active',
        ]);

        $this->assertSame(0, Artisan::call('institutes:data-gap-report', ['--json' => true]));
        $report = json_decode(Artisan::output(), true);
        $coverage = $report['sections']['coverage'];

        $this->assertContains(
            ['geography_id' => $province->id, 'name' => 'Coverage Province', 'count' => 1],
            $coverage['active_campuses_by_province_or_territory']
        );
        $this->assertContains(
            ['geography_id' => $district->id, 'name' => 'Coverage District', 'province_or_territory' => 'Coverage Province', 'count' => 1],
            $coverage['active_campuses_by_district']
        );
        $this->assertFalse($report['mutation_performed']);
    }


    public function test_geographic_coverage_keeps_same_named_districts_separate_by_identity(): void
    {
        $provinceA = Geography::query()->create(['name' => 'North Province', 'slug' => 'north-province', 'type' => 'province', 'status' => 'active']);
        $provinceB = Geography::query()->create(['name' => 'South Province', 'slug' => 'south-province', 'type' => 'province', 'status' => 'active']);
        $districtA = Geography::query()->create(['parent_id' => $provinceA->id, 'name' => 'Central District', 'slug' => 'central-district-north', 'type' => 'district', 'status' => 'active']);
        $districtB = Geography::query()->create(['parent_id' => $provinceB->id, 'name' => 'Central District', 'slug' => 'central-district-south', 'type' => 'district', 'status' => 'active']);
        $institutionA = Institution::query()->create(['name' => 'North Campus University', 'slug' => 'north-campus-university', 'status' => 'active']);
        $institutionB = Institution::query()->create(['name' => 'South Campus University', 'slug' => 'south-campus-university', 'status' => 'active']);

        Campus::query()->create(['institution_id' => $institutionA->id, 'geography_id' => $districtA->id, 'name' => 'North Central Campus', 'slug' => 'north-central-campus', 'status' => 'active']);
        Campus::query()->create(['institution_id' => $institutionB->id, 'geography_id' => $districtB->id, 'name' => 'South Central Campus', 'slug' => 'south-central-campus', 'status' => 'active']);

        $this->assertSame(0, Artisan::call('institutes:data-gap-report', ['--json' => true]));
        $report = json_decode(Artisan::output(), true);
        $districts = $report['sections']['coverage']['active_campuses_by_district'];

        $matching = array_values(array_filter($districts, fn (array $item): bool => $item['name'] === 'Central District'));
        $this->assertCount(2, $matching);
        $this->assertContains(['geography_id' => $districtA->id, 'name' => 'Central District', 'province_or_territory' => 'North Province', 'count' => 1], $matching);
        $this->assertContains(['geography_id' => $districtB->id, 'name' => 'Central District', 'province_or_territory' => 'South Province', 'count' => 1], $matching);
    }




    public function test_duplicate_name_report_is_a_candidate_signal_not_a_merge_instruction(): void
    {
        Institution::query()->create([
            'name' => 'Shared Name University',
            'slug' => 'shared-name-university-one',
            'institution_type' => 'university',
            'status' => 'candidate',
        ]);
        Institution::query()->create([
            'name' => ' shared name university ',
            'slug' => 'shared-name-university-two',
            'institution_type' => 'university',
            'status' => 'candidate',
        ]);

        Artisan::call('institutes:data-gap-report', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(1, $report['sections']['duplicate_candidates']['institution_name_groups_after_trim_and_case_fold'] ?? null);
        $this->assertStringContainsString('Candidate groups only', $report['sections']['duplicate_candidates']['note'] ?? '');
        $this->assertFalse($report['mutation_performed'] ?? true);
        $this->assertSame(2, Institution::query()->count());
    }
}
