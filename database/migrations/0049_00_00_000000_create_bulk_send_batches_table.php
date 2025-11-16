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
        Schema::create('bulk_send_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('batch_id', 100)->unique();

            $table->string('batch_name')->nullable();
            $table->string('status', 50)->default('queued')->comment('queued, processing, sent, failed');

            $table->integer('batch_size')->nullable();
            $table->integer('envelopes_sent')->default(0);
            $table->integer('envelopes_failed')->default(0);

            $table->timestamp('submitted_date')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('batch_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_send_batches');
    }
};
