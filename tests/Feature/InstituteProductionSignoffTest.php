<?php

namespace Tests\Feature;

use App\Services\InstituteProductionSignoffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteProductionSignoffTest extends TestCase
{
    use RefreshDatabase;

    public function test_signoff_report_is_read_only_and_exposes_blocking_findings(): void
    {
        $summary = app(InstituteProductionSignoffService::class)->summarize();

        $this->assertFalse($summary['mutation_performed']);
        $this->assertIsArray($summary['checks']);
        $this->assertIsArray($summary['blocking_findings']);
        $this->assertSame(count($summary['blocking_findings']), $summary['blocking_total']);
        $this->assertSame(
            $summary['blocking_total'] === 0,
            $summary['production_signoff_ready']
        );
    }

    public function test_signoff_endpoint_is_operator_protected(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => $route->getName() === 'institutes.operations.production-signoff');

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('institute.operator', $route->gatherMiddleware());
    }
}
