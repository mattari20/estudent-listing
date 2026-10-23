<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\InstituteDataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadinessActiveOfferingGapTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_program_with_only_inactive_offerings_is_reported_as_missing_active_offering(): void
    {
        $institution = Institution::create([
            'name' => 'Offering Gap Test University',
            'slug' => 'offering-gap-test-university',
            'status' => 'active',
        ]);

        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Test Computer Science',
            'slug' => 'test-computer-science',
            'status' => 'active',
        ]);

        ProgramOffering::create([
            'program_id' => $program->id,
            'campus_id' => null,
            'delivery_mode' => 'online',
            'status' => 'inactive',
            'availability' => 'unknown',
        ]);

        $summary = app(InstituteDataReadinessService::class)->summarize();

        $this->assertSame(1, $summary['integrity']['active_programs_without_offerings']);
        $this->assertFalse($summary['mutation_performed']);
        $this->assertDatabaseHas('program_offerings', [
            'program_id' => $program->id,
            'status' => 'inactive',
        ]);
    }
}
