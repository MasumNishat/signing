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
        Schema::create('identity_verification_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('workflow_id', 100)->unique();
            $table->string('workflow_name');
            $table->string('workflow_type', 100)->nullable();

            $table->string('default_name')->nullable();
            $table->text('default_description')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('workflow_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_verification_workflows');
    }
};
