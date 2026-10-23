<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSearchUnicodeQueryTest extends TestCase
{
    public function test_unicode_query_is_handled_by_public_search(): void
    {
        $this->get('/institutes/search?q=' . urlencode('یونیورسٹی تعلیم'))->assertOk();
    }
}
