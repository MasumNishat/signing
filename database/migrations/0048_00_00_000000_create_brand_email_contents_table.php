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
        Schema::create('brand_email_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('email_content_type', 100);
            $table->text('content')->nullable();
            $table->string('email_to_link', 500)->nullable();
            $table->string('link_text')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('brand_id');
            $table->index('email_content_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_email_contents');
    }
};
