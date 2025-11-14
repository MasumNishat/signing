<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permission_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->cascadeOnDelete();
            $table->string('profile_name');
            $table->boolean('is_default')->default(false);

            // User Management Permissions
            $table->boolean('can_manage_users')->default(false);
            $table->boolean('can_view_users')->default(false);
            $table->boolean('can_manage_admins')->default(false);
            $table->boolean('can_manage_groups')->default(false);

            // Account Management Permissions
            $table->boolean('can_manage_account_settings')->default(false);
            $table->boolean('can_manage_account_security_settings')->default(false);
            $table->boolean('can_manage_reporting')->default(false);
            $table->boolean('can_manage_sharing')->default(false);
            $table->boolean('can_manage_envelope_transfer')->default(false);
            $table->boolean('can_manage_signing_groups')->default(false);

            // Integration Permissions
            $table->boolean('can_manage_connect')->default(false);
            $table->boolean('can_manage_document_retention')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('profile_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_profiles');
    }
};
