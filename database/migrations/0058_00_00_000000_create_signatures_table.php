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
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('signature_id', 100)->unique();
            $table->string('signature_type', 50)->default('signature')->comment('signature, initials, stamp');
            $table->string('signature_name')->nullable();
            $table->string('status', 50)->default('active')->comment('active, closed');
            $table->string('font_style', 100)->nullable()->comment('lucida_console, lucida_handwriting, etc.');
            $table->string('phone_number', 50)->nullable();
            $table->string('stamp_type', 50)->nullable();
            $table->integer('stamp_size_mm')->nullable();

            $table->timestamp('adopted_date_time')->nullable();
            $table->timestamp('created_date_time')->useCurrent();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('account_id');
            $table->index('user_id');
            $table->index('signature_id');
            $table->index('status');
            $table->index('signature_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
