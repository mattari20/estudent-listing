<?php

namespace Tests\Feature;

use Tests\TestCase;

class ImportDeploymentReadinessGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_before_operator_page_is_accessed(): void
    {
        $this->get('/institutes/operations/imports/1/deployment-readiness')->assertRedirect();
    }
}
