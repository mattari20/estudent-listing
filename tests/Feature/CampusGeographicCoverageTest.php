<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CampusGeographicCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_geographic_coverage_counts_active_campuses_only(): void
    {
        $province = Geography::query()->create([
            'name' => 'Active Campus Coverage Province',
            'slug' => 'active-campus-coverage-province',
            'type' => 'province',
            'status' => 'active',
        ]);
        $activeInstitution = Institution::query()->create([
            'name' => 'Active Coverage Institution',
            'slug' => 'active-coverage-institution',
            'status' => 'active',
        ]);
        $inactiveInstitution = Institution::query()->create([
            'name' => 'Inactive Coverage Institution',
            'slug' => 'inactive-coverage-institution',
            'status' => 'active',
        ]);

        Campus::query()->create([
            'institution_id' => $activeInstitution->id,
            'geography_id' => $province->id,
            'name' => 'Active Coverage Campus',
            'slug' => 'active-coverage-campus',
            'status' => 'active',
        ]);
        Campus::query()->create([
            'institution_id' => $inactiveInstitution->id,
            'geography_id' => $province->id,
            'name' => 'Inactive Coverage Campus',
            'slug' => 'inactive-coverage-campus',
            'status' => 'inactive',
        ]);

        $this->assertSame(0, Artisan::call('institutes:data-gap-report', ['--json' => true]));
        $report = json_decode(Artisan::output(), true);
        $provinceRows = $report['sections']['coverage']['active_campuses_by_province_or_territory'];
        $provinceRow = collect($provinceRows)->firstWhere('name', 'Active Campus Coverage Province');

        $this->assertIsArray($provinceRow);
        $this->assertSame(1, $provinceRow['count']);
        $this->assertFalse($report['mutation_performed']);
    }
}
