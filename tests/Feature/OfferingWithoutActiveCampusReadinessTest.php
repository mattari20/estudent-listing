<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\InstituteDataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferingWithoutActiveCampusReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_offering_without_campus_is_reported_without_mutating_records(): void
    {
        $institution = Institution::create([
            'name' => 'Campus Relationship Test University',
            'slug' => 'campus-relationship-test-university',
            'status' => 'active',
        ]);

        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Campus Relationship Program',
            'slug' => 'campus-relationship-program',
            'status' => 'active',
        ]);

        $offering = ProgramOffering::create([
            'program_id' => $program->id,
            'campus_id' => null,
            'delivery_mode' => 'online',
            'status' => 'active',
            'availability' => 'unknown',
        ]);

        $summary = app(InstituteDataReadinessService::class)->summarize();

        $this->assertSame(1, $summary['integrity']['active_offerings_without_active_campus']);
        $this->assertFalse($summary['mutation_performed']);
        $this->assertDatabaseHas('program_offerings', [
            'id' => $offering->id,
            'campus_id' => null,
            'status' => 'active',
        ]);
    }
}
