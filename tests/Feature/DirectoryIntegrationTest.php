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
}
