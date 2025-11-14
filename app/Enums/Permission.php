<?php

namespace App\Enums;

/**
 * Permission constants for the application.
 *
 * These permissions are stored in permission_profiles.permissions JSONB column.
 */
enum Permission: string
{
    // Account Management
    case MANAGE_ACCOUNT = 'can_manage_account';
    case VIEW_ACCOUNT = 'can_view_account';

    // User Management
    case MANAGE_USERS = 'can_manage_users';
    case VIEW_USERS = 'can_view_users';
    case CREATE_USERS = 'can_create_users';
    case DELETE_USERS = 'can_delete_users';

    // Envelope Operations
    case SEND_ENVELOPES = 'can_send_envelopes';
    case SIGN_ENVELOPES = 'can_sign_envelopes';
    case VIEW_ENVELOPES = 'can_view_envelopes';
    case DELETE_ENVELOPES = 'can_delete_envelopes';
    case VOID_ENVELOPES = 'can_void_envelopes';
    case CORRECT_ENVELOPES = 'can_correct_envelopes';

    // Template Management
    case MANAGE_TEMPLATES = 'can_manage_templates';
    case CREATE_TEMPLATES = 'can_create_templates';
    case VIEW_TEMPLATES = 'can_view_templates';
    case DELETE_TEMPLATES = 'can_delete_templates';

    // Brand Management
    case MANAGE_BRANDING = 'can_manage_branding';
    case VIEW_BRANDING = 'can_view_branding';

    // Billing & Invoicing
    case MANAGE_BILLING = 'can_manage_billing';
    case VIEW_BILLING = 'can_view_billing';

    // Connect & Webhooks
    case MANAGE_CONNECT = 'can_manage_connect';
    case VIEW_CONNECT = 'can_view_connect';

    // Workspaces
    case MANAGE_WORKSPACES = 'can_manage_workspaces';
    case VIEW_WORKSPACES = 'can_view_workspaces';

    // PowerForms
    case MANAGE_POWERFORMS = 'can_manage_powerforms';
    case VIEW_POWERFORMS = 'can_view_powerforms';

    // Signatures
    case MANAGE_SIGNATURES = 'can_manage_signatures';
    case ADOPT_SIGNATURES = 'can_adopt_signatures';

    // Bulk Operations
    case BULK_SEND = 'can_bulk_send';
    case VIEW_BULK_LISTS = 'can_view_bulk_lists';

    // Reports & Audit
    case VIEW_REPORTS = 'can_view_reports';
    case VIEW_AUDIT_LOGS = 'can_view_audit_logs';

    // API Access
    case USE_API = 'can_use_api';
    case MANAGE_API_KEYS = 'can_manage_api_keys';

    /**
     * Get all permissions as an array.
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get permission label.
     */
    public function label(): string
    {
        return match($this) {
            self::MANAGE_ACCOUNT => 'Manage Account',
            self::VIEW_ACCOUNT => 'View Account',
            self::MANAGE_USERS => 'Manage Users',
            self::VIEW_USERS => 'View Users',
            self::CREATE_USERS => 'Create Users',
            self::DELETE_USERS => 'Delete Users',
            self::SEND_ENVELOPES => 'Send Envelopes',
            self::SIGN_ENVELOPES => 'Sign Envelopes',
            self::VIEW_ENVELOPES => 'View Envelopes',
            self::DELETE_ENVELOPES => 'Delete Envelopes',
            self::VOID_ENVELOPES => 'Void Envelopes',
            self::CORRECT_ENVELOPES => 'Correct Envelopes',
            self::MANAGE_TEMPLATES => 'Manage Templates',
            self::CREATE_TEMPLATES => 'Create Templates',
            self::VIEW_TEMPLATES => 'View Templates',
            self::DELETE_TEMPLATES => 'Delete Templates',
            self::MANAGE_BRANDING => 'Manage Branding',
            self::VIEW_BRANDING => 'View Branding',
            self::MANAGE_BILLING => 'Manage Billing',
            self::VIEW_BILLING => 'View Billing',
            self::MANAGE_CONNECT => 'Manage Connect',
            self::VIEW_CONNECT => 'View Connect',
            self::MANAGE_WORKSPACES => 'Manage Workspaces',
            self::VIEW_WORKSPACES => 'View Workspaces',
            self::MANAGE_POWERFORMS => 'Manage PowerForms',
            self::VIEW_POWERFORMS => 'View PowerForms',
            self::MANAGE_SIGNATURES => 'Manage Signatures',
            self::ADOPT_SIGNATURES => 'Adopt Signatures',
            self::BULK_SEND => 'Bulk Send',
            self::VIEW_BULK_LISTS => 'View Bulk Lists',
            self::VIEW_REPORTS => 'View Reports',
            self::VIEW_AUDIT_LOGS => 'View Audit Logs',
            self::USE_API => 'Use API',
            self::MANAGE_API_KEYS => 'Manage API Keys',
        };
    }
}
