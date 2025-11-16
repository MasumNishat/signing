<?php

// Quick script to check OAuth configuration (run on your local machine)
// Usage: php check-oauth.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== OAuth Configuration Check ===\n\n";

try {
    // Check if database is accessible
    $clients = \DB::table('oauth_clients')->get();

    echo "✅ Database connection: OK\n";
    echo "✅ Found " . $clients->count() . " OAuth client(s)\n\n";

    if ($clients->count() === 0) {
        echo "⚠️  No OAuth clients found. Run: php artisan passport:install\n";
    } else {
        echo "OAuth Clients:\n";
        echo str_repeat("-", 80) . "\n";

        foreach ($clients as $client) {
            echo "ID: {$client->id}\n";
            echo "Name: {$client->name}\n";
            echo "Secret: " . ($client->secret ? substr($client->secret, 0, 20) . "..." : "N/A (public client)") . "\n";
            echo "Redirect: {$client->redirect}\n";
            echo "Personal Access Client: " . ($client->personal_access_client ? "Yes" : "No") . "\n";
            echo "Password Client: " . ($client->password_client ? "Yes" : "No") . "\n";
            echo "Revoked: " . ($client->revoked ? "Yes" : "No") . "\n";
            echo str_repeat("-", 80) . "\n";
        }
    }

    // Check keys
    echo "\nEncryption Keys:\n";
    $privateKey = storage_path('oauth-private.key');
    $publicKey = storage_path('oauth-public.key');

    echo "Private key: " . (file_exists($privateKey) ? "✅ Exists (" . filesize($privateKey) . " bytes)" : "❌ Missing") . "\n";
    echo "Public key: " . (file_exists($publicKey) ? "✅ Exists (" . filesize($publicKey) . " bytes)" : "❌ Missing") . "\n";

    // Check tables
    echo "\nDatabase Tables:\n";
    $tables = ['oauth_auth_codes', 'oauth_access_tokens', 'oauth_refresh_tokens', 'oauth_clients', 'oauth_device_codes'];

    foreach ($tables as $table) {
        $exists = \Schema::hasTable($table);
        echo ($exists ? "✅" : "❌") . " {$table}\n";
    }

    echo "\n=== Configuration Complete ===\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nMake sure:\n";
    echo "1. PostgreSQL is running\n";
    echo "2. Database 'signing_api' exists\n";
    echo "3. Migrations have been run\n";
}
