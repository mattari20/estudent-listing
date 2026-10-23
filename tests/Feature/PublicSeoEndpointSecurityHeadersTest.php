<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSeoEndpointSecurityHeadersTest extends TestCase
{
    public function test_sitemap_includes_nosniff_header(): void
    {
        $this->get('/institutes/sitemap.xml')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_robots_response_includes_nosniff_header(): void
    {
        $this->get('/institutes/robots.txt')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }
}
