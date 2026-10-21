<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutesDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_institutes_route_exists(): void
    {
        $this->get('/institutes')->assertOk();
    }

    public function test_directory_paginates_active_institutions_and_shows_navigation(): void
    {
        foreach (range(1, 30) as $number) {
            Institution::query()->create([
                'name' => sprintf('AAA Pagination Test Institution %02d', $number),
                'slug' => sprintf('aaa-pagination-test-institution-%02d', $number),
                'status' => 'active',
            ]);
        }

        Institution::query()->create([
            'name' => 'AAA Inactive Pagination Test Institution',
            'slug' => 'aaa-inactive-pagination-test-institution',
            'status' => 'candidate',
        ]);

        $this->get('/institutes')
            ->assertOk()
            ->assertSee('Showing')
            ->assertSee('directory-pagination', false)
            ->assertSee('directory-per-page', false)
            ->assertSee('AAA Pagination Test Institution 01')
            ->assertDontSee('AAA Pagination Test Institution 30')
            ->assertDontSee('AAA Inactive Pagination Test Institution');

        $this->get('/institutes?page=2')
            ->assertOk()
            ->assertSee('AAA Pagination Test Institution 13')
            ->assertDontSee('AAA Pagination Test Institution 01');
    }

    public function test_directory_page_size_is_limited_to_supported_values_and_query_is_preserved(): void
    {
        foreach (range(1, 30) as $number) {
            Institution::query()->create([
                'name' => sprintf('AAA Page Size Test Institution %02d', $number),
                'slug' => sprintf('aaa-page-size-test-institution-%02d', $number),
                'status' => 'active',
            ]);
        }

        $this->get('/institutes?per_page=24')
            ->assertOk()
            ->assertSee('Showing')
            ->assertSee('AAA Page Size Test Institution 01')
            ->assertDontSee('AAA Page Size Test Institution 30');

        $this->get('/institutes?per_page=999')
            ->assertOk()
            ->assertSee('12 per page')
            ->assertSee('AAA Page Size Test Institution 01')
            ->assertDontSee('AAA Page Size Test Institution 30');

        $this->get('/institutes?per_page=12&page=2')
            ->assertOk()
            ->assertSee('AAA Page Size Test Institution 30')
            ->assertDontSee('AAA Page Size Test Institution 01')
            ->assertSee('per_page=12');
    }
}
