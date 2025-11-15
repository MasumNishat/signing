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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('user_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('suffix_name', 50)->nullable();
            $table->string('title', 100)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('country_code', 10)->nullable();

            // Status and Settings
            $table->string('user_status', 50)->default('active')->comment('active, inactive, closed');
            $table->string('user_type', 50)->default('user')->comment('user, admin, company_user');
            $table->string('login_status', 50)->default('not_logged_in')->nullable();
            $table->boolean('is_admin')->default(false);

            // Authentication
            $table->string('activation_access_code')->nullable();
            $table->boolean('send_activation_email')->default(true);
            $table->boolean('send_activation_on_invalid_login')->default(false);
            $table->timestamp('password_expiration')->nullable();
            $table->timestamp('last_login')->nullable();

            // Permission
            $table->foreignId('permission_profile_id')->nullable()->constrained('permission_profiles')->nullOnDelete();

            // Preferences
            $table->boolean('enable_connect_for_user')->default(false);
            $table->boolean('subscribe')->default(false);

            // Timestamps
            $table->timestamp('created_datetime')->useCurrent();
            $table->timestamp('user_profile_last_modified_date')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('email');
            $table->index('account_id');
            $table->index('user_status');
            $table->index(['account_id', 'user_status'], 'idx_account_user_status');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
