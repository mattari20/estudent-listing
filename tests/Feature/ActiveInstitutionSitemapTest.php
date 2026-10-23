<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveInstitutionSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_institution_is_included_in_public_sitemap(): void
    {
        Institution::create([
            'name' => 'Active Sitemap University',
            'slug' => 'active-sitemap-university',
            'status' => 'active',
        ]);

        $this->get('/institutes/sitemap.xml')
            ->assertOk()
            ->assertSee('active-sitemap-university');
    }
}
