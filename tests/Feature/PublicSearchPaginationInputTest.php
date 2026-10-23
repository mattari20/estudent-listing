<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSearchPaginationInputTest extends TestCase
{
    public function test_public_search_handles_second_page(): void
    {
        $this->get('/institutes/search?page=2')->assertOk();
    }
}
