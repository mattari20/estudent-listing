<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\InstituteDataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveOfferingReadinessCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_counts_inactive_offerings_separately_from_active_offerings(): void
    {
        $institution = Institution::create(['name' => 'Offering Count Institution', 'slug' => 'offering-count-institution', 'status' => 'active']);
        $program = Program::create(['institution_id' => $institution->id, 'name' => 'Offering Count Program', 'slug' => 'offering-count-program', 'status' => 'active']);
        ProgramOffering::create(['program_id' => $program->id, 'status' => 'inactive']);

        $summary = app(InstituteDataReadinessService::class)->summarize();
        $this->assertSame(1, $summary['population']['program_offerings']);
        $this->assertSame(0, $summary['population']['active_program_offerings']);
        $this->assertSame(1, $summary['population']['inactive_program_offerings']);
        $this->assertFalse($summary['mutation_performed']);
    }
}
