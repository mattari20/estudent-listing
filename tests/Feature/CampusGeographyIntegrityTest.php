<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\CampusGeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusGeographyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function institution(): Institution
    {
        return Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute-' . uniqid(),
            'institution_type' => 'university',
            'status' => 'active',
        ]);
    }

    private function geography(string $name, string $slug, string $status = 'active', ?int $parentId = null): Geography
    {
        return Geography::create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'district',
            'status' => $status,
            'parent_id' => $parentId,
        ]);
    }

    public function test_inactive_campus_is_not_inspected(): void
    {
        $campus = Campus::create([
            'institution_id' => $this->institution()->id,
            'name' => 'Archived Campus',
            'slug' => 'archived-campus',
            'status' => 'inactive',
        ]);

        $this->assertCount(0, (new CampusGeographyIntegrityService)->inspect($campus));
    }

    public function test_active_campus_without_geography_is_reported(): void
    {
        $campus = Campus::create([
            'institution_id' => $this->institution()->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $finding = (new CampusGeographyIntegrityService)->inspect($campus)->first();

        $this->assertSame('missing_geography', $finding['type']);
    }

    public function test_active_campus_with_inactive_geography_is_reported(): void
    {
        $geography = $this->geography('Legacy Area', 'legacy-area', 'inactive');

        $campus = Campus::create([
            'institution_id' => $this->institution()->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $finding = (new CampusGeographyIntegrityService)->inspect($campus)->first();

        $this->assertSame('inactive_geography', $finding['type']);
    }

    public function test_active_campus_with_active_root_geography_has_no_finding(): void
    {
        $geography = $this->geography('Lahore', 'lahore');

        $campus = Campus::create([
            'institution_id' => $this->institution()->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->assertCount(0, (new CampusGeographyIntegrityService)->inspect($campus));
    }

    public function test_active_campus_with_inactive_parent_is_reported(): void
    {
        $parent = $this->geography('Punjab', 'punjab', 'inactive');
        $child = $this->geography('Lahore', 'lahore', 'active', $parent->id);

        $campus = Campus::create([
            'institution_id' => $this->institution()->id,
            'geography_id' => $child->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $finding = (new CampusGeographyIntegrityService)->inspect($campus)->first();

        $this->assertSame('inactive_geography_parent', $finding['type']);
        $this->assertSame($parent->id, $finding['parent_id']);
    }

    public function test_active_campus_with_missing_parent_is_reported(): void
    {
        $child = $this->geography('Lahore', 'lahore', 'active', 999999);

        $campus = Campus::create([
            'institution_id' => $this->institution()->id,
            'geography_id' => $child->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $finding = (new CampusGeographyIntegrityService)->inspect($campus)->first();

        $this->assertSame('missing_geography_parent', $finding['type']);
    }
}
