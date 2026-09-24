<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Services\InstitutionIdentityIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionIdentityIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_institution_with_complete_identity_is_clean(): void
    {
        $institution = Institution::query()->create([
            'name' => 'Example University',
            'official_name' => 'Example University',
            'short_name' => 'EU',
            'slug' => 'example-university',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $this->assertSame([], app(InstitutionIdentityIntegrityService::class)->inspect($institution));
    }

    public function test_inactive_institution_is_outside_identity_findings(): void
    {
        $institution = Institution::query()->create([
            'name' => '',
            'slug' => '',
            'institution_type' => 'university',
            'status' => 'inactive',
        ]);

        $this->assertSame([], app(InstitutionIdentityIntegrityService::class)->inspect($institution));
    }

    public function test_active_institution_reports_missing_name_and_slug(): void
    {
        $institution = Institution::query()->create([
            'name' => '',
            'slug' => '',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $this->assertSame(
            ['missing_name', 'missing_slug'],
            app(InstitutionIdentityIntegrityService::class)->inspect($institution)
        );
    }

    public function test_active_institution_reports_invalid_slug(): void
    {
        $institution = Institution::query()->create([
            'name' => 'Example University',
            'slug' => 'Example University',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $this->assertContains(
            'invalid_slug',
            app(InstitutionIdentityIntegrityService::class)->inspect($institution)
        );
    }

    public function test_duplicate_active_slug_is_reported_without_mutation(): void
    {
        $first = Institution::query()->create([
            'name' => 'First University',
            'slug' => 'shared-university',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $second = Institution::query()->create([
            'name' => 'Second University',
            'slug' => 'shared-university',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $before = [$first->name, $first->slug, $second->name, $second->slug];

        $findings = app(InstitutionIdentityIntegrityService::class)->inspect($second);

        $this->assertContains('duplicate_active_slug', $findings);
        $this->assertSame($before, [
            $first->fresh()->name,
            $first->fresh()->slug,
            $second->fresh()->name,
            $second->fresh()->slug,
        ]);
    }
}
