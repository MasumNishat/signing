<?php

use function Pest\Laravel\{get, post, put, delete};

/**
 * Route Registration Tests
 *
 * These tests verify that all API routes are properly registered
 * and accessible. This is part of the QA process to ensure
 * the platform's 299+ routes are configured correctly.
 */

describe('API v2.1 Route Registration', function () {

    test('all major route groups are registered', function () {
        $routeCollection = Route::getRoutes();
        $routes = collect($routeCollection)->map(fn($route) => $route->uri())->toArray();

        $expectedPrefixes = [
            'api/v2.1/accounts',
            'api/v2.1/users',
            'api/v2.1/envelopes',
            'api/v2.1/templates',
            'api/v2.1/billing',
            'api/v2.1/brands',
            'api/v2.1/bulk_send',
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
            $found = collect($routes)->contains(fn($route) => str_starts_with($route, $prefix));
            expect($found)->toBeTrue("Route group '$prefix' should be registered");
        }
    });

    test('authentication routes exist', function () {
        expect(Route::has('api.v2.1.auth.register'))->toBeTrue();
        expect(Route::has('api.v2.1.auth.login'))->toBeTrue();
        expect(Route::has('api.v2.1.auth.logout'))->toBeTrue();
        expect(Route::has('api.v2.1.auth.user'))->toBeTrue();
    });

    test('account management routes exist', function () {
        expect(Route::has('api.v2.1.accounts.store'))->toBeTrue();
        expect(Route::has('api.v2.1.accounts.show'))->toBeTrue();
        expect(Route::has('api.v2.1.accounts.destroy'))->toBeTrue();
    });

    test('user management routes exist', function () {
        expect(Route::has('api.v2.1.users.index'))->toBeTrue();
        expect(Route::has('api.v2.1.users.store'))->toBeTrue();
        expect(Route::has('api.v2.1.users.show'))->toBeTrue();
    });

    test('envelope routes exist', function () {
        expect(Route::has('api.v2.1.envelopes.index'))->toBeTrue();
        expect(Route::has('api.v2.1.envelopes.store'))->toBeTrue();
        expect(Route::has('api.v2.1.envelopes.show'))->toBeTrue();
        expect(Route::has('api.v2.1.envelopes.send'))->toBeTrue();
        expect(Route::has('api.v2.1.envelopes.void'))->toBeTrue();
    });

    test('connect/webhooks routes exist', function () {
        expect(Route::has('api.v2.1.connect.index'))->toBeTrue();
        expect(Route::has('api.v2.1.connect.store'))->toBeTrue();
        expect(Route::has('api.v2.1.connect.logs'))->toBeTrue();
        expect(Route::has('api.v2.1.connect.failures'))->toBeTrue();
    });

    test('billing routes exist', function () {
        expect(Route::has('api.v2.1.billing.plans.index'))->toBeTrue();
        expect(Route::has('api.v2.1.billing.invoices.index'))->toBeTrue();
        expect(Route::has('api.v2.1.billing.payments.index'))->toBeTrue();
    });

    test('total route count is correct', function () {
        $routeCollection = Route::getRoutes();
        $apiRoutes = collect($routeCollection)
            ->filter(fn($route) => str_starts_with($route->uri(), 'api/v2.1'))
            ->count();

        // We expect around 299 routes based on route:list output
        expect($apiRoutes)->toBeGreaterThan(290);
        expect($apiRoutes)->toBeLessThan(350);
    });
});
