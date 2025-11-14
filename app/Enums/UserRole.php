<?php

namespace App\Enums;

/**
 * User role definitions.
 *
 * These map to permission profiles with predefined permission sets.
 */
enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ACCOUNT_ADMIN = 'account_admin';
    case ACCOUNT_MANAGER = 'account_manager';
    case SENDER = 'sender';
    case SIGNER = 'signer';
    case VIEWER = 'viewer';

    /**
     * Get role label.
     */
    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::ACCOUNT_ADMIN => 'Account Administrator',
            self::ACCOUNT_MANAGER => 'Account Manager',
            self::SENDER => 'Sender',
            self::SIGNER => 'Signer',
            self::VIEWER => 'Viewer',
        };
    }

    /**
     * Get permissions for this role.
     */
    public function permissions(): array
    {
        return match($this) {
            self::SUPER_ADMIN => Permission::all(),

            self::ACCOUNT_ADMIN => [
                Permission::MANAGE_ACCOUNT->value,
                Permission::VIEW_ACCOUNT->value,
                Permission::MANAGE_USERS->value,
                Permission::VIEW_USERS->value,
                Permission::CREATE_USERS->value,
                Permission::DELETE_USERS->value,
                Permission::SEND_ENVELOPES->value,
                Permission::SIGN_ENVELOPES->value,
                Permission::VIEW_ENVELOPES->value,
                Permission::DELETE_ENVELOPES->value,
                Permission::VOID_ENVELOPES->value,
                Permission::CORRECT_ENVELOPES->value,
                Permission::MANAGE_TEMPLATES->value,
                Permission::CREATE_TEMPLATES->value,
                Permission::VIEW_TEMPLATES->value,
                Permission::DELETE_TEMPLATES->value,
                Permission::MANAGE_BRANDING->value,
                Permission::VIEW_BRANDING->value,
                Permission::MANAGE_BILLING->value,
                Permission::VIEW_BILLING->value,
                Permission::MANAGE_CONNECT->value,
                Permission::VIEW_CONNECT->value,
                Permission::MANAGE_WORKSPACES->value,
                Permission::VIEW_WORKSPACES->value,
                Permission::MANAGE_POWERFORMS->value,
                Permission::VIEW_POWERFORMS->value,
                Permission::MANAGE_SIGNATURES->value,
                Permission::ADOPT_SIGNATURES->value,
                Permission::BULK_SEND->value,
                Permission::VIEW_BULK_LISTS->value,
                Permission::VIEW_REPORTS->value,
                Permission::VIEW_AUDIT_LOGS->value,
                Permission::USE_API->value,
                Permission::MANAGE_API_KEYS->value,
            ],

            self::ACCOUNT_MANAGER => [
                Permission::VIEW_ACCOUNT->value,
                Permission::VIEW_USERS->value,
                Permission::SEND_ENVELOPES->value,
                Permission::SIGN_ENVELOPES->value,
                Permission::VIEW_ENVELOPES->value,
                Permission::VOID_ENVELOPES->value,
                Permission::MANAGE_TEMPLATES->value,
                Permission::CREATE_TEMPLATES->value,
                Permission::VIEW_TEMPLATES->value,
                Permission::VIEW_BRANDING->value,
                Permission::VIEW_BILLING->value,
                Permission::VIEW_CONNECT->value,
                Permission::MANAGE_WORKSPACES->value,
                Permission::VIEW_WORKSPACES->value,
                Permission::MANAGE_POWERFORMS->value,
                Permission::VIEW_POWERFORMS->value,
                Permission::MANAGE_SIGNATURES->value,
                Permission::ADOPT_SIGNATURES->value,
                Permission::BULK_SEND->value,
                Permission::VIEW_BULK_LISTS->value,
                Permission::VIEW_REPORTS->value,
                Permission::USE_API->value,
            ],

            self::SENDER => [
                Permission::VIEW_ACCOUNT->value,
                Permission::SEND_ENVELOPES->value,
                Permission::SIGN_ENVELOPES->value,
                Permission::VIEW_ENVELOPES->value,
                Permission::VIEW_TEMPLATES->value,
                Permission::VIEW_WORKSPACES->value,
                Permission::VIEW_POWERFORMS->value,
                Permission::ADOPT_SIGNATURES->value,
                Permission::VIEW_BULK_LISTS->value,
                Permission::USE_API->value,
            ],

            self::SIGNER => [
                Permission::SIGN_ENVELOPES->value,
                Permission::VIEW_ENVELOPES->value,
                Permission::ADOPT_SIGNATURES->value,
            ],

            self::VIEWER => [
                Permission::VIEW_ACCOUNT->value,
                Permission::VIEW_ENVELOPES->value,
                Permission::VIEW_TEMPLATES->value,
                Permission::VIEW_REPORTS->value,
            ],
        };
    }

    /**
     * Get permissions as associative array for database storage.
     */
    public function permissionsArray(): array
    {
        $permissions = [];
        foreach ($this->permissions() as $permission) {
            $permissions[$permission] = true;
        }
        return $permissions;
    }
}
