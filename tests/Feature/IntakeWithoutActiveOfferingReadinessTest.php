<?php

namespace Tests\Feature;

use App\Models\AdmissionIntake;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\InstituteDataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntakeWithoutActiveOfferingReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_intake_linked_to_inactive_offering_is_reported_as_without_active_offering(): void
    {
        $institution = Institution::create([
            'name' => 'Intake Readiness University',
            'slug' => 'intake-readiness-university',
            'status' => 'active',
        ]);

        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Intake Readiness Program',
            'slug' => 'intake-readiness-program',
            'status' => 'active',
        ]);

        $offering = ProgramOffering::create([
            'program_id' => $program->id,
            'campus_id' => null,
            'delivery_mode' => 'online',
            'status' => 'inactive',
            'availability' => 'unknown',
        ]);

        $intake = AdmissionIntake::create([
            'program_offering_id' => $offering->id,
            'label' => 'Synthetic upcoming intake',
            'status' => 'planned',
        ]);

        $summary = app(InstituteDataReadinessService::class)->summarize();

        $this->assertSame(1, $summary['integrity']['intakes_without_active_offering']);
        $this->assertFalse($summary['mutation_performed']);
        $this->assertDatabaseHas('admission_intakes', [
            'id' => $intake->id,
            'program_offering_id' => $offering->id,
        ]);
    }
}
