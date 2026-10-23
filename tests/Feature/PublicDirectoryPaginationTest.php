<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDirectoryPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveInstitutions(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Institution::query()->create([
                'name' => sprintf('Directory Pagination University %02d', $i),
                'slug' => sprintf('directory-pagination-university-%02d', $i),
                'status' => 'active',
            ]);
        }
    }

    public function test_directory_honors_supported_page_sizes(): void
    {
        $this->createActiveInstitutions(25);

        $response = $this->get('/institutes/?per_page=9')->assertOk();

        $this->assertSame(9, substr_count($response->getContent(), 'class="institute-card"'));
        $response->assertSee('per_page=9', false);
    }

    public function test_directory_rejects_unsupported_page_sizes_and_uses_default(): void
    {
        $this->createActiveInstitutions(25);

        $response = $this->get('/institutes/?per_page=7')->assertOk();

        $this->assertSame(24, substr_count($response->getContent(), 'class="institute-card"'));
    }


    public function test_second_page_contains_only_remaining_active_institutions(): void
    {
        $this->createActiveInstitutions(25);

        $response = $this->get('/institutes/?per_page=24&page=2')->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'class="institute-card"'));
        $response->assertSee('Directory Pagination University 25');
        $response->assertDontSee('Directory Pagination University 01');
    }
}
