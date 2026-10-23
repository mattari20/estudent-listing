<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Services\InstituteGeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingActiveCampusGeographyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_institution_without_campuses_gets_a_clear_integrity_finding(): void
    {
        $institution = Institution::create([
            'name' => 'No Campus Integrity Institution',
            'slug' => 'no-campus-integrity-institution',
            'status' => 'active',
        ]);

        $findings = app(InstituteGeographyIntegrityService::class)->inspectInstitution($institution);
        $this->assertSame('missing_active_campus', $findings->first()['type']);
        $this->assertSame($institution->id, $findings->first()['institution_id']);
    }
}
