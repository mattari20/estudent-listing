<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSearchEmptyQueryTest extends TestCase
{
    public function test_public_search_handles_empty_query_without_server_error(): void
    {
        $this->get('/institutes/search')->assertOk();
    }
}
