<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvenanceFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_provenance_chain_tables_migrate(): void
    {
        $this->assertDatabaseCount('sources', 0);
        $this->assertDatabaseCount('evidence', 0);
        $this->assertDatabaseCount('assertions', 0);
        $this->assertDatabaseCount('change_events', 0);
    }
}
