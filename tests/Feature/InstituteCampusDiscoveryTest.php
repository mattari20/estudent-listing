<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InstituteCampusDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_profile_refuses_campus_level_parent_before_creating_candidates(): void
    {
        Institution::create([
            'name' => 'COMSATS University Islamabad Lahore Campus',
            'slug' => 'comsats-university-islamabad-lahore-campus',
            'institution_type' => 'Public University Campus',
            'status' => 'active',
        ]);

        try {
            app(InstituteDiscoveryAgentService::class)->run('cui-lahore-campus');
            $this->fail('Campus discovery must not use a campus-level record as its own parent.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Campus discovery requires a standalone parent institution. The configured parent appears to be campus-level; resolve the canonical parent institution before running campus discovery.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing('sources', [
            'url' => 'https://lahore.comsats.edu.pk/',
        ]);
        $this->assertDatabaseCount('source_snapshots', 0);
        $this->assertDatabaseCount('import_batches', 0);
    }
}
