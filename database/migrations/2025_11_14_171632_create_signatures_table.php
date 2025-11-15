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
            $table->string('signature_id', 100)->unique();
            $table->string('signature_type', 50)->comment('signature, initials');
            $table->string('status', 50)->default('active')->comment('active, closed');

            $table->timestamp('adopted_date_time')->nullable();
            $table->timestamp('created_date_time')->useCurrent();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('account_id');
            $table->index('signature_id');
            $table->index('status');
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
