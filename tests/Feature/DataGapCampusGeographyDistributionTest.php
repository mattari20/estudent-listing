<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DataGapCampusGeographyDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_gap_report_distributes_active_campuses_to_active_province_ancestor(): void
    {
        $country = Geography::create([
            'name' => 'Distribution Test Country',
            'slug' => 'distribution-test-country',
            'type' => 'country',
            'status' => 'active',
        ]);
        $province = Geography::create([
            'name' => 'Distribution Test Province',
            'slug' => 'distribution-test-province',
            'type' => 'province',
            'parent_id' => $country->id,
            'status' => 'active',
        ]);
        $institution = Institution::create([
            'name' => 'Distribution Test University',
            'slug' => 'distribution-test-university',
            'status' => 'active',
        ]);
        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Distribution Test Campus',
            'slug' => 'distribution-test-campus',
            'status' => 'active',
            'geography_id' => $province->id,
        ]);

        Artisan::call('institutes:data-gap-report', ['--json' => true]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $distribution = $report['sections']['coverage']['active_campuses_by_province_or_territory'];

        $this->assertContains([
            'geography_id' => $province->id,
            'name' => 'Distribution Test Province',
            'count' => 1,
        ], $distribution);
    }
}
