<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Models\InstitutionName;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_versioned_institution_api_lists_active_institutions(): void
    {
        Institution::create([
            'name' => 'API University',
            'slug' => 'api-university',
            'status' => 'active',
        ]);

        Institution::create([
            'name' => 'Closed University',
            'slug' => 'closed-university',
            'status' => 'closed',
        ]);

        $this->getJson('/api/v1/institutions')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'API University')
            ->assertJsonMissing(['slug' => 'closed-university']);
    }

    public function test_versioned_institution_api_supports_slug_lookup(): void
    {
        $institution = Institution::create([
            'name' => 'API University',
            'slug' => 'api-university',
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/institutions/api-university')
            ->assertOk()
            ->assertJsonPath('name', $institution->name);
    }

    public function test_api_search_matches_governed_alias(): void
    {
        $institution = Institution::create([
            'name' => 'API University',
            'slug' => 'api-university',
            'status' => 'active',
        ]);

        InstitutionName::create([
            'institution_id' => $institution->id,
            'name' => 'AUP',
            'name_type' => 'acronym',
            'is_current' => true,
        ]);

        $this->getJson('/api/v1/institutions?q=AUP')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'api-university');
    }

    public function test_api_search_reuses_governed_program_filter(): void
    {
        $institution = Institution::create([
            'name' => 'API University',
            'slug' => 'api-university',
            'status' => 'active',
        ]);

        Program::create([
            'institution_id' => $institution->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'status' => 'active',
        ]);

        Institution::create([
            'name' => 'Other University',
            'slug' => 'other-university',
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/institutions?program=Computer')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'api-university')
            ->assertJsonCount(1, 'data');
    }

    public function test_api_profile_excludes_inactive_campuses_programs_offerings_and_geography(): void
    {
        $institution = Institution::create([
            'name' => 'API University',
            'slug' => 'api-university',
            'status' => 'active',
        ]);

        $activeGeography = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);

        $inactiveGeography = Geography::create([
            'name' => 'Old Lahore',
            'slug' => 'old-lahore',
            'type' => 'city',
            'status' => 'inactive',
        ]);

        $activeCampus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $activeGeography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'campus_type' => 'main',
            'status' => 'active',
        ]);

        $inactiveCampus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $activeGeography->id,
            'name' => 'Closed Campus',
            'slug' => 'closed-campus',
            'campus_type' => 'branch',
            'status' => 'closed',
        ]);

        $geographyGapCampus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $inactiveGeography->id,
            'name' => 'Unpublished Campus',
            'slug' => 'unpublished-campus',
            'campus_type' => 'branch',
            'status' => 'active',
        ]);

        $activeProgram = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'status' => 'active',
        ]);

        $inactiveProgram = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Old Program',
            'slug' => 'old-program',
            'status' => 'closed',
        ]);

        ProgramOffering::create([
            'program_id' => $activeProgram->id,
            'campus_id' => $activeCampus->id,
            'delivery_mode' => 'on_campus',
            'status' => 'active',
            'intake_label' => 'Fall',
            'availability' => 'open',
        ]);

        ProgramOffering::create([
            'program_id' => $activeProgram->id,
            'campus_id' => $inactiveCampus->id,
            'delivery_mode' => 'on_campus',
            'status' => 'active',
            'intake_label' => 'Closed Campus',
            'availability' => 'closed',
        ]);

        ProgramOffering::create([
            'program_id' => $inactiveProgram->id,
            'campus_id' => $activeCampus->id,
            'delivery_mode' => 'on_campus',
            'status' => 'active',
            'intake_label' => 'Old Program',
            'availability' => 'closed',
        ]);

        $response = $this->getJson('/api/v1/institutions/api-university')
            ->assertOk();

        $response
            ->assertJsonCount(1, 'campuses')
            ->assertJsonPath('campuses.0.name', 'Main Campus')
            ->assertJsonPath('campuses.0.geography.name', 'Lahore')
            ->assertJsonCount(1, 'programs')
            ->assertJsonPath('programs.0.name', 'Computer Science')
            ->assertJsonCount(1, 'programs.0.offerings')
            ->assertJsonPath('programs.0.offerings.0.intake_label', 'Fall');

        $this->assertNotSame($inactiveCampus->id, $activeCampus->id);
        $this->assertNotSame($geographyGapCampus->id, $activeCampus->id);
    }

    public function test_api_profile_includes_active_campus_geography_program_and_offering(): void
    {
        $institution = Institution::create([
            'name' => 'API University',
            'slug' => 'api-university',
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
            'name' => 'Computer Science',
            'slug' => 'computer-science',
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

        $this->getJson('/api/v1/institutions/api-university')
            ->assertOk()
            ->assertJsonPath('campuses.0.name', 'Main Campus')
            ->assertJsonPath('campuses.0.geography.name', 'Lahore')
            ->assertJsonPath('programs.0.name', 'Computer Science')
            ->assertJsonPath('programs.0.offerings.0.intake_label', 'Fall');
    }

    public function test_api_does_not_return_inactive_institution_profile(): void
    {
        Institution::create(['name' => 'Inactive API University', 'slug' => 'inactive-api-university', 'status' => 'inactive']);
        $this->getJson('/api/v1/institutions/inactive-api-university')->assertNotFound();
    }

    public function test_api_does_not_return_candidate_institution_profile(): void
    {
        Institution::create(['name' => 'Candidate API University', 'slug' => 'candidate-api-university', 'status' => 'candidate']);
        $this->getJson('/api/v1/institutions/candidate-api-university')->assertNotFound();
    }
}
