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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Contact Information
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();

            // Additional Contact Details
            $table->string('phone_number')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->json('shared_user')->nullable()->comment('User who shared this contact');

            // Contact metadata
            $table->string('contact_id')->nullable()->comment('External contact ID');
            $table->string('contact_uri')->nullable();
            $table->string('error_details')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['account_id', 'user_id'], 'idx_account_user');
            $table->index(['user_id', 'email'], 'idx_user_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
