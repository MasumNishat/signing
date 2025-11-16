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
        Schema::create('signature_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signature_id')->constrained('signatures')->cascadeOnDelete();
            $table->string('image_type', 50)->comment('signature_image, initials_image, stamp_image');
            $table->string('file_path', 500);
            $table->string('file_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->boolean('include_chrome')->default(false);
            $table->boolean('transparent_png')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('signature_id');
            $table->index('image_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_images');
    }
};
