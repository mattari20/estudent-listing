<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstituteLoginPageTest extends TestCase
{
    public function test_institute_login_page_is_accessible(): void
    {
        $this->get('/institutes/login')->assertOk();
    }
}
