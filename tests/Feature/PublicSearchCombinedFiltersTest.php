<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSearchCombinedFiltersTest extends TestCase
{
    public function test_combined_public_search_filters_return_success(): void
    {
        $this->get('/institutes/search?program_level=undergraduate&delivery_mode=online&page=1')->assertOk();
    }
}
