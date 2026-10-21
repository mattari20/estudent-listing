<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InstituteBatchEvidenceCaptureTest extends TestCase
{
    public function test_batch_evidence_page_requires_operator_authentication(): void
    {
        $this->get('/institutes/operations/imports/1/evidence-capture')
            ->assertRedirect(route('institutes.login'));
    }

    public function test_evidence_capture_routes_are_registered_under_operator_middleware(): void
    {
        foreach ([
            'institutes.operations.imports.evidence-capture',
            'institutes.operations.imports.evidence-capture.store',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, $name.' route should be registered.');
            $middleware = $route->gatherMiddleware();

            $this->assertContains('auth', $middleware, $name.' should require authentication.');
            $this->assertContains('institute.operator', $middleware, $name.' should require operator access.');
        }
    }
}
