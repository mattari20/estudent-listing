<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveInstitutionPublicDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_directory_does_not_render_inactive_institutions(): void
    {
        Institution::create(['name' => 'Hidden Inactive Directory Candidate', 'slug' => 'hidden-inactive-directory-candidate', 'status' => 'inactive']);

        $this->get('/institutes')
            ->assertOk()
            ->assertDontSee('Hidden Inactive Directory Candidate');
    }
}
