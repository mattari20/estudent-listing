<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSearchWhitespaceQueryTest extends TestCase
{
    public function test_whitespace_query_is_handled_by_public_search(): void
    {
        $this->get('/institutes/search?q=%20%20%20')->assertOk();
    }
}
