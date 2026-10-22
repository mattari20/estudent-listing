<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Models\InstitutionName;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\AdmissionIntake;
use App\Models\EligibilityRule;
use App\Models\MeritRecord;
use App\Models\FeeRecord;
use App\Models\Scholarship;
use App\Models\Accreditation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_profile_integrates_campus_geography_program_and_offering(): void
    {
        $institution = Institution::create([
            'name' => 'Example University',
            'official_name' => 'Example University of Pakistan',
            'short_name' => 'EUP',
            'slug' => 'example-university',
            'institution_type' => 'University',
            'status' => 'active',
        ]);

        $geography = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'campus_type' => 'main',
            'status' => 'active',
        ]);

        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Bachelor of Computer Science',
            'slug' => 'bachelor-of-computer-science',
            'program_level' => 'undergraduate',
            'status' => 'active',
        ]);

        ProgramOffering::create([
            'program_id' => $program->id,
            'campus_id' => $campus->id,
            'delivery_mode' => 'on_campus',
            'status' => 'active',
            'intake_label' => 'Fall',
            'availability' => 'open',
        ]);

        $response = $this->get('/institutes/example-university');

        $response->assertOk()
            ->assertSee('Example University')
            ->assertSee('Main Campus')
            ->assertSee('Lahore')
            ->assertSee('Bachelor of Computer Science')
            ->assertSee('Fall');
    }


    public function test_institution_profile_integrates_admission_financial_and_accreditation_data(): void
    {
        $institution = Institution::create([
            'name' => 'Example University',
            'slug' => 'example-university',
            'status' => 'active',
        ]);

        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'status' => 'active',
        ]);

        $offering = ProgramOffering::create([
            'program_id' => $program->id,
            'delivery_mode' => 'on_campus',
            'status' => 'active',
            'intake_label' => 'Fall',
        ]);

        $intake = AdmissionIntake::create([
            'program_offering_id' => $offering->id,
            'label' => 'Fall 2026',
            'status' => 'applications_open',
            'application_open_at' => '2026-08-01',
            'application_close_at' => '2026-09-30',
        ]);

        EligibilityRule::create([
            'admission_intake_id' => $intake->id,
            'rule_type' => 'qualification',
            'qualification' => 'Intermediate',
            'requirement' => 'Minimum 50%',
            'status' => 'current',
        ]);

        MeritRecord::create([
            'admission_intake_id' => $intake->id,
            'merit_type' => 'closing_merit',
            'value' => '82.5%',
            'status' => 'current',
        ]);

        FeeRecord::create([
            'program_offering_id' => $offering->id,
            'admission_intake_id' => $intake->id,
            'fee_type' => 'tuition',
            'amount_type' => 'fixed',
            'amount' => 100000,
            'currency' => 'PKR',
            'frequency' => 'annual',
            'status' => 'known',
        ]);

        Scholarship::create([
            'institution_id' => $institution->id,
            'program_id' => $program->id,
            'program_offering_id' => $offering->id,
            'admission_intake_id' => $intake->id,
            'name' => 'Merit Scholarship',
            'benefit_type' => 'tuition waiver',
            'data_state' => 'known',
            'status' => 'active',
        ]);

        Accreditation::create([
            'institution_id' => $institution->id,
            'program_id' => $program->id,
            'program_offering_id' => $offering->id,
            'name' => 'Example Recognition',
            'recognition_type' => 'accreditation',
            'authority_name' => 'Example Authority',
            'status' => 'active',
        ]);

        $this->get('/institutes/example-university')
            ->assertOk()
            ->assertSee('Fall 2026')
            ->assertSee('Intermediate')
            ->assertSee('82.5%')
            ->assertSee('100000')
            ->assertSee('Merit Scholarship')
            ->assertSee('Example Recognition');
    }

    public function test_search_matches_institution_alias(): void
    {
        $institution = Institution::create([
            'name' => 'Example University',
            'slug' => 'example-university',
            'status' => 'active',
        ]);

        InstitutionName::create([
            'institution_id' => $institution->id,
            'name' => 'EUP',
            'name_type' => 'acronym',
            'is_current' => true,
        ]);

        $this->get('/institutes/search?q=EUP')
            ->assertOk()
            ->assertSee('Example University');
    }

    public function test_inactive_institution_is_not_publicly_visible(): void
    {
        Institution::create([
            'name' => 'Closed Institute',
            'slug' => 'closed-institute',
            'status' => 'closed',
        ]);

        $this->get('/institutes/closed-institute')->assertNotFound();
    }

    public function test_candidate_institution_profile_is_not_public(): void
    {
        Institution::create(['name' => 'Candidate Institute', 'slug' => 'candidate-institute', 'status' => 'candidate']);

        $this->get('/institutes/candidate-institute')->assertNotFound();
    }
}
