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
        Schema::create('powerform_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('powerform_id')->constrained('powerforms')->cascadeOnDelete();
            $table->foreignId('envelope_id')->nullable()->constrained('envelopes')->nullOnDelete();

            $table->string('submitter_name')->nullable();
            $table->string('submitter_email')->nullable();
            $table->jsonb('form_data')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('powerform_id');
            $table->index('envelope_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('powerform_submissions');
    }
};
