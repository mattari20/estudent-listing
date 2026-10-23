<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteSeoFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_institution_profile_exposes_governed_schema_and_breadcrumb(): void
    {
        $institution = Institution::create([
            'name' => 'SEO University',
            'official_name' => 'SEO University of Pakistan',
            'short_name' => 'SEO U',
            'slug' => 'seo-university',
            'institution_type' => 'university',
            'status' => 'active',
            'description' => 'A test institution description.',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
            'address' => 'Main Road',
            'postal_code' => '54000',
            'latitude' => 31.5204,
            'longitude' => 74.3587,
        ]);

        $this->get('/institutes/seo-university')
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('EducationalOrganization', false)
            ->assertSee('BreadcrumbList', false)
            ->assertSee('SEO University of Pakistan', false)
            ->assertSee('Main Road', false)
            ->assertSee('<link rel="canonical"', false);
    }

    public function test_sitemap_contains_only_active_institution_urls(): void
    {
        Institution::create([
            'name' => 'Published University',
            'slug' => 'published-university',
            'status' => 'active',
        ]);

        Institution::create([
            'name' => 'Archived University',
            'slug' => 'archived-university',
            'status' => 'inactive',
        ]);

        $this->get('/institutes/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/institutes/published-university', false)
            ->assertDontSee('/institutes/archived-university', false);
    }

    public function test_institutes_robots_file_controls_search_and_private_routes(): void
    {
        $this->get('/institutes/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Allow: /institutes/')
            ->assertSee('Disallow: /institutes/search')
            ->assertSee('Disallow: /institutes/operations/')
            ->assertSee('/institutes/sitemap.xml');
    }

    public function test_filtered_search_is_noindex_and_base_search_is_canonical(): void
    {
        $this->get('/institutes/search')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="http://localhost/institutes/search">', false)
            ->assertDontSee('<meta name="robots" content="noindex,follow">', false);

        $this->get('/institutes/search?q=computer')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false)
            ->assertDontSee('<link rel="canonical" href="">', false);
    }

    public function test_sitemap_excludes_candidate_institution_urls(): void
    {
        Institution::create(['name' => 'Candidate SEO University', 'slug' => 'candidate-seo-university', 'status' => 'candidate']);
        $this->get('/institutes/sitemap.xml')->assertOk()->assertDontSee('/institutes/candidate-seo-university', false);
    }

    public function test_sitemap_never_includes_candidate_institution_slug(): void
 {
  Institution::create(['name' => 'SEO Pending University', 'slug' => 'seo-pending-university', 'status' => 'candidate']);
  $this->get('/institutes/sitemap.xml')->assertOk()->assertDontSee('/institutes/seo-pending-university', false);
 }


    public function test_sitemap_excludes_draft_institution_urls(): void
    {
        Institution::create([
            'name' => 'Draft SEO University',
            'slug' => 'draft-seo-university',
            'status' => 'draft',
        ]);

        $this->get('/institutes/sitemap.xml')
            ->assertOk()
            ->assertDontSee('/institutes/draft-seo-university', false);
    }

}
