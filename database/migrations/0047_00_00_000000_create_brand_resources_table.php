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
        Schema::create('brand_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('resource_content_type', 100)->comment('email, sending, signing, signing_captive');
            $table->string('file_path', 500);
            $table->string('file_name')->nullable();
            $table->string('mime_type', 100)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('brand_id');
            $table->index('resource_content_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_resources');
    }
};
