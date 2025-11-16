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
        Schema::create('powerforms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('powerform_id', 100)->unique();

            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->text('powerform_url')->nullable();

            // Email settings
            $table->string('email_subject', 500)->nullable();
            $table->text('email_body')->nullable();

            // Usage limits
            $table->integer('uses_remaining')->nullable();
            $table->string('limit_use_interval', 50)->nullable();
            $table->integer('limit_use_interval_units')->nullable();
            $table->boolean('limit_use_interval_enabled')->default(false);

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index('powerform_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('powerforms');
    }
};
