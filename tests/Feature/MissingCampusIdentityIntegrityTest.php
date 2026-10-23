<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Services\CampusRelationshipIntegrityService;
use Tests\TestCase;

class MissingCampusIdentityIntegrityTest extends TestCase
{
    public function test_integrity_scan_flags_missing_name_and_slug(): void
    {
        $campus = new Campus(['status' => 'active', 'name' => '', 'slug' => '']);
        $findings = app(CampusRelationshipIntegrityService::class)->inspect($campus);
        $this->assertContains('missing_name', $findings);
        $this->assertContains('missing_slug', $findings);
    }
}
