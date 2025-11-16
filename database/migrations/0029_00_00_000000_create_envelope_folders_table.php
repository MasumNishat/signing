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
        Schema::create('envelope_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->constrained('envelopes')->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained('folders')->cascadeOnDelete();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('envelope_id');
            $table->index('folder_id');
            $table->unique(['envelope_id', 'folder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_folders');
    }
};
