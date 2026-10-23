<?php

namespace Tests\\Feature;

use Illuminate\\Cache\\RateLimiter;
use Tests\\TestCase;

class DiagnosticsEndpointRateLimitTest extends TestCase
{
    public function test_diagnostics_endpoint_is_throttled_after_five_requests_per_minute(): void
    {
        config(['services.institutes_diagnostics.token' => '']);

        // Isolate this test from rate-limit state left by other feature tests.
        $clientIp = '192.0.2.123';
        app(RateLimiter::class)->clear($clientIp);

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
                ->getJson('/institutes/automation/diagnostics')
                ->assertNotFound();
        }

        $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
            ->getJson('/institutes/automation/diagnostics')
            ->assertStatus(429);
    }
}
