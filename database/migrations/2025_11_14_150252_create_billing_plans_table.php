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
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_id', 100)->unique();
            $table->string('plan_name');
            $table->string('plan_classification', 100)->nullable();

            // Pricing
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('per_seat_price', 10, 2)->nullable();
            $table->decimal('support_incident_fee', 10, 2)->nullable();
            $table->decimal('support_plan_fee', 10, 2)->nullable();

            // Plan Limits
            $table->integer('included_seats')->default(0);
            $table->boolean('enable_support')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};
