<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicRobotsEndpointTest extends TestCase
{
    public function test_robots_endpoint_returns_a_successful_text_response(): void
    {
        $this->get('/institutes/robots.txt')->assertOk();
    }
}
