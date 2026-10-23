<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSitemapResponseTest extends TestCase
{
    public function test_public_sitemap_returns_xml_document(): void
    {
        $response = $this->get('/institutes/sitemap.xml');
        $response->assertOk();
        $this->assertStringContainsString('xml', strtolower($response->headers->get('Content-Type', '')));
        $response->assertSee('<urlset', false);
    }
}
