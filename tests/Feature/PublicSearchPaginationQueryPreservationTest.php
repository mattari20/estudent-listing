<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSearchPaginationQueryPreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_pagination_links_preserve_active_query(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Institution::create([
                'name' => sprintf('PaginationSearch University %02d', $i),
                'slug' => sprintf('paginationsearch-university-%02d', $i),
                'status' => 'active',
            ]);
        }

        $response = $this->get('/institutes/search?q=PaginationSearch')->assertOk();
        $response->assertSee('page=2', false);
        $response->assertSee('q=PaginationSearch', false);
    }
}
