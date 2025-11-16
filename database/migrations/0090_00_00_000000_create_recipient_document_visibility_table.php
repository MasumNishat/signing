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
        Schema::create('recipient_document_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->constrained('envelopes')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('envelope_recipients')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('envelope_documents')->cascadeOnDelete();
            $table->boolean('visible')->default(true);

            // Indexes
            $table->index('envelope_id');
            $table->index('recipient_id');
            $table->unique(['recipient_id', 'document_id'], 'recipient_document_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipient_document_visibility');
    }
};
