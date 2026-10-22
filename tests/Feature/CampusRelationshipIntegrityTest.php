<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\CampusRelationshipIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CampusRelationshipIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_active_campus_has_no_findings(): void
    {
        $institution = Institution::create([
            'name' => 'Test University',
            'slug' => 'test-university',
            'institution_type' => 'university',
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
            'status' => 'active',
        ]);

        $this->assertSame([], (new CampusRelationshipIntegrityService)->inspect($campus));
    }

    public function test_inactive_campus_is_excluded(): void
    {
        $campus = new Campus([
            'name' => 'Archived Campus',
            'slug' => 'archived-campus',
            'status' => 'inactive',
        ]);

        $this->assertSame([], (new CampusRelationshipIntegrityService)->inspect($campus));
    }

    public function test_missing_institution_is_reported(): void
    {
        $campus = new Campus([
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
            'geography_id' => null,
        ]);

        $findings = (new CampusRelationshipIntegrityService)->inspect($campus);

        $this->assertContains('missing_institution', $findings);
    }

    public function test_inactive_institution_is_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Inactive University',
            'slug' => 'inactive-university',
            'institution_type' => 'university',
            'status' => 'inactive',
        ]);

        $campus = new Campus([
            'institution_id' => $institution->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $findings = (new CampusRelationshipIntegrityService)->inspect($campus);

        $this->assertContains('institution_not_active', $findings);
    }

    public function test_missing_and_inactive_geography_are_reported(): void
    {
        $missing = new Campus([
            'institution_id' => null,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->assertContains(
            'missing_geography',
            (new CampusRelationshipIntegrityService)->inspect($missing)
        );

        $geography = Geography::create([
            'name' => 'Archived City',
            'slug' => 'archived-city',
            'type' => 'city',
            'status' => 'inactive',
        ]);

        $inactive = new Campus([
            'institution_id' => null,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->assertContains(
            'geography_not_active',
            (new CampusRelationshipIntegrityService)->inspect($inactive)
        );
    }

    public function test_invalid_identity_fields_are_reported(): void
    {
        $campus = new Campus([
            'institution_id' => null,
            'name' => '',
            'slug' => 'Bad Slug',
            'status' => 'active',
        ]);

        $findings = (new CampusRelationshipIntegrityService)->inspect($campus);

        $this->assertContains('missing_name', $findings);
        $this->assertContains('invalid_slug', $findings);
    }

    public function test_collection_detects_duplicate_campus_identity_without_mutation(): void
    {
        $first = new Campus([
            'institution_id' => 42,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $second = new Campus([
            'institution_id' => 42,
            'name' => 'Main Campus Lahore',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $campuses = new Collection([$first, $second]);
        $before = $campuses->map(fn (Campus $campus) => [$campus->name, $campus->slug])->all();

        $results = (new CampusRelationshipIntegrityService)->inspectMany($campuses);

        $this->assertCount(2, $results);
        $this->assertContains('duplicate_institution_slug', $results[0]['findings']);
        $this->assertContains('duplicate_institution_slug', $results[1]['findings']);
        $this->assertSame(
            $before,
            $campuses->map(fn (Campus $campus) => [$campus->name, $campus->slug])->all()
        );
    }

    public function test_active_campus_without_geography_is_flagged(): void
    {
        $institution = Institution::create(['name' => 'Campus Audit University', 'slug' => 'campus-audit-university', 'status' => 'active']);
        $campus = Campus::create(['institution_id' => $institution->id, 'geography_id' => null, 'name' => 'Main Campus', 'slug' => 'main-campus', 'status' => 'active']);
        $this->assertContains('missing_geography', app(CampusRelationshipIntegrityService::class)->inspect($campus));
    }

    public function test_inspector_flags_duplicate_slug_for_same_institution(): void
    {
        $first = new Campus(['institution_id' => 42, 'name' => 'Main Campus', 'slug' => 'main-campus', 'status' => 'active']);
        $second = new Campus(['institution_id' => 42, 'name' => 'City Campus', 'slug' => 'main-campus', 'status' => 'active']);
        $results = app(CampusRelationshipIntegrityService::class)->inspectMany(new Collection([$first, $second]));
        $this->assertCount(2, $results);
        $this->assertContains('duplicate_institution_slug', $results[0]['findings']);
        $this->assertContains('duplicate_institution_slug', $results[1]['findings']);
    }
}
