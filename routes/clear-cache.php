<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/**
 * Cache clearing routes - For development only
 * Visit: http://localhost:8000/clear-all-cache
 */

Route::get('/clear-all-cache', function() {
    try {
        // Clear view cache
        Artisan::call('view:clear');
        $output[] = 'View cache cleared';

        // Clear config cache
        Artisan::call('config:clear');
        $output[] = 'Config cache cleared';

        // Clear application cache
        Artisan::call('cache:clear');
        $output[] = 'Application cache cleared';

        // Clear route cache
        Artisan::call('route:clear');
        $output[] = 'Route cache cleared';

        return response()->json([
            'success' => true,
            'message' => 'All caches cleared successfully!',
            'details' => $output ?? []
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/clear-views', function() {
    try {
        Artisan::call('view:clear');

        return response()->json([
            'success' => true,
            'message' => 'View cache cleared! Please refresh your page.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});
