<?php

namespace App\Services;

use Illuminate\Routing\Router;

class InstituteProductionSignoffService
{
    public function summarize(?Router $router = null): array
    {
        $router ??= app('router');

        $checks = [
            'debug_default_disabled' => (bool) config('app.debug') === false,
            'session_http_only' => config('session.http_only') === true,
            'session_same_site_lax' => config('session.same_site') === 'lax',
            'production_session_secure_default' => config('session.secure') === (config('app.env') === 'production'),
            'operator_routes_protected' => $this->operatorRoutesProtected($router),
            'public_routes_present' => $this->routesPresent($router, [
                'institutes.index',
                'institutes.search',
                'institutes.sitemap',
                'institutes.robots',
                'institutes.show',
            ]),
            'api_routes_present' => $this->apiRoutesPresent($router),
            'validation_workflow_present' => is_file(base_path('.github/workflows/application-validation.yml')),
            'production_runbook_present' => is_file(base_path('docs/09-operations/DEV-316-PRODUCTION-VALIDATION-RUNBOOK.md')),
            'security_hardening_documented' => is_file(base_path('docs/09-operations/DEV-318-PRODUCTION-SECURITY-HARDENING.md')),
            'composer_lock_present' => is_file(base_path('composer.lock')),
        ];

        $blocking = array_keys(array_filter($checks, fn (bool $passed): bool => !$passed));

        return [
            'scope' => 'read_only_final_production_signoff',
            'checks' => $checks,
            'blocking_findings' => $blocking,
            'blocking_total' => count($blocking),
            'production_signoff_ready' => $blocking === [],
            'mutation_performed' => false,
        ];
    }

    private function operatorRoutesProtected(Router $router): bool
    {
        $routes = collect($router->getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_starts_with($route->getName() ?? '', 'institutes.operations.'));

        if ($routes->isEmpty()) {
            return false;
        }

        return $routes->every(function ($route): bool {
            $middleware = $route->gatherMiddleware();

            return in_array('auth', $middleware, true)
                && in_array('institute.operator', $middleware, true);
        });
    }

    private function routesPresent(Router $router, array $names): bool
    {
        foreach ($names as $name) {
            if (!$router->getRoutes()->getByName($name)) {
                return false;
            }
        }

        return true;
    }

    private function apiRoutesPresent(Router $router): bool
    {
        $uris = collect($router->getRoutes()->getRoutes())
            ->map(fn ($route): string => $route->uri())
            ->filter(fn (string $uri): bool => str_starts_with($uri, 'api/v1/institutions'));

        return $uris->contains('api/v1/institutions')
            && $uris->contains('api/v1/institutions/nearby')
            && $uris->contains('api/v1/institutions/{institution}');
    }
}
