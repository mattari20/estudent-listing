<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSitemapLastModifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_contains_last_modified_timestamp_for_active_institution(): void
    {
        Institution::create(['name' => 'Sitemap Timestamp University', 'slug' => 'sitemap-timestamp-university', 'status' => 'active']);

        $this->get('/institutes/sitemap.xml')
            ->assertOk()
            ->assertSee('sitemap-timestamp-university')
            ->assertSee('<lastmod>', false);
    }
}
