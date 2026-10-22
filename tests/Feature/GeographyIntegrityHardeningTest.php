<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Services\GeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GeographyIntegrityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_active_child_has_no_findings(): void
    {
        $parent = Geography::create([
            'name' => 'Punjab',
            'slug' => 'punjab',
            'type' => 'province',
            'status' => 'active',
        ]);

        $geography = Geography::create([
            'parent_id' => $parent->id,
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'district',
            'status' => 'active',
        ]);

        $this->assertSame([], (new GeographyIntegrityService)->inspect($geography));
    }

    public function test_inactive_geography_is_excluded(): void
    {
        $geography = new Geography([
            'name' => 'Archived',
            'slug' => 'archived',
            'type' => 'city',
            'status' => 'inactive',
        ]);

        $this->assertSame([], (new GeographyIntegrityService)->inspect($geography));
    }

    public function test_country_with_parent_is_reported(): void
    {
        $parent = Geography::create([
            'name' => 'Invalid Parent',
            'slug' => 'invalid-parent',
            'type' => 'province',
            'status' => 'active',
        ]);

        $country = new Geography([
            'parent_id' => $parent->id,
            'name' => 'Pakistan',
            'slug' => 'pakistan',
            'type' => 'country',
            'status' => 'active',
        ]);

        $this->assertContains('country_has_parent', (new GeographyIntegrityService)->inspect($country));
    }

    public function test_missing_or_inactive_parent_is_reported(): void
    {
        $missing = new Geography([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'district',
            'status' => 'active',
        ]);

        $this->assertContains('non_country_without_parent', (new GeographyIntegrityService)->inspect($missing));

        $parent = Geography::create([
            'name' => 'Archived Province',
            'slug' => 'archived-province',
            'type' => 'province',
            'status' => 'inactive',
        ]);

        $inactiveParent = new Geography([
            'parent_id' => $parent->id,
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'district',
            'status' => 'active',
        ]);

        $this->assertContains('parent_not_active', (new GeographyIntegrityService)->inspect($inactiveParent));
    }

    public function test_self_parent_and_invalid_identity_are_reported(): void
    {
        $geography = new Geography([
            'name' => '',
            'slug' => 'Bad Slug',
            'type' => 'city',
            'status' => 'active',
        ]);

        $geography->id = 17;
        $geography->parent_id = 17;

        $findings = (new GeographyIntegrityService)->inspect($geography);

        $this->assertContains('missing_name', $findings);
        $this->assertContains('invalid_slug', $findings);
        $this->assertContains('self_parent', $findings);
    }

    public function test_collection_detects_duplicate_parent_slug_without_mutation(): void
    {
        $first = new Geography([
            'parent_id' => 8,
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'district',
            'status' => 'active',
        ]);

        $second = new Geography([
            'parent_id' => 8,
            'name' => 'Lahore Duplicate',
            'slug' => 'lahore',
            'type' => 'district',
            'status' => 'active',
        ]);

        $geographies = new Collection([$first, $second]);
        $before = $geographies->map(fn (Geography $geography) => [$geography->name, $geography->slug])->all();

        $results = (new GeographyIntegrityService)->inspectMany($geographies);

        $this->assertCount(2, $results);
        $this->assertContains('duplicate_parent_slug', $results[0]['findings']);
        $this->assertContains('duplicate_parent_slug', $results[1]['findings']);
        $this->assertSame($before, $geographies->map(fn (Geography $geography) => [$geography->name, $geography->slug])->all());
    }

    public function test_validate_preserves_aggregate_contract(): void
    {
        Geography::create([
            'name' => 'Pakistan',
            'slug' => 'pakistan',
            'type' => 'country',
            'status' => 'active',
        ]);

        $result = (new GeographyIntegrityService)->validate();

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }

    public function test_active_non_country_without_parent_is_flagged(): void
    {
        $geography = Geography::create(['parent_id' => null, 'name' => 'Orphan District', 'slug' => 'orphan-district', 'type' => 'district', 'status' => 'active']);
        $this->assertContains('non_country_without_parent', app(GeographyIntegrityService::class)->inspect($geography));
    }
}
