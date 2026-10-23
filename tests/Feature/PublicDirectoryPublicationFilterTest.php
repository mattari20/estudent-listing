<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDirectoryPublicationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_institutions_are_listed_publicly(): void
    {
        Institution::create(['name' => 'Published University', 'slug' => 'published-university', 'status' => 'active']);
        Institution::create(['name' => 'Candidate University', 'slug' => 'candidate-university', 'status' => 'candidate']);
        Institution::create(['name' => 'Inactive University', 'slug' => 'inactive-university', 'status' => 'inactive']);

        $this->get('/institutes/')
            ->assertOk()
            ->assertSee('Published University')
            ->assertDontSee('Candidate University')
            ->assertDontSee('Inactive University');
    }
}
