<?php
namespace Tests\Feature;
use Tests\TestCase;
class InstitutesDirectoryTest extends TestCase
{
    public function test_institutes_route_exists(): void
    {
        $this->get('/institutes')->assertOk();
    }
}
