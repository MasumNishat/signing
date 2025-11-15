<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class ValidateOpenApiCommand extends Command
{
    protected $signature = 'test:openapi:validate {--output=terminal : Output format (terminal, json, html)}';
    protected $description = 'Validate API implementation against OpenAPI specification';

    private array $issues = [];
    private array $stats = [
        'total_spec_endpoints' => 0,
        'total_implemented_endpoints' => 0,
        'matched_endpoints' => 0,
        'missing_endpoints' => 0,
        'extra_endpoints' => 0,
        'schema_mismatches' => 0,
    ];

    public function handle()
    {
        $this->info('🔍 Validating API against OpenAPI Specification...');
        $this->newLine();

        // Load OpenAPI specification
        $specPath = base_path('docs/openapi.json');
        if (!file_exists($specPath)) {
            $this->error('OpenAPI specification not found at: ' . $specPath);
            return 1;
        }

        $spec = json_decode(file_get_contents($specPath), true);
        if (!$spec) {
            $this->error('Invalid OpenAPI specification JSON');
            return 1;
        }

        // Get Laravel routes
        $routes = $this->getLaravelRoutes();

        // Extract OpenAPI paths
        $specPaths = $this->extractSpecPaths($spec);

        $this->info('📊 Statistics:');
        $this->line('  OpenAPI Endpoints: ' . count($specPaths));
        $this->line('  Implemented Routes: ' . count($routes));
        $this->newLine();

        // Compare endpoints
        $this->info('🔎 Comparing Endpoints...');
        $comparison = $this->compareEndpoints($specPaths, $routes);

        // Display results
        $this->displayResults($comparison);

        // Generate report
        if ($this->option('output') === 'json') {
            $this->generateJsonReport($comparison);
        } elseif ($this->option('output') === 'html') {
            $this->generateHtmlReport($comparison);
        }

        // Return exit code
        return $this->stats['missing_endpoints'] > 0 || $this->stats['schema_mismatches'] > 0 ? 1 : 0;
    }

    private function getLaravelRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            // Skip non-API routes
            if (!str_starts_with($uri, 'api/v2.1/')) {
                continue;
            }

            // Normalize path to match OpenAPI format
            // Convert: api/v2.1/accounts/{accountId} => /v2.1/accounts/{accountId}
            $path = '/' . str_replace('api/', '', $uri);

            $method = strtolower($route->methods()[0]);

            if (!isset($routes[$path])) {
                $routes[$path] = [];
            }

            $routes[$path][$method] = [
                'controller' => $route->getActionName(),
                'middleware' => $route->middleware(),
                'name' => $route->getName(),
            ];
        }

        return $routes;
    }

    private function extractSpecPaths(array $spec): array
    {
        $paths = [];

        if (!isset($spec['paths'])) {
            return $paths;
        }

        foreach ($spec['paths'] as $path => $methods) {
            // Normalize path
            $normalizedPath = preg_replace('/\{([^}]+)\}/', '{$1}', $path);

            $paths[$normalizedPath] = [];

            foreach ($methods as $method => $details) {
                if (in_array($method, ['get', 'post', 'put', 'delete', 'patch'])) {
                    $paths[$normalizedPath][$method] = $details;
                }
            }
        }

        return $paths;
    }

    private function compareEndpoints(array $specPaths, array $routes): array
    {
        $this->stats['total_spec_endpoints'] = count($specPaths);
        $this->stats['total_implemented_endpoints'] = count($routes);

        $missing = [];
        $extra = [];
        $matched = [];

        // Find missing endpoints (in spec but not implemented)
        foreach ($specPaths as $path => $methods) {
            foreach ($methods as $method => $details) {
                $key = strtoupper($method) . ' ' . $path;

                if (!isset($routes[$path][$method])) {
                    $missing[] = [
                        'path' => $path,
                        'method' => strtoupper($method),
                        'key' => $key,
                        'summary' => $details['summary'] ?? 'No summary',
                    ];
                    $this->stats['missing_endpoints']++;
                } else {
                    $matched[] = [
                        'path' => $path,
                        'method' => strtoupper($method),
                        'key' => $key,
                        'controller' => $routes[$path][$method]['controller'],
                    ];
                    $this->stats['matched_endpoints']++;
                }
            }
        }

        // Find extra endpoints (implemented but not in spec)
        foreach ($routes as $path => $methods) {
            foreach ($methods as $method => $details) {
                $key = strtoupper($method) . ' ' . $path;

                if (!isset($specPaths[$path][strtolower($method)])) {
                    $extra[] = [
                        'path' => $path,
                        'method' => strtoupper($method),
                        'key' => $key,
                        'controller' => $details['controller'],
                    ];
                    $this->stats['extra_endpoints']++;
                }
            }
        }

        return [
            'missing' => $missing,
            'extra' => $extra,
            'matched' => $matched,
            'stats' => $this->stats,
        ];
    }

    private function displayResults(array $comparison): void
    {
        // Display statistics
        $this->newLine();
        $this->info('📈 Validation Results:');
        $this->line('  ✅ Matched Endpoints: ' . $this->stats['matched_endpoints']);
        $this->line('  ❌ Missing Endpoints: ' . $this->stats['missing_endpoints']);
        $this->line('  ⚠️  Extra Endpoints: ' . $this->stats['extra_endpoints']);
        $this->newLine();

        // Calculate coverage percentage
        $coverage = $this->stats['total_spec_endpoints'] > 0
            ? round(($this->stats['matched_endpoints'] / $this->stats['total_spec_endpoints']) * 100, 2)
            : 0;

        if ($coverage >= 95) {
            $this->info("🎉 API Coverage: {$coverage}% - Excellent!");
        } elseif ($coverage >= 80) {
            $this->warn("⚠️  API Coverage: {$coverage}% - Good, but needs improvement");
        } else {
            $this->error("❌ API Coverage: {$coverage}% - Critical issues found");
        }

        // Display missing endpoints
        if (count($comparison['missing']) > 0) {
            $this->newLine();
            $this->error('❌ Missing Endpoints (' . count($comparison['missing']) . '):');
            $this->table(
                ['Method', 'Path', 'Summary'],
                array_map(fn($item) => [
                    $item['method'],
                    $item['path'],
                    $item['summary'],
                ], array_slice($comparison['missing'], 0, 20))
            );

            if (count($comparison['missing']) > 20) {
                $this->line('  ... and ' . (count($comparison['missing']) - 20) . ' more');
            }
        }

        // Display extra endpoints
        if (count($comparison['extra']) > 0) {
            $this->newLine();
            $this->warn('⚠️  Extra Endpoints (not in spec) (' . count($comparison['extra']) . '):');
            $this->table(
                ['Method', 'Path', 'Controller'],
                array_map(fn($item) => [
                    $item['method'],
                    $item['path'],
                    $item['controller'],
                ], array_slice($comparison['extra'], 0, 10))
            );

            if (count($comparison['extra']) > 10) {
                $this->line('  ... and ' . (count($comparison['extra']) - 10) . ' more');
            }
        }
    }

    private function generateJsonReport(array $comparison): void
    {
        $reportPath = storage_path('app/openapi-validation-report.json');
        file_put_contents($reportPath, json_encode($comparison, JSON_PRETTY_PRINT));
        $this->info('📄 JSON report generated: ' . $reportPath);
    }

    private function generateHtmlReport(array $comparison): void
    {
        $coverage = $this->stats['total_spec_endpoints'] > 0
            ? round(($this->stats['matched_endpoints'] / $this->stats['total_spec_endpoints']) * 100, 2)
            : 0;

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>OpenAPI Validation Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { background: #4CAF50; color: white; padding: 20px; border-radius: 8px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0; }
        .stat-card { background: #f5f5f5; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-value { font-size: 32px; font-weight: bold; }
        .coverage { font-size: 48px; font-weight: bold; color: {$this->getCoverageColor($coverage)}; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #4CAF50; color: white; }
        .missing { background-color: #ffebee; }
        .extra { background-color: #fff3e0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>OpenAPI Validation Report</h1>
        <p>Generated: {date('Y-m-d H:i:s')}</p>
    </div>

    <div class="stat-card">
        <div class="coverage">{$coverage}%</div>
        <div>API Coverage</div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-value">{$this->stats['matched_endpoints']}</div>
            <div>Matched Endpoints</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{$this->stats['missing_endpoints']}</div>
            <div>Missing Endpoints</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{$this->stats['extra_endpoints']}</div>
            <div>Extra Endpoints</div>
        </div>
    </div>

    <h2>Missing Endpoints</h2>
    <table>
        <tr><th>Method</th><th>Path</th><th>Summary</th></tr>
HTML;

        foreach ($comparison['missing'] as $item) {
            $html .= sprintf(
                '<tr class="missing"><td>%s</td><td>%s</td><td>%s</td></tr>',
                $item['method'],
                $item['path'],
                htmlspecialchars($item['summary'])
            );
        }

        $html .= <<<HTML
    </table>

    <h2>Extra Endpoints</h2>
    <table>
        <tr><th>Method</th><th>Path</th><th>Controller</th></tr>
HTML;

        foreach ($comparison['extra'] as $item) {
            $html .= sprintf(
                '<tr class="extra"><td>%s</td><td>%s</td><td>%s</td></tr>',
                $item['method'],
                $item['path'],
                htmlspecialchars($item['controller'])
            );
        }

        $html .= <<<HTML
    </table>
</body>
</html>
HTML;

        $reportPath = storage_path('app/openapi-validation-report.html');
        file_put_contents($reportPath, $html);
        $this->info('📄 HTML report generated: ' . $reportPath);
    }

    private function getCoverageColor(float $coverage): string
    {
        if ($coverage >= 95) return '#4CAF50'; // Green
        if ($coverage >= 80) return '#FF9800'; // Orange
        return '#F44336'; // Red
    }
}
