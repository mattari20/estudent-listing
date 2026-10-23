<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDirectoryLargePageSizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_supports_page_size_of_48(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            Institution::create([
                'name' => sprintf('Large Page University %02d', $i),
                'slug' => sprintf('large-page-university-%02d', $i),
                'status' => 'active',
            ]);
        }

        $response = $this->get('/institutes/?per_page=48')->assertOk();
        $this->assertSame(48, substr_count($response->getContent(), 'class="institute-card"'));
        $response->assertSee('per_page=48', false);
    }
}
