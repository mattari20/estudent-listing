<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSearchStableOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_search_results_are_ordered_by_name(): void
    {
        Institution::create(['name' => 'Order Test Zeta University', 'slug' => 'order-test-zeta', 'status' => 'active']);
        Institution::create(['name' => 'Order Test Alpha University', 'slug' => 'order-test-alpha', 'status' => 'active']);
        Institution::create(['name' => 'Order Test Beta University', 'slug' => 'order-test-beta', 'status' => 'active']);

        $content = $this->get('/institutes/search?q=Order%20Test')->assertOk()->getContent();
        $alpha = strpos($content, 'Order Test Alpha University');
        $beta = strpos($content, 'Order Test Beta University');
        $zeta = strpos($content, 'Order Test Zeta University');

        $this->assertNotFalse($alpha);
        $this->assertNotFalse($beta);
        $this->assertNotFalse($zeta);
        $this->assertLessThan($beta, $alpha);
        $this->assertLessThan($zeta, $beta);
    }
}
