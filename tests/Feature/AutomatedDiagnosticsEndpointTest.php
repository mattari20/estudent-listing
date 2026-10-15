<?php

namespace Tests\\Feature;

use Tests\\TestCase;

class AutomatedDiagnosticsEndpointTest extends TestCase
{
    public function test_endpoint_is_hidden_until_a_strong_secret_is_configured(): void
    {
        config(['services.institutes_diagnostics.token' => '']);

        $this->getJson('/institutes/automation/diagnostics')
            ->assertNotFound();
    }

    public function test_endpoint_rejects_requests_without_the_bearer_token(): void
    {
        config(['services.institutes_diagnostics.token' => str_repeat('a', 64)]);

        $this->getJson('/institutes/automation/diagnostics')
            ->assertForbidden();
    }

    public function test_endpoint_rejects_an_incorrect_bearer_token(): void
    {
        config(['services.institutes_diagnostics.token' => str_repeat('a', 64)]);

        $this->withHeader('Authorization', 'Bearer '.str_repeat('b', 64))
            ->getJson('/institutes/automation/diagnostics')
            ->assertForbidden();
    }
}
