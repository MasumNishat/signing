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
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_log_id', 100)->unique();

            $table->timestamp('created_date_time')->useCurrent();

            // Request details
            $table->string('request_method', 10)->nullable();
            $table->text('request_url')->nullable();
            $table->jsonb('request_headers')->nullable();
            $table->text('request_body')->nullable();

            // Response details
            $table->integer('response_status')->nullable();
            $table->jsonb('response_headers')->nullable();
            $table->text('response_body')->nullable();

            // Performance and tracking
            $table->integer('duration_ms')->nullable();
            $table->string('ip_address', 50)->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('request_log_id');
            $table->index('account_id');
            $table->index('user_id');
            $table->index('created_date_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
