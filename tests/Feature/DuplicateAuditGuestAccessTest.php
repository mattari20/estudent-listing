<?php

namespace Tests\Feature;

use Tests\TestCase;

class DuplicateAuditGuestAccessTest extends TestCase
{
    public function test_guest_cannot_access_duplicate_audit_data(): void
    {
        $this->get('/institutes/operations/duplicate-audit/data')->assertRedirect();
    }
}
