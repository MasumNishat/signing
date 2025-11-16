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
        Schema::create('connect_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('connect_id')->nullable()->constrained('connect_configurations')->nullOnDelete();
            $table->string('log_id', 100)->unique();

            $table->string('envelope_id', 100)->nullable();
            $table->string('status', 50)->nullable()->comment('success, failed');
            $table->timestamp('created_date_time');

            $table->text('request_url')->nullable();
            $table->text('request_body')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error')->nullable();

            // Indexes
            $table->index('account_id');
            $table->index('connect_id');
            $table->index('envelope_id');
            $table->index('created_date_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connect_logs');
    }
};
