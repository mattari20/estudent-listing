<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\InstituteGeographyCrossDomainIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyRegressionSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_institution_has_no_cross_domain_geography_findings(): void
    {
        $institution = Institution::create([
            'name' => 'Safe University',
            'slug' => 'safe-university',
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

    public function test_invalid_active_campus_remains_detectable_without_mutation(): void
    {
        $institution = Institution::create([
            'name' => 'Review University',
            'slug' => 'review-university',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $findings = (new InstituteGeographyCrossDomainIntegrityService)->inspect($institution);

        $this->assertTrue($findings->contains(fn ($finding) => $finding['type'] === 'missing_geography'));
        $this->assertNull($campus->fresh()->geography_id);
        $this->assertSame('active', $campus->fresh()->status);
    }

    public function test_operator_integrity_route_is_not_publicly_writable_or_readable(): void
    {
        $this->get(route('institutes.operations.integrity'))
            ->assertRedirect();

        $this->post(route('institutes.operations.integrity'))
            ->assertStatus(405);
    }
}
