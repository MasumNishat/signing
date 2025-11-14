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
        Schema::create('envelope_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('envelope_workflow')->cascadeOnDelete();
            $table->string('step_id', 100);

            $table->string('step_name')->nullable();
            $table->string('step_status', 50)->default('inactive');
            $table->string('trigger_on_item', 50)->nullable();

            // Delayed Routing
            $table->boolean('delayed_routing_enabled')->default(false);
            $table->integer('delay_hours')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('workflow_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_workflow_steps');
    }
};
