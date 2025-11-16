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
        Schema::create('account_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->unique()->constrained('accounts')->cascadeOnDelete();

            // Signing Settings
            $table->boolean('allow_signing_extensions')->default(false);
            $table->boolean('allow_signature_stamps')->default(true);
            $table->boolean('enable_signer_attachments')->default(true);

            // Security Settings
            $table->boolean('enable_two_factor_authentication')->default(false);
            $table->boolean('require_signing_captcha')->default(false);
            $table->integer('session_timeout_minutes')->default(20);

            // Branding Settings
            $table->boolean('can_self_brand_send')->default(false);
            $table->boolean('can_self_brand_sign')->default(false);

            // API Settings
            $table->boolean('enable_api_request_logging')->default(false);
            $table->integer('api_request_log_max_entries')->default(50);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_settings');
    }
};
