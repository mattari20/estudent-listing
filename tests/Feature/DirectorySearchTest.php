<?php

namespace Tests\Feature;

use App\Models\AdmissionIntake;
use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Models\InstitutionName;
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

    public function test_geography_filters_ignore_inactive_campuses_and_geographies(): void
    {
        $punjab = Geography::create(['name' => 'Punjab', 'slug' => 'punjab', 'type' => 'province', 'status' => 'active']);
        $district = Geography::create(['parent_id' => $punjab->id, 'name' => 'Lahore District', 'slug' => 'lahore', 'type' => 'district', 'status' => 'active']);
        $activeCity = Geography::create(['parent_id' => $district->id, 'name' => 'Lahore', 'slug' => 'lahore', 'type' => 'city', 'status' => 'active']);
        $inactiveCity = Geography::create(['parent_id' => $district->id, 'name' => 'Legacy Lahore', 'slug' => 'legacy-lahore', 'type' => 'city', 'status' => 'inactive']);

        $activeInstitution = Institution::create(['name' => 'Active Lahore University', 'slug' => 'active-lahore-university', 'status' => 'active']);
        $inactiveCampusInstitution = Institution::create(['name' => 'Inactive Campus University', 'slug' => 'inactive-campus-university', 'status' => 'active']);
        $inactiveGeographyInstitution = Institution::create(['name' => 'Inactive Geography University', 'slug' => 'inactive-geography-university', 'status' => 'active']);

        Campus::create(['institution_id' => $activeInstitution->id, 'geography_id' => $activeCity->id, 'name' => 'Main Campus', 'slug' => 'active-main', 'status' => 'active']);
        Campus::create(['institution_id' => $inactiveCampusInstitution->id, 'geography_id' => $activeCity->id, 'name' => 'Closed Campus', 'slug' => 'closed-campus', 'status' => 'inactive']);
        Campus::create(['institution_id' => $inactiveGeographyInstitution->id, 'geography_id' => $inactiveCity->id, 'name' => 'Legacy Campus', 'slug' => 'legacy-campus', 'status' => 'active']);

        $this->get('/institutes/search?province=Punjab')
            ->assertOk()
            ->assertSee('Active Lahore University')
            ->assertDontSee('Inactive Campus University')
            ->assertDontSee('Inactive Geography University');

        $this->get('/institutes/search?city=Lahore')
            ->assertOk()
            ->assertSee('Active Lahore University')
            ->assertDontSee('Inactive Campus University')
            ->assertDontSee('Inactive Geography University');
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

    public function test_public_search_counts_only_active_campuses_and_programs(): void
    {
        $institution = Institution::create([
            'name' => 'Lifecycle University',
            'slug' => 'lifecycle-university',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Active Campus',
            'slug' => 'active-campus',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Closed Campus',
            'slug' => 'closed-campus',
            'status' => 'inactive',
        ]);

        Program::create([
            'institution_id' => $institution->id,
            'name' => 'Active Program',
            'slug' => 'active-program',
            'status' => 'active',
        ]);

        Program::create([
            'institution_id' => $institution->id,
            'name' => 'Historical Program',
            'slug' => 'historical-program',
            'status' => 'inactive',
        ]);

        $this->get('/institutes/search?q=Lifecycle')
            ->assertOk()
            ->assertSee('1 campuses')
            ->assertSee('1 programs');
    }

    public function test_search_route_preserves_directory_root(): void
    {
        $this->get('/institutes/search')->assertOk()->assertSee('Find an institute');
    }
    public function test_public_search_never_returns_candidate_or_inactive_institutions(): void
    {
        Institution::create(['name' => 'Public Active University', 'slug' => 'public-active-university', 'status' => 'active']);
        Institution::create(['name' => 'Private Candidate University', 'slug' => 'private-candidate-university', 'status' => 'candidate']);
        Institution::create(['name' => 'Archived Inactive University', 'slug' => 'archived-inactive-university', 'status' => 'inactive']);

        $this->get('/institutes/search?q=University')
            ->assertOk()
            ->assertSee('Public Active University')
            ->assertDontSee('Private Candidate University')
            ->assertDontSee('Archived Inactive University');
    }


    public function test_search_counts_only_active_campuses_and_programs(): void
    {
        $institution = Institution::create(['name' => 'Lifecycle Search University', 'slug' => 'lifecycle-search-university', 'status' => 'active']);
        Campus::create(['institution_id' => $institution->id, 'name' => 'Active Campus', 'slug' => 'active-campus', 'status' => 'active']);
        Campus::create(['institution_id' => $institution->id, 'name' => 'Inactive Campus', 'slug' => 'inactive-campus', 'status' => 'inactive']);
        Program::create(['institution_id' => $institution->id, 'name' => 'Active Program', 'slug' => 'active-program', 'status' => 'active']);
        Program::create(['institution_id' => $institution->id, 'name' => 'Inactive Program', 'slug' => 'inactive-program', 'status' => 'inactive']);
        $this->get('/institutes/search?q=Lifecycle')
            ->assertOk()
            ->assertSee('1 campuses')
            ->assertSee('1 programs');
    }
    public function test_public_search_matches_current_institution_aliases(): void
    {
        $institution = Institution::query()->create([
            'name' => 'National Institute of Technology',
            'official_name' => 'National Institute of Technology',
            'short_name' => 'NIT',
            'slug' => 'national-institute-of-technology',
            'status' => 'active',
        ]);

        InstitutionName::query()->create([
            'institution_id' => $institution->id,
            'name' => 'NIT Lahore',
            'name_type' => 'alias',
            'language' => 'en',
            'is_current' => true,
        ]);

        $this->get('/institutes/search?q=NIT%20Lahore')
            ->assertOk()
            ->assertSee('National Institute of Technology');

        $this->get('/institutes/search?q=Former%20Institute%20Name')
            ->assertOk()
            ->assertDontSee('National Institute of Technology');
    }


    public function test_search_empty_state_explains_how_to_recover_from_no_results(): void
    {
        $this->get('/institutes/search?q=NoSuchInstitute987')
            ->assertOk()
            ->assertSee('No matching institutes found')
            ->assertSee('Try removing a filter or using a broader search.')
            ->assertSee('value="NoSuchInstitute987"', false)
            ->assertSee('Clear all');
    }


}
