<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\InstituteDataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveOfferingInactiveProgramReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_offering_linked_to_inactive_program_is_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Inactive Program Readiness University',
            'slug' => 'inactive-program-readiness-university',
            'status' => 'active',
        ]);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Inactive Program',
            'slug' => 'inactive-program',
            'status' => 'inactive',
        ]);
        $offering = ProgramOffering::create([
            'program_id' => $program->id,
            'campus_id' => null,
            'delivery_mode' => 'online',
            'status' => 'active',
            'availability' => 'unknown',
        ]);

        $summary = app(InstituteDataReadinessService::class)->summarize();

        $this->assertSame(1, $summary['integrity']['active_offerings_without_active_program']);
        $this->assertFalse($summary['mutation_performed']);
        $this->assertDatabaseHas('program_offerings', [
            'id' => $offering->id,
            'status' => 'active',
        ]);
    }
}
