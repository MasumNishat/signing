<?php

namespace Tests\Integration;

use Tests\Feature\ApiTestCase;

/**
 * API Routes Integration Test
 *
 * Verifies that all API routes are properly registered and accessible.
 * This test ensures route configuration is correct across all modules.
 */
class ApiRoutesTest extends ApiTestCase
{
    /**
     * Test that all major API route groups are registered
     *
     * @return void
     */
    public function test_all_route_groups_are_registered(): void
    {
        $routeCollection = \Route::getRoutes();
        $routes = [];

        foreach ($routeCollection as $route) {
            $routes[] = $route->uri();
        }

        // Core module routes should exist
        $expectedPrefixes = [
            'api/v2.1/accounts',
            'api/v2.1/users',
            'api/v2.1/envelopes',
            'api/v2.1/templates',
            'api/v2.1/billing',
            'api/v2.1/brands',
            'api/v2.1/bulk',
            'api/v2.1/connect',
            'api/v2.1/diagnostics',
            'api/v2.1/folders',
            'api/v2.1/groups',
            'api/v2.1/powerforms',
            'api/v2.1/signatures',
            'api/v2.1/signing_groups',
            'api/v2.1/tabs',
            'api/v2.1/workflows',
            'api/v2.1/workspaces',
            'api/v2.1/settings',
        ];

        foreach ($expectedPrefixes as $prefix) {
            $found = false;
            foreach ($routes as $route) {
                if (str_starts_with($route, $prefix)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Route group '$prefix' not found");
        }
    }

    /**
     * Test authentication routes are registered
     *
     * @return void
     */
    public function test_authentication_routes_exist(): void
    {
        $this->assertRouteExists('POST', 'api/v2.1/auth/register');
        $this->assertRouteExists('POST', 'api/v2.1/auth/login');
        $this->assertRouteExists('POST', 'api/v2.1/auth/logout');
        $this->assertRouteExists('GET', 'api/v2.1/auth/user');
    }

    /**
     * Test core CRUD routes exist for major modules
     *
     * @return void
     */
    public function test_core_module_routes_exist(): void
    {
        // Accounts
        $this->assertRouteExists('POST', 'api/v2.1/accounts');
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}');
        $this->assertRouteExists('DELETE', 'api/v2.1/accounts/{accountId}');

        // Users
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}/users');
        $this->assertRouteExists('POST', 'api/v2.1/accounts/{accountId}/users');
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}/users/{userId}');

        // Envelopes
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}/envelopes');
        $this->assertRouteExists('POST', 'api/v2.1/accounts/{accountId}/envelopes');
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}/envelopes/{envelopeId}');

        // Templates
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}/templates');
        $this->assertRouteExists('POST', 'api/v2.1/accounts/{accountId}/templates');

        // Connect/Webhooks
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}/connect');
        $this->assertRouteExists('POST', 'api/v2.1/accounts/{accountId}/connect');
    }

    /**
     * Test advanced feature routes exist
     *
     * @return void
     */
    public function test_advanced_feature_routes_exist(): void
    {
        // Bulk operations
        $this->assertRouteExists('POST', 'api/v2.1/accounts/{accountId}/bulk_send_batches');

        // PowerForms
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}/powerforms');

        // Signatures
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}/signatures');

        // Workflows
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow');

        // Diagnostics
        $this->assertRouteExists('GET', 'api/v2.1/accounts/{accountId}/diagnostics/request_logs');
    }

    /**
     * Helper method to assert a route exists
     *
     * @param string $method
     * @param string $uri
     * @return void
     */
    protected function assertRouteExists(string $method, string $uri): void
    {
        $routeCollection = \Route::getRoutes();
        $found = false;

        foreach ($routeCollection as $route) {
            if (in_array($method, $route->methods()) && $route->uri() === $uri) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "Route '$method $uri' not found");
    }
}
