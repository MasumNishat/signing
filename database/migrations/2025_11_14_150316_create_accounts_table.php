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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number', 50)->unique();
            $table->string('account_name');
            $table->string('status', 50)->default('active')->comment('active, suspended, closed');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('envelope_partition_id', 100)->nullable();
            $table->boolean('can_upgrade')->default(true);
            $table->string('distributor_code', 100)->nullable();
            $table->foreignId('billing_plan_id')->nullable()->constrained('billing_plans')->nullOnDelete();

            // Address Information
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();

            // Settings
            $table->boolean('allow_tab_order')->default(true);
            $table->boolean('enable_sequential_signing')->default(false);
            $table->boolean('enable_recipient_viewing_notification')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('account_number');
            $table->index('status');
            $table->index('created_at');
            $table->index(['account_name', 'status'], 'idx_account_name_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
