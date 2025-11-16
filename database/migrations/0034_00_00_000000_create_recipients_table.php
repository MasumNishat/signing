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
        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('recipient_id', 100)->unique();
            $table->string('recipient_type', 50)->comment('signer, carbon_copy, certified_delivery');
            $table->string('email');
            $table->string('name');

            $table->integer('routing_order')->default(1);
            $table->string('status', 50)->default('created');

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('recipient_id');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipients');
    }
};
