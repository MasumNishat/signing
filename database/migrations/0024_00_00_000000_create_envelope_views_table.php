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
        Schema::create('envelope_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->constrained('envelopes')->cascadeOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('envelope_recipients')->nullOnDelete();

            $table->string('view_type', 50)->comment('sender, recipient, correct, edit, shared');
            $table->text('view_url');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            // Indexes
            $table->index('envelope_id');
            $table->index('recipient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_views');
    }
};
