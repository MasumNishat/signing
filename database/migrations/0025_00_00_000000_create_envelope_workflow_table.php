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
        Schema::create('envelope_workflow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->unique()->constrained('envelopes')->cascadeOnDelete();

            $table->string('workflow_status', 50)->default('in_progress');
            $table->integer('current_routing_order')->default(1);

            // Scheduled Sending
            $table->boolean('scheduled_sending_enabled')->default(false);
            $table->timestamp('scheduled_sending_resume_date')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('envelope_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_workflow');
    }
};
