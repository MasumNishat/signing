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
        Schema::create('bulk_send_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('bulk_send_lists')->cascadeOnDelete();

            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->jsonb('custom_fields')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('list_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_send_recipients');
    }
};
