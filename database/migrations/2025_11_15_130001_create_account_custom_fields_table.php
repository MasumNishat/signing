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
        Schema::create('account_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();

            // Field Information
            $table->string('field_id')->unique()->index();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();

            // Field Type
            $table->string('field_type')->default('text')->comment('text, list');
            $table->json('list_items')->nullable()->comment('For list type fields');

            // Field Configuration
            $table->boolean('required')->default(false);
            $table->boolean('show')->default(true);
            $table->integer('max_length')->nullable();
            $table->integer('order')->default(0);

            // Metadata
            $table->timestamps();

            // Indexes
            $table->index(['account_id', 'name'], 'idx_account_field_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_custom_fields');
    }
};
