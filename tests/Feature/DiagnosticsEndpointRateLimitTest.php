<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiter;
use Tests\TestCase;

class DiagnosticsEndpointRateLimitTest extends TestCase
{
    public function test_diagnostics_endpoint_is_throttled_after_five_requests_per_minute(): void
    {
        config(['services.institutes_diagnostics.token' => '']);
        app(RateLimiter::class)->clear('127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/institutes/automation/diagnostics')->assertNotFound();
        }

        $this->getJson('/institutes/automation/diagnostics')->assertStatus(429);
    }
}
