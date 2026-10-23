<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DataGapActiveCampusMissingGeographyCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_coverage_count_distinguishes_active_and_inactive_campuses_without_geography(): void
    {
        $institution = Institution::create([
            'name' => 'Geography Count University',
            'slug' => 'geography-count-university',
            'status' => 'active',
        ]);
        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Active Missing Geography Campus',
            'slug' => 'active-missing-geography-campus',
            'status' => 'active',
            'geography_id' => null,
        ]);
        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Inactive Missing Geography Campus',
            'slug' => 'inactive-missing-geography-campus',
            'status' => 'inactive',
            'geography_id' => null,
        ]);

        Artisan::call('institutes:data-gap-report', ['--json' => true]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $coverage = $report['sections']['coverage']['campus_geography_coverage'];

        $this->assertSame(2, $coverage['total_campuses']);
        $this->assertSame(0, $coverage['campuses_with_geography']);
        $this->assertSame(2, $coverage['campuses_without_geography']);
        $this->assertSame(1, $coverage['active_campuses_without_geography']);
    }
}
