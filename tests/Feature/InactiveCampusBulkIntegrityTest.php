<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Services\CampusRelationshipIntegrityService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InactiveCampusBulkIntegrityTest extends TestCase
{
    public function test_bulk_integrity_scan_skips_inactive_campuses(): void
    {
        $campuses = new Collection([new Campus(['status' => 'inactive'])]);
        $this->assertSame([], app(CampusRelationshipIntegrityService::class)->inspectMany($campuses));
    }
}
