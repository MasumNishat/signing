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
        Schema::create('envelope_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->constrained('envelopes')->cascadeOnDelete();
            $table->string('field_id', 100);

            $table->string('name');
            $table->text('value')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('show')->default(true);

            // Type: text, list
            $table->string('field_type', 50)->default('text');
            $table->jsonb('list_items')->nullable();

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
        Schema::dropIfExists('envelope_custom_fields');
    }
};
