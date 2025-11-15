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
        Schema::create('envelope_purge_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->unique()->constrained('accounts')->cascadeOnDelete();

            $table->boolean('enable_purge')->default(false);
            $table->integer('purge_interval_days')->default(365);
            $table->integer('retain_completed_envelopes_days')->default(365);
            $table->integer('retain_voided_envelopes_days')->default(90);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_purge_configurations');
    }
};
