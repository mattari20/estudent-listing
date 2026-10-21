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
        foreach (range(1, 13) as $number) {
            Institution::query()->create([
                'name' => sprintf('Test Institution %02d', $number),
                'slug' => sprintf('test-institution-%02d', $number),
                'status' => 'active',
            ]);
        }

        Institution::query()->create([
            'name' => 'Inactive Test Institution',
            'slug' => 'inactive-test-institution',
            'status' => 'candidate',
        ]);

        $this->get('/institutes')
            ->assertOk()
            ->assertSee('Showing')
            ->assertSee('1–12')
            ->assertSee('of')
            ->assertSee('13')
            ->assertSee('Test Institution 01')
            ->assertDontSee('/institutes/test-institution-13')
            ->assertDontSee('Inactive Test Institution')
            ->assertSee('directory-pagination', false)
            ->assertSee('directory-per-page', false);

        $this->get('/institutes?page=2')
            ->assertOk()
            ->assertSee('Test Institution 13')
            ->assertDontSee('Test Institution 01')
            ->assertSee('Page')
            ->assertSee('2');
    }

    public function test_directory_page_size_is_limited_to_supported_values_and_query_is_preserved(): void
    {
        foreach (range(1, 25) as $number) {
            Institution::query()->create([
                'name' => sprintf('Page Size Institution %02d', $number),
                'slug' => sprintf('page-size-institution-%02d', $number),
                'status' => 'active',
            ]);
        }

        $this->get('/institutes?per_page=24')
            ->assertOk()
            ->assertSee('Showing')
            ->assertSee('1–24')
            ->assertSee('of')
            ->assertSee('25')
            ->assertDontSee('/institutes/page-size-institution-25');

        $this->get('/institutes?per_page=999')
            ->assertOk()
            ->assertSee('1–12')
            ->assertSee('12 per page');

        $this->get('/institutes?per_page=12&page=2')
            ->assertOk()
            ->assertSee('Page')
            ->assertSee('2')
            ->assertSee('Page Size Institution 25');
    }
}
