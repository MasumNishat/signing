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
        Schema::create('envelope_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->constrained('envelopes')->cascadeOnDelete();
            $table->string('attachment_id', 100);

            $table->string('label')->nullable();
            $table->string('attachment_type', 50)->nullable()->comment('sender, signer');
            $table->text('data')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('envelope_id');
            $table->index('attachment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_attachments');
    }
};
