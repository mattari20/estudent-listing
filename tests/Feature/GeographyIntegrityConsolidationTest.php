<?php

namespace TestsFeature;

use AppModelsCampus;
use AppModelsGeography;
use AppModelsInstitution;
use AppServicesInstituteGeographyContextService;
use AppServicesInstituteGeographyIntegrityService;
use IlluminateFoundationTestingRefreshDatabase;
use TestsTestCase;

class GeographyIntegrityConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_active_geography_chain_is_clean_and_context_is_root_to_leaf(): void
    {
        $province = Geography::create([
            'name' => 'Punjab',
            'slug' => 'punjab',
            'type' => 'province',
            'status' => 'active',
        ]);

        $district = Geography::create([
            'parent_id' => $province->id,
            'name' => 'Lahore District',
            'slug' => 'lahore-district',
            'type' => 'district',
            'status' => 'active',
        ]);

        $city = Geography::create([
            'parent_id' => $district->id,
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);

        $institution = Institution::create([
            'name' => 'Geography University',
            'slug' => 'geography-university',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $city->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $findings = (new InstituteGeographyIntegrityService)->inspectCampus($campus);

        $this->assertSame('valid_geography', $findings->first()['type']);
        $this->assertSame(
            'Punjab · Lahore District · Lahore',
            (new InstituteGeographyContextService)->forCampus($campus)
        );
    }

    public function test_inactive_ancestor_is_reported_and_context_is_hidden(): void
    {
        $province = Geography::create([
            'name' => 'Legacy Province',
            'slug' => 'legacy-province',
            'type' => 'province',
            'status' => 'inactive',
        ]);

        $city = Geography::create([
            'parent_id' => $province->id,
            'name' => 'Legacy City',
            'slug' => 'legacy-city',
            'type' => 'city',
            'status' => 'active',
        ]);

        $institution = Institution::create([
            'name' => 'Legacy Geography University',
            'slug' => 'legacy-geography-university',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $city->id,
            'name' => 'Main Campus',
            'slug' => 'legacy-main',
            'status' => 'active',
        ]);

        $finding = (new InstituteGeographyIntegrityService)->inspectCampus($campus)->first();

        $this->assertSame('inactive_geography_ancestor', $finding['type']);
        $this->assertNull((new InstituteGeographyContextService)->forCampus($campus));
    }

    public function test_geography_cycle_is_reported_without_mutation(): void
    {
        $first = Geography::create([
            'name' => 'Cycle A',
            'slug' => 'cycle-a',
            'type' => 'city',
            'status' => 'active',
        ]);

        $second = Geography::create([
            'name' => 'Cycle B',
            'slug' => 'cycle-b',
            'type' => 'district',
            'status' => 'active',
            'parent_id' => $first->id,
        ]);

        $first->update(['parent_id' => $second->id]);

        $institution = Institution::create([
            'name' => 'Cycle University',
            'slug' => 'cycle-university',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $first->id,
            'name' => 'Cycle Campus',
            'slug' => 'cycle-campus',
            'status' => 'active',
        ]);

        $finding = (new InstituteGeographyIntegrityService)->inspectCampus($campus)->first();

        $this->assertSame('geography_cycle', $finding['type']);
        $this->assertSame($first->id, $campus->fresh()->geography_id);
    }

    public function test_public_search_does_not_match_inactive_geography_ancestor(): void
    {
        $province = Geography::create([
            'name' => 'Inactive Province',
            'slug' => 'inactive-province',
            'type' => 'province',
            'status' => 'inactive',
        ]);

        $city = Geography::create([
            'parent_id' => $province->id,
            'name' => 'Visible City',
            'slug' => 'visible-city',
            'type' => 'city',
            'status' => 'active',
        ]);

        $institution = Institution::create([
            'name' => 'Inactive Ancestor University',
            'slug' => 'inactive-ancestor-university',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $city->id,
            'name' => 'Main Campus',
            'slug' => 'inactive-ancestor-campus',
            'status' => 'active',
        ]);

        $this->get('/institutes/search?city=Visible%20City')
            ->assertOk()
            ->assertDontSee('Inactive Ancestor University');
    }

    public function test_geography_integrity_review_is_operator_protected_and_read_only(): void
    {
        $this->get(route('institutes.operations.integrity'))
            ->assertRedirect();

        $this->post(route('institutes.operations.integrity'))
            ->assertStatus(405);
    }
}
