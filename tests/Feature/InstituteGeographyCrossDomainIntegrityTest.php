<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\InstituteGeographyCrossDomainIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteGeographyCrossDomainIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_domain_integrity_combines_institution_and_campus_findings(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Missing Geography Campus',
            'slug' => 'missing-geography-campus',
            'status' => 'active',
        ]);

        $findings = (new InstituteGeographyCrossDomainIntegrityService)->inspect($institution);

        $this->assertTrue($findings->contains(fn ($finding) => $finding['type'] === 'missing_geography'));
        $this->assertTrue($findings->contains(fn ($finding) => $finding['type'] === 'missing_active_geography'));
    }

    public function test_cross_domain_integrity_is_empty_for_valid_active_institution(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'status' => 'active',
        ]);

        $geography = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->assertCount(
            0,
            (new InstituteGeographyCrossDomainIntegrityService)->inspect($institution)
        );
    }

    public function test_inactive_institution_is_not_inspected(): void
    {
        $institution = Institution::create([
            'name' => 'Archived Institute',
            'slug' => 'archived-institute',
            'status' => 'inactive',
        ]);

        $this->assertCount(
            0,
            (new InstituteGeographyCrossDomainIntegrityService)->inspect($institution)
        );
    }
}
