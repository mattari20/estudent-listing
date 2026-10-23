<?php

namespace Tests\Feature;

use Tests\TestCase;

class ImportRowDetailsGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_before_operator_page_is_accessed(): void
    {
        $this->get('/institutes/operations/rows/1')->assertRedirect();
    }
}
