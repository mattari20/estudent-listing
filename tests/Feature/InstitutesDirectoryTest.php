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
                'name' => sprintf('ZZZ Pagination Test Institution %02d', $number),
                'slug' => sprintf('zzz-pagination-test-institution-%02d', $number),
                'status' => 'active',
            ]);
        }

        Institution::query()->create([
            'name' => 'ZZZ Inactive Pagination Test Institution',
            'slug' => 'zzz-inactive-pagination-test-institution',
            'status' => 'candidate',
        ]);

        $this->get('/institutes')
            ->assertOk()
            ->assertSee('Showing')
            ->assertSee('directory-pagination', false)
            ->assertSee('directory-per-page', false)
            ->assertSee('ZZZ Pagination Test Institution 01')
            ->assertDontSee('ZZZ Pagination Test Institution 30')
            ->assertDontSee('ZZZ Inactive Pagination Test Institution');

        $this->get('/institutes?page=2')
            ->assertOk()
            ->assertSee('ZZZ Pagination Test Institution 30')
            ->assertDontSee('ZZZ Pagination Test Institution 01');
    }

    public function test_directory_page_size_is_limited_to_supported_values_and_query_is_preserved(): void
    {
        foreach (range(1, 30) as $number) {
            Institution::query()->create([
                'name' => sprintf('ZZZ Page Size Test Institution %02d', $number),
                'slug' => sprintf('zzz-page-size-test-institution-%02d', $number),
                'status' => 'active',
            ]);
        }

        $this->get('/institutes?per_page=24')
            ->assertOk()
            ->assertSee('Showing')
            ->assertSee('ZZZ Page Size Test Institution 01')
            ->assertDontSee('ZZZ Page Size Test Institution 30');

        $this->get('/institutes?per_page=999')
            ->assertOk()
            ->assertSee('12 per page')
            ->assertSee('ZZZ Page Size Test Institution 01')
            ->assertDontSee('ZZZ Page Size Test Institution 30');

        $this->get('/institutes?per_page=12&page=2')
            ->assertOk()
            ->assertSee('ZZZ Page Size Test Institution 30')
            ->assertDontSee('ZZZ Page Size Test Institution 01')
            ->assertSee('per_page=12');
    }
}
