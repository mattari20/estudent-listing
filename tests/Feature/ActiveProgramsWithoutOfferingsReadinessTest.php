<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\InstituteDataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveProgramsWithoutOfferingsReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_reports_active_programs_without_any_offering_without_mutating_data(): void
    {
        $institution = Institution::create([
            'name' => 'Readiness Gap University',
            'slug' => 'readiness-gap-university',
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
            'name' => 'Program With Offering',
            'slug' => 'program-with-offering',
            'status' => 'active',
        ]);

        ProgramOffering::create([
            'program_id' => $withOffering->id,
            'status' => 'inactive',
        ]);

        $summary = app(InstituteDataReadinessService::class)->summarize();

        $this->assertSame(1, $summary['integrity']['active_programs_without_offerings']);
        $this->assertSame(2, $summary['population']['active_programs']);
        $this->assertFalse($summary['mutation_performed']);
        $this->assertDatabaseHas('programs', ['id' => $withoutOffering->id, 'status' => 'active']);
    }
}
