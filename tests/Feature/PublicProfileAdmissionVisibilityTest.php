<?php

namespace Tests\Feature;

use App\Models\AdmissionIntake;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileAdmissionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_and_suspended_intakes_are_hidden_from_public_profiles(): void
    {
        $institution = Institution::query()->create([
            'name' => 'Admission Visibility University',
            'slug' => 'admission-visibility-university',
            'status' => 'active',
        ]);
        $program = Program::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Admission Visibility Program',
            'slug' => 'admission-visibility-program',
            'status' => 'active',
        ]);
        $offering = ProgramOffering::query()->create([
            'program_id' => $program->id,
            'delivery_mode' => 'on_campus',
            'status' => 'active',
            'availability' => 'unknown',
        ]);

        AdmissionIntake::query()->create([
            'program_offering_id' => $offering->id,
            'label' => 'Open Applications Intake',
            'status' => 'applications_open',
        ]);
        AdmissionIntake::query()->create([
            'program_offering_id' => $offering->id,
            'label' => 'Cancelled Historical Intake',
            'status' => 'cancelled',
        ]);
        AdmissionIntake::query()->create([
            'program_offering_id' => $offering->id,
            'label' => 'Suspended Historical Intake',
            'status' => 'suspended',
        ]);

        $this->get('/institutes/admission-visibility-university')
            ->assertOk()
            ->assertSee('Open Applications Intake')
            ->assertDontSee('Cancelled Historical Intake')
            ->assertDontSee('Suspended Historical Intake');
    }


    public function test_candidate_intakes_are_hidden_from_public_profiles(): void
    {
        $institution = Institution::query()->create(['name' => 'Candidate Intake University', 'slug' => 'candidate-intake-university', 'status' => 'active']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'name' => 'Candidate Intake Program', 'slug' => 'candidate-intake-program', 'status' => 'active']);
        $offering = ProgramOffering::query()->create(['program_id' => $program->id, 'delivery_mode' => 'on_campus', 'status' => 'active', 'availability' => 'unknown']);
        AdmissionIntake::query()->create(['program_offering_id' => $offering->id, 'label' => 'Unapproved Candidate Intake', 'status' => 'candidate']);

        $this->get('/institutes/candidate-intake-university')
            ->assertOk()
            ->assertDontSee('Unapproved Candidate Intake');
    }
}
