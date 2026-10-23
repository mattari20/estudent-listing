<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSitemapCacheHeadersTest extends TestCase
{
    public function test_sitemap_has_xml_content_type_and_public_cache_policy(): void
    {
        $response = $this->get('/institutes/sitemap.xml')->assertOk();

        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=3600', (string) $response->headers->get('Cache-Control'));
    }
}
