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
        Schema::create('signing_group_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signing_group_id')->constrained('signing_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('email')->nullable();
            $table->string('user_name')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('signing_group_id');
            $table->index('user_id');
            $table->unique(['signing_group_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signing_group_users');
    }
};
