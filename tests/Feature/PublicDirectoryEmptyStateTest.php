<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDirectoryEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_explains_when_no_active_institutions_are_available(): void
    {
        Institution::query()->create([
            'name' => 'Candidate Institution',
            'slug' => 'candidate-institution',
            'status' => 'candidate',
        ]);

        Institution::query()->create([
            'name' => 'Inactive Institution',
            'slug' => 'inactive-institution',
            'status' => 'inactive',
        ]);

        $this->get('/institutes/')
            ->assertOk()
            ->assertSee('No institutions to display')
            ->assertSee('No institutions available')
            ->assertSee('Institution information is not currently available.')
            ->assertDontSee('Candidate Institution')
            ->assertDontSee('Inactive Institution')
            ->assertDontSee('directory-pagination');
    }
}
