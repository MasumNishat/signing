<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Models\Account;
use App\Models\ConnectConfiguration;
use App\Models\ConnectFailure;
use App\Models\ConnectLog;
use App\Models\ConnectOAuthConfig;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ConnectService
 *
 * Business logic for Connect/Webhook configuration management.
 * Handles CRUD operations for Connect configurations, logs, failures, and OAuth config.
 */
class ConnectService
{
    /**
     * Create a new Connect configuration
     *
     * @param Account $account
     * @param array $data
     * @return ConnectConfiguration
     * @throws BusinessLogicException
     */
    public function createConfiguration(Account $account, array $data): ConnectConfiguration
    {
        DB::beginTransaction();

        try {
            $config = ConnectConfiguration::create([
                'account_id' => $account->id,
                'name' => $data['name'] ?? null,
                'url_to_publish_to' => $data['url_to_publish_to'],
                'envelope_events' => $data['envelope_events'] ?? [],
                'recipient_events' => $data['recipient_events'] ?? [],
                'all_users' => $data['all_users'] ?? true,
                'include_certificate_of_completion' => $data['include_certificate_of_completion'] ?? true,
                'include_documents' => $data['include_documents'] ?? true,
                'include_envelope_void_reason' => $data['include_envelope_void_reason'] ?? true,
                'include_sender_account_as_custom_field' => $data['include_sender_account_as_custom_field'] ?? false,
                'include_time_zone_information' => $data['include_time_zone_information'] ?? true,
                'use_soap_interface' => $data['use_soap_interface'] ?? false,
                'include_hmac' => $data['include_hmac'] ?? false,
                'sign_message_with_x509_certificate' => $data['sign_message_with_x509_certificate'] ?? false,
                'enabled' => $data['enabled'] ?? true,
            ]);

            DB::commit();

            return $config->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Connect configuration creation failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to create Connect configuration: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing Connect configuration
     *
     * @param ConnectConfiguration $config
     * @param array $data
     * @return ConnectConfiguration
     * @throws BusinessLogicException
     */
    public function updateConfiguration(ConnectConfiguration $config, array $data): ConnectConfiguration
    {
        DB::beginTransaction();

        try {
            $config->update([
                'name' => $data['name'] ?? $config->name,
                'url_to_publish_to' => $data['url_to_publish_to'] ?? $config->url_to_publish_to,
                'envelope_events' => $data['envelope_events'] ?? $config->envelope_events,
                'recipient_events' => $data['recipient_events'] ?? $config->recipient_events,
                'all_users' => $data['all_users'] ?? $config->all_users,
                'include_certificate_of_completion' => $data['include_certificate_of_completion'] ?? $config->include_certificate_of_completion,
                'include_documents' => $data['include_documents'] ?? $config->include_documents,
                'include_envelope_void_reason' => $data['include_envelope_void_reason'] ?? $config->include_envelope_void_reason,
                'include_sender_account_as_custom_field' => $data['include_sender_account_as_custom_field'] ?? $config->include_sender_account_as_custom_field,
                'include_time_zone_information' => $data['include_time_zone_information'] ?? $config->include_time_zone_information,
                'use_soap_interface' => $data['use_soap_interface'] ?? $config->use_soap_interface,
                'include_hmac' => $data['include_hmac'] ?? $config->include_hmac,
                'sign_message_with_x509_certificate' => $data['sign_message_with_x509_certificate'] ?? $config->sign_message_with_x509_certificate,
                'enabled' => $data['enabled'] ?? $config->enabled,
            ]);

            DB::commit();

            return $config->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Connect configuration update failed', [
                'connect_id' => $config->connect_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update Connect configuration: ' . $e->getMessage());
        }
    }

    /**
     * Delete a Connect configuration
     *
     * @param ConnectConfiguration $config
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteConfiguration(ConnectConfiguration $config): bool
    {
        try {
            return $config->delete();
        } catch (\Exception $e) {
            Log::error('Connect configuration deletion failed', [
                'connect_id' => $config->connect_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete Connect configuration: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific Connect configuration
     *
     * @param Account $account
     * @param string $connectId
     * @return ConnectConfiguration
     * @throws ResourceNotFoundException
     */
    public function getConfiguration(Account $account, string $connectId): ConnectConfiguration
    {
        $config = ConnectConfiguration::where('account_id', $account->id)
            ->where('connect_id', $connectId)
            ->with(['logs', 'oauthConfig'])
            ->first();

        if (!$config) {
            throw new ResourceNotFoundException('Connect configuration not found');
        }

        return $config;
    }

    /**
     * List all Connect configurations for an account
     *
     * @param Account $account
     * @return Collection
     */
    public function listConfigurations(Account $account): Collection
    {
        return ConnectConfiguration::where('account_id', $account->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get Connect logs with pagination and filters
     *
     * @param Account $account
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLogs(Account $account, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ConnectLog::where('account_id', $account->id);

        // Filter by connect configuration
        if (!empty($filters['connect_id'])) {
            $config = ConnectConfiguration::where('account_id', $account->id)
                ->where('connect_id', $filters['connect_id'])
                ->first();

            if ($config) {
                $query->where('connect_id', $config->id);
            }
        }

        // Filter by envelope
        if (!empty($filters['envelope_id'])) {
            $query->where('envelope_id', $filters['envelope_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->where('created_date_time', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('created_date_time', '<=', $filters['to_date']);
        }

        // Sort by most recent first
        $query->recent();

        return $query->paginate($perPage);
    }

    /**
     * Get a specific Connect log
     *
     * @param Account $account
     * @param string $logId
     * @return ConnectLog
     * @throws ResourceNotFoundException
     */
    public function getLog(Account $account, string $logId): ConnectLog
    {
        $log = ConnectLog::where('account_id', $account->id)
            ->where('log_id', $logId)
            ->with(['connectConfiguration', 'envelope'])
            ->first();

        if (!$log) {
            throw new ResourceNotFoundException('Connect log not found');
        }

        return $log;
    }

    /**
     * Delete a Connect log
     *
     * @param ConnectLog $log
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteLog(ConnectLog $log): bool
    {
        try {
            return $log->delete();
        } catch (\Exception $e) {
            Log::error('Connect log deletion failed', [
                'log_id' => $log->log_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete Connect log: ' . $e->getMessage());
        }
    }

    /**
     * Get Connect failures
     *
     * @param Account $account
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFailures(Account $account, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ConnectFailure::where('account_id', $account->id);

        // Filter by envelope
        if (!empty($filters['envelope_id'])) {
            $query->where('envelope_id', $filters['envelope_id']);
        }

        // Filter by retryable status
        if (!empty($filters['retryable'])) {
            if ($filters['retryable'] === 'true' || $filters['retryable'] === true) {
                $query->retryable();
            }
        }

        // Sort by most recent first
        $query->recent();

        return $query->paginate($perPage);
    }

    /**
     * Delete a Connect failure
     *
     * @param ConnectFailure $failure
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteFailure(ConnectFailure $failure): bool
    {
        try {
            return $failure->delete();
        } catch (\Exception $e) {
            Log::error('Connect failure deletion failed', [
                'failure_id' => $failure->failure_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete Connect failure: ' . $e->getMessage());
        }
    }

    /**
     * Create or update OAuth configuration
     *
     * @param Account $account
     * @param array $data
     * @return ConnectOAuthConfig
     * @throws BusinessLogicException
     */
    public function setOAuthConfig(Account $account, array $data): ConnectOAuthConfig
    {
        DB::beginTransaction();

        try {
            // Find or create OAuth config for account
            $oauthConfig = ConnectOAuthConfig::firstOrNew(['account_id' => $account->id]);

            // Get connect_id if provided
            $connectId = null;
            if (!empty($data['connect_id'])) {
                $connect = ConnectConfiguration::where('account_id', $account->id)
                    ->where('connect_id', $data['connect_id'])
                    ->first();

                if ($connect) {
                    $connectId = $connect->id;
                }
            }

            $oauthConfig->fill([
                'connect_id' => $connectId,
                'oauth_client_id' => $data['oauth_client_id'] ?? $oauthConfig->oauth_client_id,
                'oauth_token_endpoint' => $data['oauth_token_endpoint'] ?? $oauthConfig->oauth_token_endpoint,
                'oauth_authorization_endpoint' => $data['oauth_authorization_endpoint'] ?? $oauthConfig->oauth_authorization_endpoint,
            ]);

            $oauthConfig->save();

            DB::commit();

            return $oauthConfig->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OAuth config creation/update failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to set OAuth configuration: ' . $e->getMessage());
        }
    }

    /**
     * Get OAuth configuration
     *
     * @param Account $account
     * @return ConnectOAuthConfig|null
     */
    public function getOAuthConfig(Account $account): ?ConnectOAuthConfig
    {
        return ConnectOAuthConfig::where('account_id', $account->id)->first();
    }

    /**
     * Delete OAuth configuration
     *
     * @param Account $account
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteOAuthConfig(Account $account): bool
    {
        try {
            return ConnectOAuthConfig::where('account_id', $account->id)->delete() > 0;
        } catch (\Exception $e) {
            Log::error('OAuth config deletion failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete OAuth configuration: ' . $e->getMessage());
        }
    }
}
