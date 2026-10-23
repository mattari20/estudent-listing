<?php

namespace Tests\Feature;

use Tests\TestCase;

class ImportRowDiagnosticsGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_before_import_row_diagnostics_are_accessed(): void
    {
        $this->get('/institutes/operations/imports/1/row-diagnostics')->assertRedirect();
    }
}
