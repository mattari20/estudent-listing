<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSearchTrimQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_trims_surrounding_whitespace_from_query(): void
    {
        Institution::create(['name' => 'Whitespace Search University', 'slug' => 'whitespace-search-university', 'status' => 'active']);

        $this->get('/institutes/search?q=%20Whitespace%20Search%20')
            ->assertOk()
            ->assertSee('Whitespace Search University');
    }
}
