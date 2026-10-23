<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Services\CampusRelationshipIntegrityService;
use Tests\TestCase;

class InactiveCampusRelationshipIntegrityTest extends TestCase
{
    public function test_inactive_campus_is_not_reported_as_an_active_integrity_issue(): void
    {
        $campus = new Campus(['status' => 'inactive']);
        $this->assertSame([], app(CampusRelationshipIntegrityService::class)->inspect($campus));
    }
}
