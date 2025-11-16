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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Action details
            $table->string('action', 100);
            $table->string('resource_type', 100)->nullable();
            $table->string('resource_id', 100)->nullable();

            // Change tracking
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();

            // Request context
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('account_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('resource_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
