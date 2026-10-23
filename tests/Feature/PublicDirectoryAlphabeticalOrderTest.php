<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDirectoryAlphabeticalOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_orders_active_institutions_by_name(): void
    {
        Institution::create(['name' => 'Zulu University', 'slug' => 'zulu-university', 'status' => 'active']);
        Institution::create(['name' => 'Alpha University', 'slug' => 'alpha-university', 'status' => 'active']);
        Institution::create(['name' => 'Middle University', 'slug' => 'middle-university', 'status' => 'active']);

        $content = $this->get('/institutes/')->assertOk()->getContent();
        $alpha = strpos($content, 'Alpha University');
        $middle = strpos($content, 'Middle University');
        $zulu = strpos($content, 'Zulu University');

        $this->assertNotFalse($alpha);
        $this->assertNotFalse($middle);
        $this->assertNotFalse($zulu);
        $this->assertLessThan($middle, $alpha);
        $this->assertLessThan($zulu, $middle);
    }
}
