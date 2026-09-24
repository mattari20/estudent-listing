<?php

namespace Tests\Feature;

use App\Models\AdmissionIntake;
use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
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

    public function test_search_can_filter_by_geography_hierarchy(): void
    {
        $punjab = Geography::create(['name' => 'Punjab', 'slug' => 'punjab', 'type' => 'province', 'status' => 'active']);
        $lahoreDistrict = Geography::create(['parent_id' => $punjab->id, 'name' => 'Lahore District', 'slug' => 'lahore', 'type' => 'district', 'status' => 'active']);
        $lahore = Geography::create(['parent_id' => $lahoreDistrict->id, 'name' => 'Lahore', 'slug' => 'lahore', 'type' => 'city', 'status' => 'active']);

        $sindh = Geography::create(['name' => 'Sindh', 'slug' => 'sindh', 'type' => 'province', 'status' => 'active']);
        $karachiDistrict = Geography::create(['parent_id' => $sindh->id, 'name' => 'Karachi District', 'slug' => 'karachi', 'type' => 'district', 'status' => 'active']);
        $karachi = Geography::create(['parent_id' => $karachiDistrict->id, 'name' => 'Karachi', 'slug' => 'karachi', 'type' => 'city', 'status' => 'active']);

        $lahoreInstitution = Institution::create(['name' => 'Lahore University', 'slug' => 'lahore-university', 'status' => 'active']);
        $karachiInstitution = Institution::create(['name' => 'Karachi University', 'slug' => 'karachi-university', 'status' => 'active']);

        Campus::create(['institution_id' => $lahoreInstitution->id, 'geography_id' => $lahore->id, 'name' => 'Main Campus', 'slug' => 'lahore-main', 'status' => 'active']);
        Campus::create(['institution_id' => $karachiInstitution->id, 'geography_id' => $karachi->id, 'name' => 'Main Campus', 'slug' => 'karachi-main', 'status' => 'active']);

        $this->get('/institutes/search?province=Punjab')
            ->assertOk()
            ->assertSee('Lahore University')
            ->assertDontSee('Karachi University');

        $this->get('/institutes/search?district=Lahore%20District')
            ->assertOk()
            ->assertSee('Lahore University')
            ->assertDontSee('Karachi University');
    }

    public function test_public_geography_filters_ignore_inactive_campus_and_ancestor(): void
    {
        $punjab = Geography::create(['name' => 'Punjab', 'slug' => 'punjab', 'type' => 'province', 'status' => 'active']);
        $lahoreDistrict = Geography::create(['parent_id' => $punjab->id, 'name' => 'Lahore District', 'slug' => 'lahore', 'type' => 'district', 'status' => 'active']);
        $lahore = Geography::create(['parent_id' => $lahoreDistrict->id, 'name' => 'Lahore', 'slug' => 'lahore', 'type' => 'city', 'status' => 'active']);

        $inactiveCampusInstitution = Institution::create(['name' => 'Inactive Campus University', 'slug' => 'inactive-campus-university', 'status' => 'active']);
        Campus::create([
            'institution_id' => $inactiveCampusInstitution->id,
            'geography_id' => $lahore->id,
            'name' => 'Archived Campus',
            'slug' => 'archived-campus',
            'status' => 'inactive',
        ]);

        $inactiveDistrict = Geography::create(['parent_id' => $punjab->id, 'name' => 'Inactive District', 'slug' => 'inactive-district', 'type' => 'district', 'status' => 'inactive']);
        $inactiveCity = Geography::create(['parent_id' => $inactiveDistrict->id, 'name' => 'Inactive City', 'slug' => 'inactive-city', 'type' => 'city', 'status' => 'active']);

        $inactiveAncestorInstitution = Institution::create(['name' => 'Inactive Ancestor University', 'slug' => 'inactive-ancestor-university', 'status' => 'active']);
        Campus::create([
            'institution_id' => $inactiveAncestorInstitution->id,
            'geography_id' => $inactiveCity->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->get('/institutes/search?city=Lahore')
            ->assertOk()
            ->assertDontSee('Inactive Campus University');

        $this->get('/institutes/search?district=Inactive%20District')
            ->assertOk()
            ->assertDontSee('Inactive Ancestor University');
    }

    public function test_search_can_filter_by_program_level_delivery_mode_and_admission_status(): void
    {
        $institution = Institution::create(['name' => 'Future University', 'slug' => 'future-university', 'status' => 'active']);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'BS Computer Science',
            'slug' => 'bs-computer-science',
            'program_level' => 'undergraduate',
            'status' => 'active',
        ]);
        $offering = ProgramOffering::create([
            'program_id' => $program->id,
            'delivery_mode' => 'on_campus',
            'status' => 'active',
            'availability' => 'open',
        ]);
        AdmissionIntake::create([
            'program_offering_id' => $offering->id,
            'label' => 'Fall 2026',
            'status' => 'applications_open',
        ]);

        $this->get('/institutes/search?program_level=undergraduate&delivery_mode=on_campus&admission_status=applications_open')
            ->assertOk()
            ->assertSee('Future University');
    }

    public function test_search_route_preserves_directory_root(): void
    {
        $this->get('/institutes/search')->assertOk()->assertSee('Institute Search');
    }
}
