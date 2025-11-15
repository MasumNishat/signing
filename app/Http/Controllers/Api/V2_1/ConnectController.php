<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\ConnectConfiguration;
use App\Models\ConnectFailure;
use App\Models\ConnectLog;
use App\Services\ConnectService;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Connect Controller
 *
 * Handles webhook/connect configuration and event publishing endpoints.
 * Manages Connect configurations, logs, failures, OAuth config, and retry queue.
 *
 * Total Endpoints: 15 (from 19 in spec, some combined)
 */
class ConnectController extends BaseController
{
    /**
     * Connect service
     */
    protected ConnectService $connectService;

    /**
     * Webhook service
     */
    protected WebhookService $webhookService;

    /**
     * Initialize controller
     */
    public function __construct(ConnectService $connectService, WebhookService $webhookService)
    {
        $this->connectService = $connectService;
        $this->webhookService = $webhookService;
    }

    /**
     * Get Connect Configuration Information (list all)
     *
     * GET /v2.1/accounts/{accountId}/connect
     */
    public function index(string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $configurations = $this->connectService->listConfigurations($account);
            return $this->success($configurations, 'Connect configurations retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Create a connect configuration
     *
     * POST /v2.1/accounts/{accountId}/connect
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'url_to_publish_to' => 'required|url',
            'envelope_events' => 'nullable|array',
            'recipient_events' => 'nullable|array',
            'all_users' => 'nullable|boolean',
            'include_certificate_of_completion' => 'nullable|boolean',
            'include_documents' => 'nullable|boolean',
            'include_envelope_void_reason' => 'nullable|boolean',
            'include_sender_account_as_custom_field' => 'nullable|boolean',
            'include_time_zone_information' => 'nullable|boolean',
            'use_soap_interface' => 'nullable|boolean',
            'include_hmac' => 'nullable|boolean',
            'sign_message_with_x509_certificate' => 'nullable|boolean',
            'enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $config = $this->connectService->createConfiguration($account, $request->all());
            return $this->created($config, 'Connect configuration created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Update connect configuration
     *
     * PUT /v2.1/accounts/{accountId}/connect
     */
    public function update(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'connect_id' => 'required|string',
            'name' => 'nullable|string|max:255',
            'url_to_publish_to' => 'nullable|url',
            'envelope_events' => 'nullable|array',
            'recipient_events' => 'nullable|array',
            'enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $config = ConnectConfiguration::where('account_id', $account->id)
                ->where('connect_id', $request->input('connect_id'))
                ->firstOrFail();

            $updatedConfig = $this->connectService->updateConfiguration($config, $request->all());
            return $this->success($updatedConfig, 'Connect configuration updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get a specific Connect configuration
     *
     * GET /v2.1/accounts/{accountId}/connect/{connectId}
     */
    public function show(string $accountId, string $connectId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $config = $this->connectService->getConfiguration($account, $connectId);
            return $this->success($config, 'Connect configuration retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * Delete a connect configuration
     *
     * DELETE /v2.1/accounts/{accountId}/connect/{connectId}
     */
    public function destroy(string $accountId, string $connectId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $config = ConnectConfiguration::where('account_id', $account->id)
                ->where('connect_id', $connectId)
                ->firstOrFail();

            $this->connectService->deleteConfiguration($config);
            return $this->noContent('Connect configuration deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Republish Connect information for specific envelope (retry queue)
     *
     * PUT /v2.1/accounts/{accountId}/connect/envelopes/{envelopeId}/retry_queue
     */
    public function retryEnvelope(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $results = $this->webhookService->retryFailedDeliveries($account, $envelopeId);
            return $this->success($results, 'Envelope events republished successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Republish Connect information for multiple envelopes (retry queue)
     *
     * PUT /v2.1/accounts/{accountId}/connect/envelopes/retry_queue
     */
    public function retryEnvelopes(string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $results = $this->webhookService->retryFailedDeliveries($account);
            return $this->success($results, 'All failed events republished successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get Connect log
     *
     * GET /v2.1/accounts/{accountId}/connect/logs
     */
    public function logs(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'connect_id' => 'nullable|string',
            'envelope_id' => 'nullable|string',
            'status' => 'nullable|in:success,failed',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $filters = [
                'connect_id' => $request->input('connect_id'),
                'envelope_id' => $request->input('envelope_id'),
                'status' => $request->input('status'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
            ];

            $perPage = $request->input('per_page', 15);
            $logs = $this->connectService->getLogs($account, $filters, $perPage);

            return $this->paginated($logs, 'Connect logs retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get specific Connect log entry
     *
     * GET /v2.1/accounts/{accountId}/connect/logs/{logId}
     */
    public function getLog(string $accountId, string $logId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $log = $this->connectService->getLog($account, $logId);
            return $this->success($log, 'Connect log retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * Delete Connect log entry
     *
     * DELETE /v2.1/accounts/{accountId}/connect/logs/{logId}
     */
    public function deleteLog(string $accountId, string $logId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $log = ConnectLog::where('account_id', $account->id)
                ->where('log_id', $logId)
                ->firstOrFail();

            $this->connectService->deleteLog($log);
            return $this->noContent('Connect log deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get Connect failure log information
     *
     * GET /v2.1/accounts/{accountId}/connect/failures
     */
    public function failures(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'envelope_id' => 'nullable|string',
            'retryable' => 'nullable|in:true,false',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $filters = [
                'envelope_id' => $request->input('envelope_id'),
                'retryable' => $request->input('retryable'),
            ];

            $perPage = $request->input('per_page', 15);
            $failures = $this->connectService->getFailures($account, $filters, $perPage);

            return $this->paginated($failures, 'Connect failures retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete Connect failure log entry
     *
     * DELETE /v2.1/accounts/{accountId}/connect/failures/{failureId}
     */
    public function deleteFailure(string $accountId, string $failureId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $failure = ConnectFailure::where('account_id', $account->id)
                ->where('failure_id', $failureId)
                ->firstOrFail();

            $this->connectService->deleteFailure($failure);
            return $this->noContent('Connect failure deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get Connect OAuth Config
     *
     * GET /v2.1/accounts/{accountId}/connect/oauth
     */
    public function getOAuthConfig(string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $config = $this->connectService->getOAuthConfig($account);

            if (!$config) {
                return $this->error('OAuth configuration not found', 404);
            }

            return $this->success($config, 'OAuth configuration retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Set Connect OAuth Config
     *
     * POST /v2.1/accounts/{accountId}/connect/oauth
     */
    public function createOAuthConfig(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'connect_id' => 'nullable|string',
            'oauth_client_id' => 'required|string',
            'oauth_token_endpoint' => 'required|url',
            'oauth_authorization_endpoint' => 'required|url',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $config = $this->connectService->setOAuthConfig($account, $request->all());
            return $this->created($config, 'OAuth configuration created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Update Connect OAuth Config
     *
     * PUT /v2.1/accounts/{accountId}/connect/oauth
     */
    public function updateOAuthConfig(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'connect_id' => 'nullable|string',
            'oauth_client_id' => 'nullable|string',
            'oauth_token_endpoint' => 'nullable|url',
            'oauth_authorization_endpoint' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $config = $this->connectService->setOAuthConfig($account, $request->all());
            return $this->success($config, 'OAuth configuration updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete Connect OAuth Config
     *
     * DELETE /v2.1/accounts/{accountId}/connect/oauth
     */
    public function deleteOAuthConfig(string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $deleted = $this->connectService->deleteOAuthConfig($account);

            if ($deleted) {
                return $this->noContent('OAuth configuration deleted successfully');
            } else {
                return $this->error('OAuth configuration not found', 404);
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
