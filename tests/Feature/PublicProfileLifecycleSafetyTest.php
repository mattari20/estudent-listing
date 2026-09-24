<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\AdmissionIntake;
use App\Models\FeeRecord;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\Scholarship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileLifecycleSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_excludes_inactive_program_and_child_records(): void
    {
        $institution = Institution::create([
            'name' => 'Lifecycle Institute',
            'slug' => 'lifecycle-institute',
            'status' => 'active',
        ]);

        $activeProgram = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Active Program',
            'slug' => 'active-program',
            'status' => 'active',
        ]);

        $inactiveProgram = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Historical Program',
            'slug' => 'historical-program',
            'status' => 'inactive',
        ]);

        $activeOffering = ProgramOffering::create([
            'program_id' => $activeProgram->id,
            'delivery_mode' => 'on_campus',
            'status' => 'active',
        ]);

        ProgramOffering::create([
            'program_id' => $activeProgram->id,
            'delivery_mode' => 'online',
            'status' => 'inactive',
        ]);

        $activeIntake = AdmissionIntake::create([
            'program_offering_id' => $activeOffering->id,
            'label' => 'Fall 2026',
            'status' => 'applications_open',
        ]);

        AdmissionIntake::create([
            'program_offering_id' => $activeOffering->id,
            'label' => 'Suspended Intake',
            'status' => 'suspended',
        ]);

        Scholarship::create([
            'institution_id' => $institution->id,
            'program_id' => $activeProgram->id,
            'program_offering_id' => $activeOffering->id,
            'admission_intake_id' => $activeIntake->id,
            'name' => 'Current Scholarship',
            'status' => 'published',
        ]);

        Scholarship::create([
            'institution_id' => $institution->id,
            'program_id' => $activeProgram->id,
            'program_offering_id' => $activeOffering->id,
            'admission_intake_id' => $activeIntake->id,
            'name' => 'Withdrawn Scholarship',
            'status' => 'withdrawn',
        ]);

        FeeRecord::create([
            'program_offering_id' => $activeOffering->id,
            'admission_intake_id' => $activeIntake->id,
            'fee_type' => 'Tuition',
            'amount_type' => 'fixed',
            'amount' => 100000,
            'currency' => 'PKR',
            'status' => 'published',
        ]);

        FeeRecord::create([
            'program_offering_id' => $activeOffering->id,
            'admission_intake_id' => $activeIntake->id,
            'fee_type' => 'Old Tuition',
            'amount_type' => 'fixed',
            'amount' => 90000,
            'currency' => 'PKR',
            'status' => 'superseded',
        ]);

        Accreditation::create([
            'institution_id' => $institution->id,
            'program_id' => $activeProgram->id,
            'program_offering_id' => $activeOffering->id,
            'name' => 'Current Recognition',
            'status' => 'published',
        ]);

        Accreditation::create([
            'institution_id' => $institution->id,
            'program_id' => $activeProgram->id,
            'program_offering_id' => $activeOffering->id,
            'name' => 'Withdrawn Recognition',
            'status' => 'withdrawn',
        ]);

        $this->get('/institutes/lifecycle-institute')
            ->assertOk()
            ->assertSee('Active Program')
            ->assertSee('Current Scholarship')
            ->assertSee('Tuition')
            ->assertSee('Current Recognition')
            ->assertDontSee('Historical Program')
            ->assertDontSee('Withdrawn Scholarship')
            ->assertDontSee('Old Tuition')
            ->assertDontSee('Withdrawn Recognition');
    }
}
