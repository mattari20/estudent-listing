<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class InstitutesDirectoryTest extends TestCase
{
    use RefreshDatabase;
    public function test_institutes_route_exists(): void
    {
        $this->get('/institutes')->assertOk();
    }
}
