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
        Schema::create('identity_verification_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('workflow_id', 100)->unique();
            $table->string('workflow_name');
            $table->string('workflow_type', 100)->nullable()->comment('id_check, phone_auth, sms_auth, knowledge_based, id_lookup');
            $table->string('workflow_status', 50)->default('active')->comment('active, inactive');
            $table->string('workflow_label')->nullable();

            $table->string('default_name')->nullable();
            $table->text('default_description')->nullable();

            // Provider and configuration
            $table->string('signature_provider')->nullable()->comment('Third-party signature provider');
            $table->boolean('phone_auth_recipient_may_provide_number')->default(false);
            $table->string('id_check_configuration_name')->nullable();
            $table->string('sms_auth_configuration_name')->nullable();

            // Steps and options (JSONB for flexibility)
            $table->json('steps')->nullable()->comment('Workflow steps configuration');
            $table->json('input_options')->nullable()->comment('Input options for workflow');

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('workflow_id');
            $table->index('workflow_status');
            $table->index('workflow_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_verification_workflows');
    }
};
