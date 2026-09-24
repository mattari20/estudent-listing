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

        $this->getJson('/api/v1/institutions')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'API University');
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
}
