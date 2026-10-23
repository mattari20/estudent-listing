<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitemapXmlContentTypeTest extends TestCase
{
    public function test_public_sitemap_uses_an_xml_content_type(): void
    {
        $this->get('/institutes/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }
}
