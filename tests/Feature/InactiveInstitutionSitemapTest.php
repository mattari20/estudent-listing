<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveInstitutionSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_institution_is_excluded_from_public_sitemap(): void
    {
        Institution::create([
            'name' => 'Inactive Sitemap University',
            'slug' => 'inactive-sitemap-university',
            'status' => 'inactive',
        ]);

        $this->get('/institutes/sitemap.xml')
            ->assertOk()
            ->assertDontSee('inactive-sitemap-university');
    }
}
