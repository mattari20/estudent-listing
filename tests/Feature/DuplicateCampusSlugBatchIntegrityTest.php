<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Services\CampusRelationshipIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DuplicateCampusSlugBatchIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_integrity_scan_flags_duplicate_institution_slug_pairs(): void
    {
        $institution = Institution::create([
            'name' => 'Duplicate Slug Test Institution',
            'slug' => 'duplicate-slug-test-institution',
            'status' => 'active',
        ]);
        $campuses = new Collection([
            new Campus(['institution_id' => $institution->id, 'name' => 'North Campus', 'slug' => 'shared-campus', 'status' => 'active']),
            new Campus(['institution_id' => $institution->id, 'name' => 'South Campus', 'slug' => 'shared-campus', 'status' => 'active']),
        ]);

        $findings = app(CampusRelationshipIntegrityService::class)->inspectMany($campuses);
        $this->assertCount(2, $findings);
        $this->assertContains('duplicate_institution_slug', $findings[0]['findings']);
        $this->assertContains('duplicate_institution_slug', $findings[1]['findings']);
    }
}
