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
        Schema::create('envelopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('envelope_id', 100)->unique();

            // Basic Info
            $table->string('email_subject', 500)->nullable();
            $table->text('email_blurb')->nullable();
            $table->string('status', 50)->default('created')->comment('created, sent, delivered, signed, completed, declined, voided');

            // Sender Info
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();

            // Status Dates
            $table->timestamp('created_date_time')->useCurrent();
            $table->timestamp('sent_date_time')->nullable();
            $table->timestamp('delivered_date_time')->nullable();
            $table->timestamp('signed_date_time')->nullable();
            $table->timestamp('completed_date_time')->nullable();
            $table->timestamp('declined_date_time')->nullable();
            $table->timestamp('voided_date_time')->nullable();
            $table->text('voided_reason')->nullable();

            // Settings
            $table->boolean('enable_wet_sign')->default(false);
            $table->boolean('allow_markup')->default(true);
            $table->boolean('allow_reassign')->default(true);
            $table->boolean('allow_view_history')->default(true);
            $table->boolean('enforce_signer_visibility')->default(false);
            $table->boolean('is_signature_provider_envelope')->default(false);

            // Notification Settings
            $table->boolean('use_disclosure')->default(true);
            $table->boolean('reminder_enabled')->default(false);
            $table->integer('reminder_delay')->nullable();
            $table->integer('reminder_frequency')->nullable();
            $table->boolean('expire_enabled')->default(false);
            $table->integer('expire_after')->nullable();
            $table->integer('expire_warn')->nullable();

            // Workflow
            $table->boolean('is_dynamic_envelope')->default(false);
            $table->boolean('enable_sequential_signing')->default(false);

            // Metadata URIs
            $table->text('custom_fields_uri')->nullable();
            $table->text('documents_uri')->nullable();
            $table->text('recipients_uri')->nullable();
            $table->text('envelope_uri')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('account_id');
            $table->index('envelope_id');
            $table->index('status');
            $table->index('sender_user_id');
            $table->index('created_date_time');
            $table->index(['account_id', 'status', 'created_date_time'], 'idx_envelope_account_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelopes');
    }
};
