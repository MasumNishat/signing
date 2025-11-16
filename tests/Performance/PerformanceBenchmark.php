<?php

namespace Tests\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Performance Benchmark Test Suite
 *
 * Measures response times, database query counts, and memory usage
 * for critical API endpoints.
 *
 * Usage: php artisan test --testsuite=Performance
 */
class PerformanceBenchmark extends TestCase
{
    private array $benchmarks = [];

    protected function setUp(): void
    {
        parent::setUp();
        DB::enableQueryLog();
        $this->benchmarks = [];
    }

    /**
     * Benchmark a callable and record metrics
     */
    protected function benchmark(string $name, callable $callback): array
    {
        // Clear cache to ensure fair measurement
        Cache::flush();
        DB::flushQueryLog();

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $result = $callback();

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $metrics = [
            'name' => $name,
            'duration_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'query_count' => count(DB::getQueryLog()),
            'timestamp' => now()->toIso8601String(),
        ];

        $this->benchmarks[] = $metrics;

        return $metrics;
    }

    /**
     * Test: Authentication endpoint performance
     */
    public function test_authentication_performance(): void
    {
        $metrics = $this->benchmark('POST /api/v2.1/auth/login', function () {
            $response = $this->postJson('/api/v2.1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'SecurePass123!',
            ]);

            return $response;
        });

        // Performance assertions
        $this->assertLessThan(200, $metrics['duration_ms'], 'Login should complete in < 200ms');
        $this->assertLessThan(10, $metrics['query_count'], 'Login should use < 10 queries');
        $this->assertLessThan(5, $metrics['memory_mb'], 'Login should use < 5MB memory');

        Log::info('Authentication Performance', $metrics);
    }

    /**
     * Test: List envelopes endpoint performance
     */
    public function test_list_envelopes_performance(): void
    {
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\Account::factory()->create();

        $metrics = $this->benchmark('GET /api/v2.1/accounts/{id}/envelopes', function () use ($user, $account) {
            $response = $this->actingAs($user, 'api')
                ->getJson("/api/v2.1/accounts/{$account->account_id}/envelopes");

            return $response;
        });

        // Performance assertions
        $this->assertLessThan(300, $metrics['duration_ms'], 'List envelopes should complete in < 300ms');
        $this->assertLessThan(15, $metrics['query_count'], 'List envelopes should use < 15 queries');
        $this->assertLessThan(10, $metrics['memory_mb'], 'List envelopes should use < 10MB memory');

        Log::info('List Envelopes Performance', $metrics);
    }

    /**
     * Test: Create envelope endpoint performance
     */
    public function test_create_envelope_performance(): void
    {
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\Account::factory()->create();

        $envelopeData = [
            'email_subject' => 'Performance Test Document',
            'email_message' => 'Please sign this test document',
            'status' => 'draft',
            'documents' => [
                [
                    'document_id' => '1',
                    'name' => 'Test Document',
                    'file_extension' => 'pdf',
                ],
            ],
            'recipients' => [
                'signers' => [
                    [
                        'email' => 'signer@example.com',
                        'name' => 'Test Signer',
                        'routing_order' => 1,
                    ],
                ],
            ],
        ];

        $metrics = $this->benchmark('POST /api/v2.1/accounts/{id}/envelopes', function () use ($user, $account, $envelopeData) {
            $response = $this->actingAs($user, 'api')
                ->postJson("/api/v2.1/accounts/{$account->account_id}/envelopes", $envelopeData);

            return $response;
        });

        // Performance assertions
        $this->assertLessThan(500, $metrics['duration_ms'], 'Create envelope should complete in < 500ms');
        $this->assertLessThan(25, $metrics['query_count'], 'Create envelope should use < 25 queries');
        $this->assertLessThan(15, $metrics['memory_mb'], 'Create envelope should use < 15MB memory');

        Log::info('Create Envelope Performance', $metrics);
    }

    /**
     * Test: Database query performance (N+1 detection)
     */
    public function test_database_query_efficiency(): void
    {
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\Account::factory()->create();

        // Create 10 envelopes with recipients
        \App\Models\Envelope::factory()
            ->count(10)
            ->for($account)
            ->create();

        DB::flushQueryLog();

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v2.1/accounts/{$account->account_id}/envelopes");

        $queryCount = count(DB::getQueryLog());

        // Should use eager loading to prevent N+1
        $this->assertLessThan(5, $queryCount, 'Should use eager loading (< 5 queries for 10 envelopes)');

        Log::info('Query Efficiency Test', [
            'envelope_count' => 10,
            'query_count' => $queryCount,
        ]);
    }

    /**
     * Test: Bulk operation performance
     */
    public function test_bulk_operations_performance(): void
    {
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\Account::factory()->create();

        $bulkData = [
            'batch_name' => 'Performance Test Batch',
            'recipients' => array_fill(0, 100, [
                'email' => 'recipient@example.com',
                'name' => 'Test Recipient',
            ]),
        ];

        $metrics = $this->benchmark('POST /api/v2.1/accounts/{id}/bulk_send_batches', function () use ($user, $account, $bulkData) {
            $response = $this->actingAs($user, 'api')
                ->postJson("/api/v2.1/accounts/{$account->account_id}/bulk_send_batches", $bulkData);

            return $response;
        });

        // Bulk operations can take longer
        $this->assertLessThan(2000, $metrics['duration_ms'], 'Bulk operation should complete in < 2s');
        $this->assertLessThan(50, $metrics['memory_mb'], 'Bulk operation should use < 50MB memory');

        Log::info('Bulk Operation Performance', $metrics);
    }

    /**
     * Test: Cache effectiveness
     */
    public function test_cache_effectiveness(): void
    {
        $user = \App\Models\User::factory()->create();
        $account = \App\Models\Account::factory()->create();

        // First request (uncached)
        DB::flushQueryLog();
        $this->actingAs($user, 'api')
            ->getJson("/api/v2.1/accounts/{$account->account_id}");
        $uncachedQueries = count(DB::getQueryLog());

        // Second request (should be cached)
        DB::flushQueryLog();
        $this->actingAs($user, 'api')
            ->getJson("/api/v2.1/accounts/{$account->account_id}");
        $cachedQueries = count(DB::getQueryLog());

        // Cached requests should use fewer queries
        $this->assertLessThanOrEqual($uncachedQueries, $cachedQueries, 'Cached requests should use same or fewer queries');

        Log::info('Cache Effectiveness', [
            'uncached_queries' => $uncachedQueries,
            'cached_queries' => $cachedQueries,
        ]);
    }

    /**
     * Generate performance report
     */
    protected function tearDown(): void
    {
        if (!empty($this->benchmarks)) {
            $report = [
                'summary' => [
                    'total_tests' => count($this->benchmarks),
                    'avg_duration_ms' => round(array_sum(array_column($this->benchmarks, 'duration_ms')) / count($this->benchmarks), 2),
                    'avg_memory_mb' => round(array_sum(array_column($this->benchmarks, 'memory_mb')) / count($this->benchmarks), 2),
                    'total_queries' => array_sum(array_column($this->benchmarks, 'query_count')),
                ],
                'benchmarks' => $this->benchmarks,
            ];

            Log::info('Performance Benchmark Report', $report);

            // Write to file for CI/CD
            file_put_contents(
                storage_path('logs/performance-report.json'),
                json_encode($report, JSON_PRETTY_PRINT)
            );
        }

        parent::tearDown();
    }
}
