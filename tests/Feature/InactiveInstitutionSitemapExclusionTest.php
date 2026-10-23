<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveInstitutionSitemapExclusionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_institution_is_not_published_in_sitemap(): void
    {
        Institution::create(['name' => 'Inactive Sitemap Candidate', 'slug' => 'inactive-sitemap-candidate', 'status' => 'inactive']);
        Institution::create(['name' => 'Active Sitemap Candidate', 'slug' => 'active-sitemap-candidate', 'status' => 'active']);

        $this->get('/institutes/sitemap.xml')
            ->assertOk()
            ->assertSee('active-sitemap-candidate')
            ->assertDontSee('inactive-sitemap-candidate');
    }
}
