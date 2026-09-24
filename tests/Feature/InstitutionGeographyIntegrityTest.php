<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\InstitutionGeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionGeographyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_institution_with_active_geography_coverage_has_no_finding(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $geography = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'district',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->assertCount(0, (new InstitutionGeographyIntegrityService)->inspect($institution));
    }

    public function test_active_institution_without_active_campus_is_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $findings = (new InstitutionGeographyIntegrityService)->inspect($institution);

        $this->assertSame('missing_active_campus', $findings->first()['type']);
    }

    public function test_active_institution_without_active_geography_coverage_is_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $findings = (new InstitutionGeographyIntegrityService)->inspect($institution);

        $this->assertSame('missing_active_geography', $findings->first()['type']);
        $this->assertSame(1, $findings->first()['active_campus_count']);
    }

    public function test_active_institution_with_only_inactive_geography_is_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $geography = Geography::create([
            'name' => 'Legacy Area',
            'slug' => 'legacy-area',
            'type' => 'district',
            'status' => 'inactive',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $findings = (new InstitutionGeographyIntegrityService)->inspect($institution);

        $this->assertSame('missing_active_geography', $findings->first()['type']);
    }

    public function test_inactive_institution_is_not_flagged(): void
    {
        $institution = Institution::create([
            'name' => 'Archived Institute',
            'slug' => 'archived-institute',
            'institution_type' => 'university',
            'status' => 'inactive',
        ]);

        $this->assertCount(0, (new InstitutionGeographyIntegrityService)->inspect($institution));
    }
}
