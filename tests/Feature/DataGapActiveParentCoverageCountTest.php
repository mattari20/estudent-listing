<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DataGapActiveParentCoverageCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_coverage_counts_only_active_institutions_missing_active_children(): void
    {
        $withoutActiveChildren = Institution::create([
            'name' => 'No Active Children University',
            'slug' => 'no-active-children-university',
            'status' => 'active',
        ]);
        $inactiveCampusOnly = Institution::create([
            'name' => 'Inactive Campus Only University',
            'slug' => 'inactive-campus-only-university',
            'status' => 'active',
        ]);
        $inactiveInstitution = Institution::create([
            'name' => 'Inactive Parent University',
            'slug' => 'inactive-parent-university',
            'status' => 'inactive',
        ]);
        $withActiveChildren = Institution::create([
            'name' => 'Active Children University',
            'slug' => 'active-children-university',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $inactiveCampusOnly->id,
            'name' => 'Inactive Campus',
            'slug' => 'inactive-campus-only',
            'status' => 'inactive',
        ]);
        Campus::create([
            'institution_id' => $inactiveInstitution->id,
            'name' => 'Inactive Parent Campus',
            'slug' => 'inactive-parent-campus',
            'status' => 'active',
        ]);
        Campus::create([
            'institution_id' => $withActiveChildren->id,
            'name' => 'Active Campus',
            'slug' => 'active-campus-child',
            'status' => 'active',
        ]);
        Program::create([
            'institution_id' => $withActiveChildren->id,
            'name' => 'Active Program',
            'slug' => 'active-program-child',
            'status' => 'active',
        ]);

        Artisan::call('institutes:data-gap-report', ['--json' => true]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $coverage = $report['sections']['coverage'];

        $this->assertSame(2, $coverage['active_institutions_without_active_campuses']);
        $this->assertSame(3, $coverage['active_institutions_without_active_programs']);
        $this->assertArrayNotHasKey('institutions_without_campuses', $coverage);
        $this->assertArrayNotHasKey('institutions_without_programs', $coverage);
        $this->assertDatabaseHas('institutions', ['id' => $withoutActiveChildren->id, 'status' => 'active']);
    }
}
