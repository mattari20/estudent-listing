<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_uses_public_ui_shell_and_institute_card(): void
    {
        Institution::create([
            'name' => 'Example University',
            'slug' => 'example-university',
            'institution_type' => 'University',
            'status' => 'active',
        ]);

        $this->get('/institutes/')
            ->assertOk()
            ->assertSee('Find educational institutes in Pakistan')
            ->assertSee('Example University')
            ->assertSee('View institute');
    }

    public function test_search_uses_filter_panel_and_preserves_discovery_boundary(): void
    {
        $this->get('/institutes/search')
            ->assertOk()
            ->assertSee('Institute type')
            ->assertSee('Province / territory')
            ->assertSee('Program level')
            ->assertSee('Search results are discovery results, not quality rankings.');
    }

    public function test_profile_preserves_canonical_url_and_section_navigation(): void
    {
        $institution = Institution::create([
            'name' => 'Example University',
            'slug' => 'example-university',
            'status' => 'active',
        ]);

        $this->get('/institutes/example-university')
            ->assertOk()
            ->assertSee('Example University')
            ->assertSee('href="#campuses"', false)
            ->assertSee('href="#programs"', false)
            ->assertSee('href="#recognition"', false)
            ->assertSee(route('institutes.show', $institution), false);
    }
}
