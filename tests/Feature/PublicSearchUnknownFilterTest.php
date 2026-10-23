<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSearchUnknownFilterTest extends TestCase
{
    public function test_unknown_filter_value_does_not_crash_public_search(): void
    {
        $this->get('/institutes/search?program_level=unknown-level')->assertOk();
    }
}
