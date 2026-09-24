<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectorySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_institute_search_matches_name(): void
    {
        Institution::create(['name' => 'Lahore Engineering University', 'slug' => 'lahore-engineering-university', 'status' => 'active']);
        Institution::create(['name' => 'Karachi Medical College', 'slug' => 'karachi-medical-college', 'status' => 'active']);

        $this->get('/institutes/search?q=Engineering')
            ->assertOk()
            ->assertSee('Lahore Engineering University')
            ->assertDontSee('Karachi Medical College');
    }

    public function test_search_matches_program_and_discipline(): void
    {
        $institution = Institution::create(['name' => 'Tech University', 'slug' => 'tech-university', 'status' => 'active']);

        Program::create([
            'institution_id' => $institution->id,
            'name' => 'Bachelor of Computer Science',
            'slug' => 'bcs',
            'discipline' => 'Computer Science',
            'status' => 'active',
        ]);

        $this->get('/institutes/search?q=Computer')
            ->assertOk()
            ->assertSee('Tech University');
    }

    public function test_search_can_filter_by_institution_type(): void
    {
        Institution::create(['name' => 'Engineering University', 'slug' => 'engineering-university', 'institution_type' => 'University', 'status' => 'active']);
        Institution::create(['name' => 'Technical College', 'slug' => 'technical-college', 'institution_type' => 'College', 'status' => 'active']);

        $this->get('/institutes/search?type=University')
            ->assertOk()
            ->assertSee('Engineering University')
            ->assertDontSee('Technical College');
    }

    public function test_search_can_filter_by_city(): void
    {
        $lahore = Geography::create(['name' => 'Lahore', 'slug' => 'lahore', 'type' => 'city', 'status' => 'active']);
        $karachi = Geography::create(['name' => 'Karachi', 'slug' => 'karachi', 'type' => 'city', 'status' => 'active']);

        $lahoreInstitution = Institution::create(['name' => 'Lahore University', 'slug' => 'lahore-university', 'status' => 'active']);
        $karachiInstitution = Institution::create(['name' => 'Karachi University', 'slug' => 'karachi-university', 'status' => 'active']);

        Campus::create([
            'institution_id' => $lahoreInstitution->id,
            'geography_id' => $lahore->id,
            'name' => 'Main Campus',
            'slug' => 'lahore-main',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $karachiInstitution->id,
            'geography_id' => $karachi->id,
            'name' => 'Main Campus',
            'slug' => 'karachi-main',
            'status' => 'active',
        ]);

        $this->get('/institutes/search?city=Lahore')
            ->assertOk()
            ->assertSee('Lahore University')
            ->assertDontSee('Karachi University');
    }

    public function test_search_route_preserves_directory_root(): void
    {
        $this->get('/institutes/search')->assertOk()->assertSee('Institute Search');
    }
}
