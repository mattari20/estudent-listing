<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSearchLongQueryTest extends TestCase
{
    public function test_long_search_query_does_not_crash_public_search(): void
    {
        $query = urlencode(str_repeat('student ', 200));
        $this->get('/institutes/search?q=' . $query)->assertOk();
    }
}
