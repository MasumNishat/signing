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
        Schema::create('envelope_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->unique()->constrained('envelopes')->cascadeOnDelete();

            $table->foreignId('locked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('locked_by_user_name')->nullable();
            $table->timestamp('locked_until_date_time')->nullable();
            $table->integer('lock_duration_in_seconds')->nullable();
            $table->string('lock_type', 50)->default('edit');

            $table->timestamps();

            // Indexes
            $table->index('envelope_id');
            $table->index('locked_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_locks');
    }
};
