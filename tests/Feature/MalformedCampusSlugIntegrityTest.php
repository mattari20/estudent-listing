<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Services\CampusRelationshipIntegrityService;
use Tests\TestCase;

class MalformedCampusSlugIntegrityTest extends TestCase
{
    public function test_integrity_scan_flags_malformed_slug_without_changing_campus(): void
    {
        $campus = new Campus(['status' => 'active', 'name' => 'Sample Campus', 'slug' => 'Bad Campus']);
        $findings = app(CampusRelationshipIntegrityService::class)->inspect($campus);
        $this->assertContains('invalid_slug', $findings);
        $this->assertSame('Bad Campus', $campus->slug);
    }
}
