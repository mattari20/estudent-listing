<?php

namespace Tests\Feature;

use App\Models\AdmissionIntake;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSearchAdmissionStatusSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_and_suspended_intakes_are_not_public_search_filters(): void
    {
        $institution = Institution::query()->create([
            'name' => 'Closed Intake University',
            'slug' => 'closed-intake-university',
            'status' => 'active',
        ]);
        $program = Program::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Archived Admissions Program',
            'slug' => 'archived-admissions-program',
            'status' => 'active',
        ]);
        $offering = ProgramOffering::query()->create([
            'program_id' => $program->id,
            'delivery_mode' => 'on_campus',
            'status' => 'active',
            'availability' => 'unknown',
        ]);

        foreach (['cancelled', 'suspended'] as $status) {
            AdmissionIntake::query()->create([
                'program_offering_id' => $offering->id,
                'label' => ucfirst($status).' intake',
                'status' => $status,
            ]);
        }

        $this->get('/institutes/search')
            ->assertOk()
            ->assertDontSee('value="cancelled"', false)
            ->assertDontSee('value="suspended"', false);

        $this->get('/institutes/search?admission_status=cancelled')
            ->assertOk()
            ->assertDontSee('Closed Intake University');

        $this->get('/institutes/search?admission_status=suspended')
            ->assertOk()
            ->assertDontSee('Closed Intake University');
    }
}
