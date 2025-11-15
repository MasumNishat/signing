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
        Schema::create('watermark_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->unique()->constrained('accounts')->cascadeOnDelete();

            // Watermark Settings
            $table->boolean('enabled')->default(false);
            $table->string('watermark_text')->nullable();
            $table->string('watermark_font')->default('arial');
            $table->integer('watermark_font_size')->default(100);
            $table->string('watermark_font_color')->default('gray');
            $table->integer('watermark_transparency')->default(50);

            // Positioning
            $table->string('horizontal_alignment')->default('center')->comment('left, center, right');
            $table->string('vertical_alignment')->default('middle')->comment('top, middle, bottom');

            // Display Options
            $table->boolean('display_angle')->default(false);
            $table->integer('angle')->default(45);
            $table->boolean('display_on_all_pages')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watermark_configurations');
    }
};
