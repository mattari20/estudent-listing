<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicRobotsResponseTest extends TestCase
{
    public function test_public_robots_endpoint_returns_successful_response(): void
    {
        $this->get('/institutes/robots.txt')->assertOk();
    }
}
