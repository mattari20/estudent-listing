<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicInstituteSearchReachabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_search_endpoint_returns_success_for_a_query(): void
    {
        $this->get('/institutes/search?q=engineering')->assertOk();
    }
}
