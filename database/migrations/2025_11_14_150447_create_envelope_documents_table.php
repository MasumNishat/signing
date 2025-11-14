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
        Schema::create('envelope_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->constrained('envelopes')->cascadeOnDelete();
            $table->string('document_id', 100);

            // Basic Info
            $table->string('name')->index();
            $table->text('document_base64')->nullable();
            $table->string('file_extension', 20)->nullable();
            $table->integer('order_number')->default(1);

            // Document Settings
            $table->string('display', 50)->default('inline');
            $table->boolean('include_in_download')->default(true);
            $table->boolean('signable')->default(true);

            // File Info
            $table->string('file_path', 500)->nullable();
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->integer('pages')->nullable();

            // Transform
            $table->boolean('transform_pdf_fields')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('envelope_id');
            $table->index('document_id');
            $table->index(['envelope_id', 'order_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_documents');
    }
};
