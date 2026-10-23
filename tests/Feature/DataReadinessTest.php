<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\InstituteDataCoverageService;
use App\Services\InstituteDataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_summary_counts_institutions(): void
    {
        Institution::create(['name' => 'Example', 'slug' => 'example', 'status' => 'active']);
        $s = (new InstituteDataReadinessService)->summarize();
        $this->assertSame(1, $s['population']['institutions']);
        $this->assertSame(1, $s['population']['active_institutions']);
        $this->assertSame(1, $s['integrity']['active_institutions_without_active_campus']);
        $this->assertFalse($s['structurally_ready']);
        $this->assertFalse($s['mutation_performed']);
    }

    public function test_coverage_is_explicit(): void
    {
        Institution::create(['name' => 'Example', 'slug' => 'example', 'status' => 'active', 'institution_type' => 'university']);
        $c = (new InstituteDataCoverageService)->institutionCoverage();
        $this->assertSame(100.0, $c['type_coverage']);
    }

    public function test_valid_active_hierarchy_has_no_structural_findings(): void
    {
        $institution = Institution::create(['name' => 'Example', 'slug' => 'example', 'status' => 'active']);
        $geography = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'status' => 'active',
        ]);
        ProgramOffering::create([
            'program_id' => $program->id,
            'campus_id' => $campus->id,
            'status' => 'active',
        ]);

        $s = (new InstituteDataReadinessService)->summarize();

        $this->assertSame(0, $s['blocking_findings']);
        $this->assertTrue($s['structurally_ready']);
    }


    public function test_empty_population_is_reported_as_zero_without_mutation(): void
    {
        $summary = (new InstituteDataReadinessService)->summarize();

        $this->assertSame(0, $summary['population']['institutions']);
        $this->assertSame(0, $summary['population']['active_institutions']);
        $this->assertFalse($summary['mutation_performed']);
    }
}
