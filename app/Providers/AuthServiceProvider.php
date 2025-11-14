<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Account::class => \App\Policies\AccountPolicy::class,
        \App\Models\ApiKey::class => \App\Policies\ApiKeyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Configure Passport token lifetimes
        Passport::tokensExpireIn(now()->addHours(1));
        Passport::refreshTokensExpireIn(now()->addDays(14));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // Define OAuth scopes
        Passport::tokensCan([
            // Account Management
            'account.read' => 'View account information',
            'account.write' => 'Modify account settings',

            // User Management
            'user.read' => 'View user information',
            'user.write' => 'Create and modify users',
            'user.delete' => 'Delete users',

            // Envelope Operations
            'envelope.read' => 'View envelopes',
            'envelope.write' => 'Create and modify envelopes',
            'envelope.send' => 'Send envelopes for signing',
            'envelope.delete' => 'Delete envelopes',
            'envelope.void' => 'Void envelopes',

            // Template Operations
            'template.read' => 'View templates',
            'template.write' => 'Create and modify templates',
            'template.delete' => 'Delete templates',

            // Brand Management
            'brand.read' => 'View brands',
            'brand.write' => 'Create and modify brands',
            'brand.delete' => 'Delete brands',

            // Billing & Invoicing
            'billing.read' => 'View billing information',
            'billing.write' => 'Modify billing settings',

            // Connect & Webhooks
            'connect.read' => 'View Connect configurations',
            'connect.write' => 'Modify Connect configurations',
            'connect.delete' => 'Delete Connect configurations',

            // Workspaces
            'workspace.read' => 'View workspaces',
            'workspace.write' => 'Create and modify workspaces',
            'workspace.delete' => 'Delete workspaces',

            // PowerForms
            'powerform.read' => 'View PowerForms',
            'powerform.write' => 'Create and modify PowerForms',
            'powerform.delete' => 'Delete PowerForms',

            // Signatures
            'signature.read' => 'View signatures',
            'signature.write' => 'Create and adopt signatures',
            'signature.delete' => 'Delete signatures',

            // Bulk Operations
            'bulk.read' => 'View bulk send lists',
            'bulk.write' => 'Create and modify bulk send lists',
            'bulk.send' => 'Execute bulk send operations',

            // Reports & Logs
            'report.read' => 'View reports and logs',
            'audit.read' => 'View audit logs',

            // Full Access
            '*' => 'Full API access',
        ]);

        // Set default scope
        Passport::setDefaultScope([
            'account.read',
            'user.read',
            'envelope.read',
            'template.read',
        ]);
    }
}
