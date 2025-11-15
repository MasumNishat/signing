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
        Schema::create('user_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principal_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('authorization_type', 50)->comment('principal, agent');
            $table->jsonb('permissions')->nullable();
            $table->string('status', 50)->default('active')->comment('active, inactive');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('principal_user_id');
            $table->index('agent_user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_authorizations');
    }
};
