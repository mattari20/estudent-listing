<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CanonicalDomainFoundationTest extends TestCase
{
    use RefreshDatabase;
    public function test_canonical_domain_tables_can_be_migrated(): void
    {
        $this->assertDatabaseCount('institutions', 0);
        $this->assertDatabaseCount('geographies', 0);
        $this->assertDatabaseCount('campuses', 0);
        $this->assertDatabaseCount('institution_names', 0);
    }
}
