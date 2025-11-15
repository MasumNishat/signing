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
        Schema::create('connect_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('failure_id', 100)->unique();

            $table->string('envelope_id', 100)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('failed_date_time')->nullable();

            $table->integer('retry_count')->default(0);

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('account_id');
            $table->index('envelope_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connect_failures');
    }
};
