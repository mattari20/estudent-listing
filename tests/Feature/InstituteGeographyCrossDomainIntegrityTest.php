<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Services\InstituteGeographyCrossDomainIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteGeographyCrossDomainIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_institution_without_active_campus_is_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Integrity Test Institute',
            'slug' => 'integrity-test-institute',
            'status' => 'active',
        ]);

        $findings = app(InstituteGeographyCrossDomainIntegrityService::class)
            ->inspect($institution);

        $this->assertTrue($findings->contains(fn (array $finding): bool =>
            $finding['type'] === 'missing_active_campus'
            && $finding['institution_id'] === $institution->id
        ));
    }

    public function test_inactive_institution_is_not_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Inactive Integrity Test Institute',
            'slug' => 'inactive-integrity-test-institute',
            'status' => 'inactive',
        ]);

        $findings = app(InstituteGeographyCrossDomainIntegrityService::class)
            ->inspect($institution);

        $this->assertCount(0, $findings);
    }
}
