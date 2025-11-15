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
        Schema::create('envelope_transfer_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('rule_id', 100)->unique();

            $table->string('rule_name')->nullable();
            $table->boolean('enabled')->default(true);

            // From/To
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('from_group_id')->nullable();
            $table->bigInteger('to_group_id')->nullable();

            // Conditions
            $table->date('modified_start_date')->nullable();
            $table->date('modified_end_date')->nullable();
            $table->jsonb('envelope_types')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('from_user_id');
            $table->index('to_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envelope_transfer_rules');
    }
};
