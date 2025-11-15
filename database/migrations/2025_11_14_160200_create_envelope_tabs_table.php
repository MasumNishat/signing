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
        Schema::create('envelope_tabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->constrained('envelopes')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('envelope_documents')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('envelope_recipients')->cascadeOnDelete();
            $table->string('tab_id', 100);

            // Tab Type
            $table->string('tab_type', 50)->comment('sign_here, initial_here, date_signed, text, checkbox, radio_group, list, number, email, etc.');
            $table->string('tab_label')->nullable();

            // Position
            $table->integer('page_number');
            $table->integer('x_position');
            $table->integer('y_position');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();

            // Settings
            $table->boolean('required')->default(false);
            $table->boolean('locked')->default(false);
            $table->boolean('disabled')->default(false);
            $table->boolean('read_only')->default(false);
            $table->boolean('bold')->default(false);
            $table->boolean('italic')->default(false);
            $table->boolean('underline')->default(false);

            // Value
            $table->text('value')->nullable();
            $table->text('original_value')->nullable();

            // Validation
            $table->string('validation_type', 50)->nullable();
            $table->string('validation_pattern')->nullable();
            $table->string('validation_message')->nullable();
            $table->integer('min_length')->nullable();
            $table->integer('max_length')->nullable();

            // Conditional Logic
            $table->string('conditional_parent_label')->nullable();
            $table->string('conditional_parent_value')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('envelope_id');
            $table->index('document_id');
            $table->index('recipient_id');
            $table->index('tab_id');
            $table->index('tab_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_tabs');
    }
};
