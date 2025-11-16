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
        Schema::create('shared_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('item_type', 50)->comment('envelope, template');
            $table->string('item_id', 100);
            $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('shared_with_group_id')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('user_id');
            $table->index('item_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_access');
    }
};
