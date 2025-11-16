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
        Schema::create('watermarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->unique()->constrained('accounts')->cascadeOnDelete();

            $table->boolean('enabled')->default(false);
            $table->string('watermark_text')->nullable();
            $table->string('font', 100)->default('Arial');
            $table->integer('font_size')->default(12);
            $table->string('font_color', 20)->default('#000000');
            $table->integer('display_angle')->default(0);
            $table->integer('transparency')->default(50);
            $table->text('image_base64')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watermarks');
    }
};
