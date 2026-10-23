<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSearchInvalidPageTest extends TestCase
{
    public function test_invalid_page_parameter_does_not_produce_server_error(): void
    {
        $response = $this->get('/institutes/search?page=not-a-number');
        $this->assertNotSame(500, $response->getStatusCode());
    }
}
