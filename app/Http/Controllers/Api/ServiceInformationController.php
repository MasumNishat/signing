<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * ServiceInformationController
 *
 * Provides API version information and service capabilities.
 * This is a root-level endpoint that helps clients discover API versions.
 */
class ServiceInformationController extends Controller
{
    /**
     * GET /service_information
     *
     * Retrieves the available REST API versions
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'apiVersions' => [
                [
                    'version' => 'v2.1',
                    'versionUrl' => url('/api/v2.1'),
                    'isCurrentVersion' => true,
                ],
                [
                    'version' => 'v2',
                    'versionUrl' => url('/api/v2.1'), // v2.1 is compatible with v2
                    'isCurrentVersion' => false,
                ],
            ],
            'productVersion' => config('app.version', '1.0.0'),
            'productName' => config('app.name', 'Signing API'),
            'links' => [
                [
                    'rel' => 'documentation',
                    'href' => url('/docs'),
                ],
                [
                    'rel' => 'openapi',
                    'href' => url('/api/documentation'),
                ],
            ],
        ], 200);
    }
}
