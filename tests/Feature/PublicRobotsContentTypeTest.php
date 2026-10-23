<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicRobotsContentTypeTest extends TestCase
{
    public function test_public_robots_endpoint_uses_plain_text_content_type(): void
    {
        $response = $this->get('/institutes/robots.txt');
        $response->assertOk();
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type', ''));
    }
}
