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
        Schema::create('envelope_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->constrained('envelopes')->cascadeOnDelete();
            $table->string('recipient_id', 100);

            // Recipient Info
            $table->string('recipient_type', 50)->comment('signer, carbon_copy, certified_delivery, in_person_signer, agent, editor, intermediary');
            $table->string('role_name')->nullable();
            $table->string('name');
            $table->string('email');

            // Routing
            $table->integer('routing_order')->default(1);

            // Status
            $table->string('status', 50)->default('created')->comment('created, sent, delivered, signed, declined, completed, fax_pending, auto_responded');
            $table->timestamp('signed_date_time')->nullable();
            $table->timestamp('delivered_date_time')->nullable();
            $table->timestamp('sent_date_time')->nullable();
            $table->timestamp('declined_date_time')->nullable();
            $table->text('declined_reason')->nullable();

            // Authentication
            $table->string('access_code', 100)->nullable();
            $table->boolean('require_id_lookup')->default(false);
            $table->string('id_check_configuration_name')->nullable();
            $table->string('phone_authentication_country_code', 10)->nullable();
            $table->string('phone_authentication_number', 50)->nullable();
            $table->string('sms_authentication_country_code', 10)->nullable();
            $table->string('sms_authentication_number', 50)->nullable();

            // Settings
            $table->boolean('can_sign_offline')->default(false);
            $table->boolean('require_signer_certificate')->default(false);
            $table->boolean('require_sign_on_paper')->default(false);
            $table->boolean('sign_in_each_location')->default(false);

            // Host Info (for in-person signing)
            $table->string('host_name')->nullable();
            $table->string('host_email')->nullable();

            // Metadata
            $table->string('client_user_id')->nullable();
            $table->text('embedded_recipient_start_url')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('envelope_id');
            $table->index('recipient_id');
            $table->index('status');
            $table->index('routing_order');
            $table->index(['envelope_id', 'routing_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_recipients');
    }
};
