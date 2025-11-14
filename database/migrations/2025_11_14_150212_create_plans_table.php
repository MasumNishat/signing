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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_id', 100)->unique();
            $table->string('plan_name')->index();
            $table->string('plan_type', 50)->nullable()->comment('free, pro, business, enterprise');

            // Limits
            $table->integer('max_users')->default(1);
            $table->integer('max_envelopes_per_month')->default(5);
            $table->integer('max_storage_gb')->default(5);

            // Features (JSONB for flexible feature configuration)
            $table->jsonb('features')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
