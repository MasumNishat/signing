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
        Schema::create('connect_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('connect_id', 100)->unique();

            $table->string('name')->nullable();
            $table->text('url_to_publish_to');

            // Events to publish
            $table->jsonb('envelope_events')->nullable();
            $table->jsonb('recipient_events')->nullable();

            // Settings
            $table->boolean('all_users')->default(true);
            $table->boolean('include_certificate_of_completion')->default(true);
            $table->boolean('include_documents')->default(true);
            $table->boolean('include_envelope_void_reason')->default(true);
            $table->boolean('include_sender_account_as_custom_field')->default(false);
            $table->boolean('include_time_zone_information')->default(true);

            // OAuth/Security
            $table->boolean('use_soap_interface')->default(false);
            $table->boolean('include_hmac')->default(false);
            $table->boolean('sign_message_with_x509_certificate')->default(false);

            $table->boolean('enabled')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('connect_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connect_configurations');
    }
};
