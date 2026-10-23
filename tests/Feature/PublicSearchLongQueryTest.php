<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSearchLongQueryTest extends TestCase
{
    public function test_long_search_query_does_not_crash_public_search(): void
    {
        $this->get('/institutes/search?q=' . str_repeat('student ', 200))->assertOk();
    }
}
