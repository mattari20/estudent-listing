<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\InstituteDataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadinessActiveOfferingGapBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_gap_excludes_programs_with_active_offerings_and_includes_programs_with_none(): void
    {
        $institution = Institution::create([
            'name' => 'Offering Baseline University',
            'slug' => 'offering-baseline-university',
            'status' => 'active',
        ]);

        $withoutOffering = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Program Without Offering',
            'slug' => 'program-without-offering',
            'status' => 'active',
        ]);

        $withOffering = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Program With Active Offering',
            'slug' => 'program-with-active-offering',
            'status' => 'active',
        ]);

        ProgramOffering::create([
            'program_id' => $withOffering->id,
            'campus_id' => null,
            'delivery_mode' => 'online',
            'status' => 'active',
            'availability' => 'unknown',
        ]);

        $summary = app(InstituteDataReadinessService::class)->summarize();

        $this->assertSame(1, $summary['integrity']['active_programs_without_offerings']);
        $this->assertDatabaseHas('programs', ['id' => $withoutOffering->id]);
        $this->assertDatabaseHas('program_offerings', ['program_id' => $withOffering->id, 'status' => 'active']);
        $this->assertFalse($summary['mutation_performed']);
    }
}
