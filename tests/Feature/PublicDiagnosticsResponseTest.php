<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicDiagnosticsResponseTest extends TestCase
{
    public function test_diagnostics_endpoint_returns_controlled_response(): void
    {
        $response = $this->getJson('/institutes/automation/diagnostics');
        $this->assertNotSame(500, $response->getStatusCode());
        $this->assertContains($response->getStatusCode(), [200, 401, 403, 404, 429]);
    }
}
